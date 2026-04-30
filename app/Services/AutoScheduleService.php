<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\FacultyAvailability;
use App\Models\FacultySubject;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleGenerationConflict;
use App\Models\ScheduleGenerationRun;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoScheduleService
{
    protected array $days = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    protected array $timeSlots = [
        ['07:00', '07:30'],
        ['07:30', '08:00'],
        ['08:00', '08:30'],
        ['08:30', '09:00'],
        ['09:00', '09:30'],
        ['09:30', '10:00'],
        ['10:00', '10:30'],
        ['10:30', '11:00'],
        ['11:00', '11:30'],
        ['11:30', '12:00'],
        ['12:00', '12:30'],
        ['12:30', '13:00'],
        ['13:00', '13:30'],
        ['13:30', '14:00'],
        ['14:00', '14:30'],
        ['14:30', '15:00'],
        ['15:00', '15:30'],
        ['15:30', '16:00'],
        ['16:00', '16:30'],
        ['16:30', '17:00'],
        ['17:00', '17:30'],
        ['17:30', '18:00'],
        ['18:00', '18:30'],
        ['18:30', '19:00'],
        ['19:00', '19:30'],
        ['19:30', '20:00'],
        ['20:00', '20:30'],
        ['20:30', '21:00'],
    ];

    protected int $minimumGapMinutes = 30;
    protected int $maximumGapMinutes = 60;
    protected int $maxSubjectsPerDay = 2;

    protected array $lastSlotFailureReasons = [];

    public function generateForSection(
        int $sectionId,
        int $schoolYearId,
        int $semesterId,
        bool $replaceExisting = false
    ): array {
        return DB::transaction(function () use ($sectionId, $schoolYearId, $semesterId, $replaceExisting) {
            $section = Section::with('course')->findOrFail($sectionId);

            $generationRun = ScheduleGenerationRun::create([
                'section_id' => $section->id,
                'school_year_id' => $schoolYearId,
                'semester_id' => $semesterId,
                'status' => 'success',
                'total_created' => 0,
                'total_failed' => 0,
            ]);

            if ($replaceExisting) {
                Schedule::where('section_id', $section->id)
                    ->where('school_year_id', $schoolYearId)
                    ->where('semester_id', $semesterId)
                    ->delete();
            }

            $curricula = Curriculum::with('subject')
                ->where('course_id', $section->course_id)
                ->where('year_level', $section->year_level)
                ->where('semester_id', $semesterId)
                ->where('active', true)
                ->orderBy('sort_order')
                ->get();

            $curricula = $this->sortCurriculaByDifficulty(
                $curricula,
                $schoolYearId,
                $semesterId
            );

            if ($curricula->isEmpty()) {
                $generationRun->update([
                    'status' => 'failed',
                    'total_created' => 0,
                    'total_failed' => 1,
                ]);

                ScheduleGenerationConflict::create([
                    'generation_run_id' => $generationRun->id,
                    'schedule_id' => null,
                    'subject_id' => null,
                    'instructor_id' => null,
                    'room_id' => null,
                    'conflict_type' => 'NO_CURRICULUM',
                    'severity' => 'error',
                    'is_conflict' => true,
                    'message' => 'No active curriculum found for this section.',
                ]);

                return [
                    'generation_run_id' => $generationRun->id,
                    'section' => $section->load('course'),
                    'created' => [],
                    'failed' => [
                        [
                            'subject' => null,
                            'reason' => 'No active curriculum found for this section.',
                        ],
                    ],
                ];
            }

            $created = [];
            $failed = [];

            foreach ($curricula as $curriculum) {
                $subject = $curriculum->subject;
                $sessions = $this->buildSessions($subject);

                $instructorCandidates = FacultySubject::with('instructor')
                    ->where('subject_id', $subject->id)
                    ->whereHas('instructor', function ($query) {
                        $query->where(function ($q) {
                            $q->whereNull('archived')
                                ->orWhere('archived', 0);
                        })
                        ->where(function ($q) {
                            $q->whereNull('status')
                                ->orWhereIn('status', ['Active', 'active']);
                        });
                    })
                    ->orderByDesc('is_primary')
                    ->orderByDesc('priority_score')
                    ->get();

                // Load balancing improvement:
                // Only instructors assigned in faculty_subjects are considered.
                // Among those allowed instructors, prefer the one with lighter current load,
                // while still respecting is_primary and priority_score.
                $instructorCandidates = $this->sortInstructorCandidatesByLoadBalance(
                    $instructorCandidates,
                    $schoolYearId,
                    $semesterId
                );

                if ($instructorCandidates->isEmpty()) {
                    $reason = 'No instructor assigned to this subject.';

                    $failed[] = [
                        'subject' => $subject->subject_code,
                        'reason' => $reason,
                    ];

                    ScheduleGenerationConflict::create([
                        'generation_run_id' => $generationRun->id,
                        'schedule_id' => null,
                        'subject_id' => $subject->id,
                        'instructor_id' => null,
                        'room_id' => null,
                        'conflict_type' => 'NO_INSTRUCTOR',
                        'severity' => 'error',
                        'is_conflict' => true,
                        'message' => "{$subject->subject_code} - {$reason}",
                    ]);

                    continue;
                }

                $scheduledAllSessions = true;

                $hasLectureAndLab =
                    count($sessions) === 2 &&
                    collect($sessions)->pluck('type')->contains('lecture') &&
                    collect($sessions)->pluck('type')->contains('laboratory');

                if ($hasLectureAndLab) {
                    $pairedPlaced = false;
                    $pairedFoundTime = false;
                    $pairedFoundRoom = false;
                    $pairedTimeFailureReasons = [];
                    $pairedRoomFailureReasons = [];

                    foreach ($instructorCandidates as $facultySubject) {
                        $instructor = $facultySubject->instructor;

                        $pairedSlots = $this->findConsecutiveLectureLabSlot(
                            $section,
                            $subject,
                            $instructor->id,
                            $schoolYearId,
                            $semesterId,
                            $sessions
                        );

                        if (!$pairedSlots) {
                            $pairedTimeFailureReasons = array_merge(
                                $pairedTimeFailureReasons,
                                $this->getLastSlotFailureReasons()
                            );
                            continue;
                        }

                        $pairedFoundTime = true;

                        $roomAssignments = [];
                        $canUseRooms = true;

                        foreach ($pairedSlots as $slotData) {
                            $roomType = $this->getRoomTypeForSession($subject, $slotData['type']);

                            $room = $this->findAvailableRoom(
                                $roomType,
                                (int) $section->capacity,
                                $schoolYearId,
                                $semesterId,
                                $slotData['day'],
                                $slotData['start_time'],
                                $slotData['end_time']
                            );

                            if (!$room) {
                                $pairedRoomFailureReasons[] = $this->describeRoomFailure(
                                    $roomType,
                                    (int) $section->capacity,
                                    $schoolYearId,
                                    $semesterId,
                                    $slotData['day'],
                                    $slotData['start_time'],
                                    $slotData['end_time'],
                                    $slotData['type']
                                );
                                $canUseRooms = false;
                                break;
                            }

                            $roomAssignments[] = [
                                'slot' => $slotData,
                                'room' => $room,
                            ];
                        }

                        if (!$canUseRooms) {
                            continue;
                        }

                        $pairedFoundRoom = true;

                        foreach ($roomAssignments as $assignment) {
                            $slotData = $assignment['slot'];
                            $room = $assignment['room'];

                            $schedule = Schedule::create([
                                'generation_run_id' => $generationRun->id,
                                'school_year_id' => $schoolYearId,
                                'semester_id' => $semesterId,
                                'section_id' => $section->id,
                                'course_id' => $section->course_id,
                                'subject_id' => $subject->id,
                                'instructor_id' => $instructor->id,
                                'room_id' => $room->id,
                                'year_level' => $section->year_level,
                                'day' => $slotData['day'],
                                'start_time' => $slotData['start_time'],
                                'end_time' => $slotData['end_time'],
                                'hours' => $slotData['hours'],
                                'session_type' => $slotData['type'],
                                'status' => 'Scheduled',
                            ]);

                            ScheduleGenerationConflict::create([
                                'generation_run_id' => $generationRun->id,
                                'schedule_id' => $schedule->id,
                                'subject_id' => $subject->id,
                                'instructor_id' => $instructor->id,
                                'room_id' => $room->id,
                                'conflict_type' => 'SCHEDULED_SUCCESS',
                                'severity' => 'info',
                                'is_conflict' => false,
                                'message' => "{$subject->subject_code} {$slotData['type']} scheduled on {$slotData['day']} {$slotData['start_time']} - {$slotData['end_time']} in room {$room->room_code}.",
                            ]);

                            $created[] = $schedule->load(['subject', 'instructor', 'room']);
                        }

                        $pairedPlaced = true;
                        break;
                    }

                    if ($pairedPlaced) {
                        continue;
                    }

                    $pairedReason = !$pairedFoundTime
                        ? $this->buildFailureSummary(
                            'No valid consecutive lecture/laboratory time slot found.',
                            $pairedTimeFailureReasons
                        )
                        : (
                            !$pairedFoundRoom
                                ? $this->buildFailureSummary(
                                    'Consecutive lecture/laboratory time was found, but no matching room was available.',
                                    $pairedRoomFailureReasons
                                )
                                : 'Could not place consecutive lecture/laboratory session.'
                        );

                    ScheduleGenerationConflict::create([
                        'generation_run_id' => $generationRun->id,
                        'schedule_id' => null,
                        'subject_id' => $subject->id,
                        'instructor_id' => null,
                        'room_id' => null,
                        'conflict_type' => !$pairedFoundTime
                            ? 'NO_VALID_PAIRED_TIME_SLOT'
                            : (!$pairedFoundRoom ? 'NO_AVAILABLE_PAIRED_ROOM' : 'PAIRED_PLACEMENT_FAILED'),
                        'severity' => 'warning',
                        'is_conflict' => true,
                        'message' => "{$subject->subject_code} - {$pairedReason}",
                    ]);
                }

                foreach ($sessions as $session) {
                    $sessionHours = (float) $session['hours'];
                    $sessionType = $session['type'];

                    $placed = false;
                    $foundTimeSlot = false;
                    $foundRoom = false;
                    $timeFailureReasons = [];
                    $roomFailureReasons = [];

                    foreach ($instructorCandidates as $facultySubject) {
                        $instructor = $facultySubject->instructor;

                        $slot = $this->findBestSlot(
                            $section,
                            $subject,
                            $instructor->id,
                            $schoolYearId,
                            $semesterId,
                            $sessionHours
                        );

                        if (!$slot) {
                            $timeFailureReasons = array_merge(
                                $timeFailureReasons,
                                $this->getLastSlotFailureReasons()
                            );
                            continue;
                        }

                        $foundTimeSlot = true;

                        $roomType = $this->getRoomTypeForSession($subject, $sessionType);

                        $room = $this->findAvailableRoom(
                            $roomType,
                            (int) $section->capacity,
                            $schoolYearId,
                            $semesterId,
                            $slot['day'],
                            $slot['start_time'],
                            $slot['end_time']
                        );

                        if (!$room) {
                            $roomFailureReasons[] = $this->describeRoomFailure(
                                $roomType,
                                (int) $section->capacity,
                                $schoolYearId,
                                $semesterId,
                                $slot['day'],
                                $slot['start_time'],
                                $slot['end_time'],
                                $sessionType
                            );
                            continue;
                        }

                        $foundRoom = true;

                        $schedule = Schedule::create([
                            'generation_run_id' => $generationRun->id,
                            'school_year_id' => $schoolYearId,
                            'semester_id' => $semesterId,
                            'section_id' => $section->id,
                            'course_id' => $section->course_id,
                            'subject_id' => $subject->id,
                            'instructor_id' => $instructor->id,
                            'room_id' => $room->id,
                            'year_level' => $section->year_level,
                            'day' => $slot['day'],
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'hours' => $sessionHours,
                            'status' => 'Scheduled',
                            'session_type' => $sessionType,
                        ]);

                        ScheduleGenerationConflict::create([
                            'generation_run_id' => $generationRun->id,
                            'schedule_id' => $schedule->id,
                            'subject_id' => $subject->id,
                            'instructor_id' => $instructor->id,
                            'room_id' => $room->id,
                            'conflict_type' => 'SCHEDULED_SUCCESS',
                            'severity' => 'info',
                            'is_conflict' => false,
                            'message' => "{$subject->subject_code} {$sessionType} scheduled on {$slot['day']} {$slot['start_time']} - {$slot['end_time']} in room {$room->room_code}.",
                        ]);

                        $created[] = $schedule->load(['subject', 'instructor', 'room']);
                        $placed = true;
                        break;
                    }

                    if (!$placed) {
                        $scheduledAllSessions = false;

                        if (!$foundTimeSlot) {
                            $reason = $this->buildFailureSummary(
                                "No valid time slot found for {$sessionType} ({$sessionHours} hour(s)).",
                                $timeFailureReasons
                            );
                            $conflictType = 'NO_VALID_TIME_SLOT';
                        } elseif (!$foundRoom) {
                            $reason = $this->buildFailureSummary(
                                "No available room found for {$sessionType} ({$sessionHours} hour(s)).",
                                $roomFailureReasons
                            );
                            $conflictType = 'NO_AVAILABLE_ROOM';
                        } else {
                            $reason = "No instructor-room combination could satisfy the constraints for {$sessionType} ({$sessionHours} hour(s)).";
                            $conflictType = 'NO_VALID_INSTRUCTOR_ROOM_COMBINATION';
                        }

                        $failed[] = [
                            'subject' => $subject->subject_code,
                            'reason' => $reason,
                        ];

                        ScheduleGenerationConflict::create([
                            'generation_run_id' => $generationRun->id,
                            'schedule_id' => null,
                            'subject_id' => $subject->id,
                            'instructor_id' => null,
                            'room_id' => null,
                            'conflict_type' => $conflictType,
                            'severity' => 'warning',
                            'is_conflict' => true,
                            'message' => "{$subject->subject_code} - {$reason}",
                        ]);
                    }
                }

                if (!$scheduledAllSessions) {
                    continue;
                }
            }

            
            $totalCreated = count($created);
            $totalFailed = count($failed);

            $status = 'success';
            if ($totalCreated > 0 && $totalFailed > 0) {
                $status = 'partial';
            } elseif ($totalCreated === 0 && $totalFailed > 0) {
                $status = 'failed';
            }

            $generationRun->update([
                'status' => $status,
                'total_created' => $totalCreated,
                'total_failed' => $totalFailed,
            ]);

            return [
                'generation_run_id' => $generationRun->id,
                'section' => $section->load('course'),
                'created' => $created,
                'failed' => $failed,
            ];
        });
    }

    protected function sortCurriculaByDifficulty($curricula, int $schoolYearId, int $semesterId)
    {
        return $curricula->sort(function ($a, $b) use ($schoolYearId, $semesterId) {
            $scoreA = $this->calculateCurriculumDifficulty($a, $schoolYearId, $semesterId);
            $scoreB = $this->calculateCurriculumDifficulty($b, $schoolYearId, $semesterId);

            if ($scoreA === $scoreB) {
                return ($a->sort_order ?? 9999) <=> ($b->sort_order ?? 9999);
            }

            return $scoreB <=> $scoreA;
        })->values();
    }

    protected function calculateCurriculumDifficulty($curriculum, int $schoolYearId, int $semesterId): int
    {
        $subject = $curriculum->subject;

        if (!$subject) {
            return 0;
        }

        $score = 0;

        $lectureHours = (float) ($subject->lecture_hours ?? 0);
        $laboratoryHours = (float) ($subject->laboratory_hours ?? 0);
        $totalHours = $lectureHours + $laboratoryHours;

        $hasLab = $laboratoryHours > 0;
        $hasLecture = $lectureHours > 0;

        $instructorCandidates = FacultySubject::where('subject_id', $subject->id)->get();
        $instructorCount = $instructorCandidates->count();

        $lectureRoomType = $subject->lecture_room_type_required ?? null;
        $labRoomType = $subject->laboratory_room_type_required ?? null;
        $regularRoomType = $subject->room_type_required ?? null;

        $availabilityWindows = $this->countInstructorAvailabilityWindows(
            $subject->id,
            $schoolYearId,
            $semesterId
        );

        // 1. lab subjects first
        if ($hasLab) {
            $score += 100;
        }

        // 2. lecture + lab pair is harder
        if ($hasLecture && $hasLab) {
            $score += 60;
        }

        // 3. longer subjects first
        $score += (int) round($totalHours * 20);

        // 4. fewer instructors = harder
        if ($instructorCount === 0) {
            $score += 200;
        } elseif ($instructorCount === 1) {
            $score += 100;
        } elseif ($instructorCount === 2) {
            $score += 60;
        } elseif ($instructorCount === 3) {
            $score += 30;
        }

        // 5. stricter room requirements
        if (!empty($labRoomType)) {
            $score += 70;
        }

        if (!empty($lectureRoomType)) {
            $score += 40;
        }

        if (!empty($regularRoomType)) {
            $score += 25;
        }

        // 6. fewer availability windows = harder
        if ($availabilityWindows <= 2) {
            $score += 100;
        } elseif ($availabilityWindows <= 5) {
            $score += 70;
        } elseif ($availabilityWindows <= 10) {
            $score += 40;
        }

        return $score;
    }

    protected function countInstructorAvailabilityWindows(
        int $subjectId,
        int $schoolYearId,
        int $semesterId
    ): int {
        $instructorIds = FacultySubject::where('subject_id', $subjectId)
            ->pluck('instructor_id')
            ->unique()
            ->values();

        if ($instructorIds->isEmpty()) {
            return 0;
        }

        return FacultyAvailability::whereIn('instructor_id', $instructorIds)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('status', 'Available')
            ->count();
    }

    protected function buildSessions($subject): array
    {
        $sessions = [];

        $lectureHours = (float) ($subject->lecture_hours ?? 0);
        $laboratoryHours = (float) ($subject->laboratory_hours ?? 0);

        // Add lecture session
        if ($lectureHours > 0) {
            $sessions[] = [
                'type' => 'lecture',
                'hours' => $lectureHours,
            ];
        }

        // Add laboratory session
        if ($laboratoryHours > 0) {
            $sessions[] = [
                'type' => 'laboratory',
                'hours' => $laboratoryHours,
            ];
        }

        // If already valid, return
        if (!empty($sessions)) {
            return $sessions;
        }

        // Fallback: no lecture/lab defined → treat as lecture
        $total = (float) ($subject->total_hours_per_week ?? 0);

        if ($total <= 0) {
            return [];
        }

        return [
            [
                'type' => 'lecture',
                'hours' => $total,
            ]
        ];
    }

    protected function findBestSlot(
        Section $section,
        $subject,
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        float $hoursNeeded
    ): ?array {
        $this->resetSlotFailureReasons();
        $candidates = [];

        foreach ($this->days as $day) {
            foreach (range(0, count($this->timeSlots) - 1) as $startIndex) {
                $slot = $this->buildTimeRangeFromSlots($startIndex, $hoursNeeded);

                if (!$slot) {
                    $this->rememberSlotFailure('The requested duration does not fit within school hours.');
                    continue;
                }

                $startTime = $slot['start_time'];
                $endTime = $slot['end_time'];

                $invalidReason = $this->getCandidateSlotInvalidReason(
                    $section,
                    $subject,
                    $instructorId,
                    $schoolYearId,
                    $semesterId,
                    $day,
                    $startTime,
                    $endTime,
                    false
                );

                if ($invalidReason !== null) {
                    $this->rememberSlotFailure($invalidReason);
                    continue;
                }

                if (!$this->respectsSubjectDailyLimit(
                    $section->id,
                    $subject->id,
                    $schoolYearId,
                    $semesterId,
                    $day,
                    $hoursNeeded,
                    (float) ($subject->max_hours_per_day ?? 24)
                )) {
                    $this->rememberSlotFailure('Subject exceeds its maximum allowed hours per day.');
                    continue;
                }

                if (!$this->respectsInstructorLoad(
                    $instructorId,
                    $hoursNeeded,
                    $schoolYearId,
                    $semesterId
                )) {
                    $this->rememberSlotFailure('Instructor load limit blocks this slot.');
                    continue;
                }

                $candidates[] = [
                    'day' => $day,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'score' => $this->scoreSlot(
                        $section,
                        $subject,
                        $instructorId,
                        $schoolYearId,
                        $semesterId,
                        $day,
                        $startTime,
                        $endTime
                    ),
                ];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                if ($a['day'] === $b['day']) {
                    return strcmp($a['start_time'], $b['start_time']);
                }

                return array_search($a['day'], $this->days, true) <=> array_search($b['day'], $this->days, true);
            }

            return $b['score'] <=> $a['score'];
        });

        return [
            'day' => $candidates[0]['day'],
            'start_time' => $candidates[0]['start_time'],
            'end_time' => $candidates[0]['end_time'],
        ];
    }

    protected function scoreSlot(
        Section $section,
        $subject,
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime
    ): int {
        $score = 0;

        $start = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);

        // Prefer mid-morning / early afternoon over very early or very late
        if ($start->format('H:i') >= '08:00' && $start->format('H:i') <= '15:00') {
            $score += 20;
        }

        if ($start->format('H:i') < '08:00') {
            $score -= 10;
        }

        if ($end->format('H:i') > '17:30') {
            $score -= 12;
        }

        // Prefer days that already have schedules for the section,
        // so the schedule stays compact instead of being too scattered
        $sectionDaySchedules = Schedule::where('section_id', $section->id)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('day', $day)
            ->orderBy('start_time')
            ->get();

        if ($sectionDaySchedules->isNotEmpty()) {
            $score += 18;

            $gapPenalty = $this->calculateGapPenalty($sectionDaySchedules, $startTime, $endTime);
            $score -= $gapPenalty;
        } else {
            $score += 4;
        }

        // Prefer days where instructor already has classes,
        // but avoid creating ugly large gaps
        $instructorDaySchedules = Schedule::where('instructor_id', $instructorId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('day', $day)
            ->orderBy('start_time')
            ->get();

        if ($instructorDaySchedules->isNotEmpty()) {
            $score += 12;

            $gapPenalty = $this->calculateGapPenalty($instructorDaySchedules, $startTime, $endTime);
            $score -= (int) round($gapPenalty * 0.7);
        } else {
            $score += 3;
        }

        // Soft load-balancing rule:
        // Do not block an instructor just because they already have classes.
        // Instead, lower the score so the scheduler prefers a lighter-load instructor
        // when another assigned instructor is available.
        $durationHours = $start->diffInMinutes($end) / 60;
        $score -= $this->calculateInstructorLoadPenalty(
            $instructorId,
            $durationHours,
            $schoolYearId,
            $semesterId
        );

        // Slightly prefer earlier weekdays
        $dayWeights = [
            'Monday' => 6,
            'Tuesday' => 5,
            'Wednesday' => 4,
            'Thursday' => 3,
            'Friday' => 2,
            'Saturday' => 1,
        ];

        $score += $dayWeights[$day] ?? 0;

        return $score;
    }

    protected function calculateGapPenalty($existingSchedules, string $newStartTime, string $newEndTime): int
    {
        if ($existingSchedules->isEmpty()) {
            return 0;
        }

        $newStart = Carbon::createFromFormat('H:i:s', $newStartTime);
        $newEnd = Carbon::createFromFormat('H:i:s', $newEndTime);

        $smallestGap = null;

        foreach ($existingSchedules as $schedule) {
            $existingStart = Carbon::createFromFormat('H:i:s', $schedule->start_time);
            $existingEnd = Carbon::createFromFormat('H:i:s', $schedule->end_time);

            // New slot before existing
            if ($newEnd <= $existingStart) {
                $gap = $newEnd->diffInMinutes($existingStart);
            }
            // New slot after existing
            elseif ($newStart >= $existingEnd) {
                $gap = $existingEnd->diffInMinutes($newStart);
            }
            // Overlap/touching should not happen because validation already blocks conflicts,
            // but if very close, treat as best possible gap
            else {
                $gap = 0;
            }

            if ($smallestGap === null || $gap < $smallestGap) {
                $smallestGap = $gap;
            }
        }

        if ($smallestGap === null) {
            return 0;
        }

        // Penalty only when gaps become too large
        if ($smallestGap <= 30) {
            return 0;
        }

        if ($smallestGap <= 60) {
            return 5;
        }

        if ($smallestGap <= 120) {
            return 15;
        }

        return 30;
    }

    protected function findConsecutiveLectureLabSlot(
        Section $section,
        $subject,
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        array $sessions
    ): ?array {
        $this->resetSlotFailureReasons();

        $lecture = collect($sessions)->firstWhere('type', 'lecture');
        $lab = collect($sessions)->firstWhere('type', 'laboratory');

        if (!$lecture || !$lab) {
            $this->rememberSlotFailure('Subject does not have both lecture and laboratory sessions.');
            return null;
        }

        foreach ($this->days as $day) {
            foreach (range(0, count($this->timeSlots) - 1) as $startIndex) {
                $lectureSlot = $this->buildTimeRangeFromSlots($startIndex, (float) $lecture['hours']);
                if (!$lectureSlot) {
                    $this->rememberSlotFailure('Lecture duration does not fit within school hours.');
                    continue;
                }

                $labStartIndex = $this->findSlotIndexByTime(substr($lectureSlot['end_time'], 0, 5));
                if ($labStartIndex === null) {
                    $this->rememberSlotFailure('Laboratory cannot start immediately after the lecture slot.');
                    continue;
                }

                $labSlot = $this->buildTimeRangeFromSlots($labStartIndex, (float) $lab['hours']);
                if (!$labSlot) {
                    $this->rememberSlotFailure('Laboratory duration does not fit after the lecture within school hours.');
                    continue;
                }

                $lectureInvalidReason = $this->getCandidateSlotInvalidReason(
                    $section,
                    $subject,
                    $instructorId,
                    $schoolYearId,
                    $semesterId,
                    $day,
                    $lectureSlot['start_time'],
                    $lectureSlot['end_time'],
                    true
                );

                if ($lectureInvalidReason !== null) {
                    $this->rememberSlotFailure('Lecture blocked: ' . $lectureInvalidReason);
                    continue;
                }

                $labInvalidReason = $this->getCandidateSlotInvalidReason(
                    $section,
                    $subject,
                    $instructorId,
                    $schoolYearId,
                    $semesterId,
                    $day,
                    $labSlot['start_time'],
                    $labSlot['end_time'],
                    true
                );

                if ($labInvalidReason !== null) {
                    $this->rememberSlotFailure('Laboratory blocked: ' . $labInvalidReason);
                    continue;
                }

                $totalHoursForDay = (float) $lecture['hours'] + (float) $lab['hours'];

                if (!$this->respectsSubjectDailyLimit(
                    $section->id,
                    $subject->id,
                    $schoolYearId,
                    $semesterId,
                    $day,
                    $totalHoursForDay,
                    (float) ($subject->max_hours_per_day ?? 24)
                )) {
                    $this->rememberSlotFailure('Subject exceeds its maximum allowed hours per day.');
                    continue;
                }

                if (!$this->respectsInstructorLoad(
                    $instructorId,
                    $totalHoursForDay,
                    $schoolYearId,
                    $semesterId
                )) {
                    $this->rememberSlotFailure('Instructor load limit blocks this lecture/laboratory pair.');
                    continue;
                }

                return [
                    [
                        'type' => 'lecture',
                        'hours' => (float) $lecture['hours'],
                        'day' => $day,
                        'start_time' => $lectureSlot['start_time'],
                        'end_time' => $lectureSlot['end_time'],
                    ],
                    [
                        'type' => 'laboratory',
                        'hours' => (float) $lab['hours'],
                        'day' => $day,
                        'start_time' => $labSlot['start_time'],
                        'end_time' => $labSlot['end_time'],
                    ],
                ];
            }
        }

        return null;
    }

    protected function getRoomTypeForSession($subject, string $sessionType): ?string
    {
        if ($sessionType === 'lecture') {

            if (!empty($subject->lecture_room_type_required)) {
                return $subject->lecture_room_type_required;
            }

            if (!empty($subject->room_type_required)) {
                return $subject->room_type_required;
            }

            return $this->detectDefaultRoomType($subject, 'lecture');
        }

        if ($sessionType === 'laboratory') {

            if (!empty($subject->laboratory_room_type_required)) {
                return $subject->laboratory_room_type_required;
            }

            if (!empty($subject->room_type_required)) {
                return $subject->room_type_required;
            }

            return $this->detectDefaultRoomType($subject, 'laboratory');
        }

        return $this->detectDefaultRoomType($subject, 'lecture');
    }

    protected function detectDefaultRoomType($subject, string $sessionType): string
    {
        if ($sessionType === 'laboratory') {

            if (!empty($subject->laboratory_room_type_required)) {
                return $subject->laboratory_room_type_required;
            }

            if (!empty($subject->room_type_required)) {
                return $subject->room_type_required;
            }

            return 'computer_lab'; // safe lab fallback
        }

        // Default everything else to lecture
        if (!empty($subject->lecture_room_type_required)) {
            return $subject->lecture_room_type_required;
        }

        if (!empty($subject->room_type_required)) {
            return $subject->room_type_required;
        }

        return 'lecture';
    }

    protected function isCandidateSlotValid(
        Section $section,
        $subject,
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime,
        bool $ignoreSameSubjectGap = false
    ): bool {
        return $this->getCandidateSlotInvalidReason(
            $section,
            $subject,
            $instructorId,
            $schoolYearId,
            $semesterId,
            $day,
            $startTime,
            $endTime,
            $ignoreSameSubjectGap
        ) === null;
    }

    protected function getCandidateSlotInvalidReason(
        Section $section,
        $subject,
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime,
        bool $ignoreSameSubjectGap = false
    ): ?string {
        if (!$this->isInstructorAvailable(
            $instructorId,
            $schoolYearId,
            $semesterId,
            $day,
            $startTime,
            $endTime
        )) {
            return 'Instructor is unavailable during this time.';
        }

        if ($this->hasSectionConflict(
            $section->id,
            $schoolYearId,
            $semesterId,
            $day,
            $startTime,
            $endTime,
            $subject->id,
            $ignoreSameSubjectGap
        )) {
            return 'Section already has a class within this time or within the 30-minute minimum interval.';
        }

        if ($this->hasInstructorConflict(
            $instructorId,
            $schoolYearId,
            $semesterId,
            $day,
            $startTime,
            $endTime,
            $subject->id,
            $ignoreSameSubjectGap
        )) {
            return 'Instructor already has a class within this time or within the 30-minute minimum interval.';
        }

        if (!$this->respectsMaximumGapForSection(
            $section->id,
            $schoolYearId,
            $semesterId,
            $day,
            $startTime,
            $endTime,
            $subject->id,
            $ignoreSameSubjectGap
        )) {
            return 'Section maximum 1-hour gap rule blocks this slot.';
        }

        if (!$this->respectsMaximumGapForInstructor(
            $instructorId,
            $schoolYearId,
            $semesterId,
            $day,
            $startTime,
            $endTime,
            $subject->id,
            $ignoreSameSubjectGap
        )) {
            return 'Instructor maximum 1-hour gap rule blocks this slot.';
        }

        if (!$this->respectsMaxSubjectsPerDay(
            $section->id,
            $schoolYearId,
            $semesterId,
            $day,
            $subject->id
        )) {
            return 'Section already has the maximum of 2 different subjects on this day.';
        }

        return null;
    }

    protected function findSlotIndexByTime(string $time): ?int
    {
        foreach ($this->timeSlots as $index => $slot) {
            if ($slot[0] === $time) {
                return $index;
            }
        }

        return null;
    }

    protected function buildTimeRangeFromSlots(int $startIndex, float $hoursNeeded): ?array
    {
        $minutesNeeded = (int) round($hoursNeeded * 60);
        $start = $this->timeSlots[$startIndex][0] ?? null;

        if (!$start) {
            return null;
        }

        $startCarbon = Carbon::createFromFormat('H:i', $start);
        $endCarbon = (clone $startCarbon)->addMinutes($minutesNeeded);

        if ($endCarbon->format('H:i') > '20:30') {
            return null;
        }

        return [
            'day' => null,
            'start_time' => $startCarbon->format('H:i:s'),
            'end_time' => $endCarbon->format('H:i:s'),
        ];
    }

    protected function hasTimeConflictWithGap(
        $query,
        string $day,
        string $startTime,
        string $endTime,
        int $gapMinutes = 30,
        ?int $subjectId = null,
        bool $ignoreSameSubjectGap = false
    ): bool {
        $newStart = Carbon::createFromFormat('H:i:s', $startTime);
        $newEnd = Carbon::createFromFormat('H:i:s', $endTime);

        return $query->where('day', $day)
            ->get()
            ->contains(function ($row) use ($newStart, $newEnd, $gapMinutes, $subjectId, $ignoreSameSubjectGap) {
                $existingStart = Carbon::createFromFormat('H:i:s', $row->start_time);
                $existingEnd = Carbon::createFromFormat('H:i:s', $row->end_time);

                $effectiveGap = $gapMinutes;

                if (
                    $ignoreSameSubjectGap &&
                    $subjectId !== null &&
                    (int) $row->subject_id === (int) $subjectId
                ) {
                    $effectiveGap = 0;
                }

                $blockedStart = (clone $existingStart)->subMinutes($effectiveGap);
                $blockedEnd = (clone $existingEnd)->addMinutes($effectiveGap);

                return $newStart < $blockedEnd && $newEnd > $blockedStart;
            });
    }

    protected function hasSectionConflict(
        int $sectionId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime,
        ?int $subjectId = null,
        bool $ignoreSameSubjectGap = false
    ): bool {
        $query = Schedule::where('section_id', $sectionId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId);

        return $this->hasTimeConflictWithGap(
            $query,
            $day,
            $startTime,
            $endTime,
            $this->minimumGapMinutes,
            $subjectId,
            $ignoreSameSubjectGap
        );
    }

    protected function hasInstructorConflict(
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime,
        ?int $subjectId = null,
        bool $ignoreSameSubjectGap = false
    ): bool {
        $query = Schedule::where('instructor_id', $instructorId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId);

        return $this->hasTimeConflictWithGap(
            $query,
            $day,
            $startTime,
            $endTime,
            $this->minimumGapMinutes,
            $subjectId,
            $ignoreSameSubjectGap
        );
    }

    protected function respectsMaximumGapForSection(
        int $sectionId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime,
        ?int $subjectId = null,
        bool $ignoreSameSubjectGap = false
    ): bool {
        $existingSchedules = Schedule::where('section_id', $sectionId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('day', $day)
            ->orderBy('start_time')
            ->get();

        if ($existingSchedules->isEmpty()) {
            return true;
        }

        $newStart = Carbon::createFromFormat('H:i:s', $startTime);
        $newEnd = Carbon::createFromFormat('H:i:s', $endTime);

        foreach ($existingSchedules as $existing) {
            if (
                $ignoreSameSubjectGap &&
                $subjectId !== null &&
                (int) $existing->subject_id === (int) $subjectId
            ) {
                continue;
            }

            $existingStart = Carbon::createFromFormat('H:i:s', $existing->start_time);
            $existingEnd = Carbon::createFromFormat('H:i:s', $existing->end_time);

            if ($newStart->greaterThanOrEqualTo($existingEnd)) {
                $gap = $existingEnd->diffInMinutes($newStart);
                if ($gap > $this->maximumGapMinutes) {
                    return false;
                }
            }

            if ($newEnd->lessThanOrEqualTo($existingStart)) {
                $gap = $newEnd->diffInMinutes($existingStart);
                if ($gap > $this->maximumGapMinutes) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function respectsMaximumGapForInstructor(
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime,
        ?int $subjectId = null,
        bool $ignoreSameSubjectGap = false
    ): bool {
        $existingSchedules = Schedule::where('instructor_id', $instructorId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('day', $day)
            ->orderBy('start_time')
            ->get();

        if ($existingSchedules->isEmpty()) {
            return true;
        }

        $newStart = Carbon::createFromFormat('H:i:s', $startTime);
        $newEnd = Carbon::createFromFormat('H:i:s', $endTime);

        foreach ($existingSchedules as $existing) {
            if (
                $ignoreSameSubjectGap &&
                $subjectId !== null &&
                (int) $existing->subject_id === (int) $subjectId
            ) {
                continue;
            }

            $existingStart = Carbon::createFromFormat('H:i:s', $existing->start_time);
            $existingEnd = Carbon::createFromFormat('H:i:s', $existing->end_time);

            if ($newStart->greaterThanOrEqualTo($existingEnd)) {
                $gap = $existingEnd->diffInMinutes($newStart);
                if ($gap > $this->maximumGapMinutes) {
                    return false;
                }
            }

            if ($newEnd->lessThanOrEqualTo($existingStart)) {
                $gap = $newEnd->diffInMinutes($existingStart);
                if ($gap > $this->maximumGapMinutes) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function respectsMaxSubjectsPerDay(
        int $sectionId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        int $subjectId
    ): bool {
        $subjectIds = Schedule::where('section_id', $sectionId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('day', $day)
            ->pluck('subject_id')
            ->unique()
            ->values();

        if ($subjectIds->contains($subjectId)) {
            return true;
        }

        return $subjectIds->count() < $this->maxSubjectsPerDay;
    }

    protected function findAvailableRoom(
        ?string $roomType,
        int $capacity,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime
    ): ?Room {
        $rooms = Room::query()
            ->when($roomType, function ($query) use ($roomType) {
                $query->where('room_type', $roomType);
            })
            ->whereIn('status', ['Active', 'active'])
            ->orderByRaw('CASE WHEN capacity >= ? THEN 0 ELSE 1 END', [$capacity])
            ->orderByRaw('ABS(capacity - ?)', [$capacity])
            ->orderByDesc('capacity')
            ->get();

        foreach ($rooms as $room) {
            $hasConflict = Schedule::where('school_year_id', $schoolYearId)
                ->where('semester_id', $semesterId)
                ->where('room_id', $room->id)
                ->where('day', $day)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->exists();

            if (!$hasConflict) {
                return $room;
            }
        }

        return null;
    }

    protected function isInstructorAvailable(
        int $instructorId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime
    ): bool {
        $instructor = Instructor::findOrFail($instructorId);

        $rules = FacultyAvailability::where('instructor_id', $instructorId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('day', $day)
            ->get();

        if ($instructor->employment_type === 'part_time') {
            return $rules->contains(function ($row) use ($startTime, $endTime) {
                return $row->status === 'Available'
                    && $row->start_time <= $startTime
                    && $row->end_time >= $endTime;
            });
        }

        if ($day === 'Sunday' || $startTime < '07:00:00' || $endTime > '20:30:00') {
            return false;
        }

        $isBlocked = $rules->contains(function ($row) use ($startTime, $endTime) {
            return $row->status === 'Unavailable'
                && $row->start_time < $endTime
                && $row->end_time > $startTime;
        });

        return !$isBlocked;
    }

    protected function respectsSubjectDailyLimit(
        int $sectionId,
        int $subjectId,
        int $schoolYearId,
        int $semesterId,
        string $day,
        float $newHours,
        float $maxHoursPerDay
    ): bool {
        $current = (float) Schedule::where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->where('day', $day)
            ->sum('hours');

        return ($current + $newHours) <= $maxHoursPerDay;
    }

    protected function getInstructorCurrentLoad(
        int $instructorId,
        int $schoolYearId,
        int $semesterId
    ): float {
        return (float) Schedule::where('instructor_id', $instructorId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->sum('hours');
    }

    protected function sortInstructorCandidatesByLoadBalance(
        $instructorCandidates,
        int $schoolYearId,
        int $semesterId
    ) {
        return $instructorCandidates->sortByDesc(function ($facultySubject) use ($schoolYearId, $semesterId) {
            if (!$facultySubject->instructor) {
                return -9999;
            }

            $currentLoad = $this->getInstructorCurrentLoad(
                $facultySubject->instructor->id,
                $schoolYearId,
                $semesterId
            );

            $priorityScore = (int) ($facultySubject->priority_score ?? 0);
            $primaryBonus = (int) ($facultySubject->is_primary ?? 0) === 1 ? 30 : 0;

            // Higher score = tried first.
            // priority_score and is_primary still matter,
            // but instructors with lighter loads are preferred.
            return $priorityScore + $primaryBonus - ($currentLoad * 3);
        })->values();
    }

    protected function calculateInstructorLoadPenalty(
        int $instructorId,
        float $newHours,
        int $schoolYearId,
        int $semesterId
    ): int {
        $currentLoad = $this->getInstructorCurrentLoad(
            $instructorId,
            $schoolYearId,
            $semesterId
        );

        $totalLoad = $currentLoad + $newHours;

        // These are soft limits only.
        // They do not block scheduling; they only affect which valid option is preferred.
        $idealLoad = 12;
        $softMaxLoad = 18;
        $heavyLoad = 24;

        // Encourage using instructors with very light load.
        if ($totalLoad <= $idealLoad) {
            return -10; // bonus because score subtracts this value
        }

        // Normal load range.
        if ($totalLoad <= $softMaxLoad) {
            return 0;
        }

        // Slight overload: still allowed, but less preferred.
        if ($totalLoad <= $heavyLoad) {
            return 15;
        }

        // Heavy overload: still allowed, but strongly discouraged.
        return 40;
    }

    protected function respectsInstructorLoad(
        int $instructorId,
        float $newHours,
        int $schoolYearId,
        int $semesterId
    ): bool {
        // max_hours_per_week was removed from the instructors table.
        // Instructor load is now handled as a soft scoring rule in scoreSlot(),
        // not as a hard blocker.
        return true;
    }

    protected function resetSlotFailureReasons(): void
    {
        $this->lastSlotFailureReasons = [];
    }

    protected function rememberSlotFailure(string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            return;
        }

        $this->lastSlotFailureReasons[] = $reason;
    }

    protected function getLastSlotFailureReasons(): array
    {
        return $this->lastSlotFailureReasons;
    }

    protected function buildFailureSummary(string $defaultReason, array $reasons): string
    {
        $reasons = array_values(array_filter(array_map('trim', $reasons)));

        if (empty($reasons)) {
            return $defaultReason;
        }

        $counts = array_count_values($reasons);
        arsort($counts);

        $topReasons = array_slice(array_keys($counts), 0, 3);

        return $defaultReason . ' Main reason(s): ' . implode(' ', $topReasons);
    }

    protected function describeRoomFailure(
        ?string $roomType,
        int $capacity,
        int $schoolYearId,
        int $semesterId,
        string $day,
        string $startTime,
        string $endTime,
        string $sessionType
    ): string {
        $roomQuery = Room::query()
            ->whereIn('status', ['Active', 'active']);

        if ($roomType) {
            $roomQuery->where('room_type', $roomType);
        }

        $rooms = $roomQuery->get();
        $roomTypeLabel = $roomType ?: 'any room type';

        if ($rooms->isEmpty()) {
            return "No active room found with type {$roomTypeLabel} for {$sessionType}.";
        }

        $roomsWithEnoughCapacity = $rooms->filter(function ($room) use ($capacity) {
            return (int) ($room->capacity ?? 0) >= $capacity;
        });

        if ($roomsWithEnoughCapacity->isEmpty()) {
            return "No room with type {$roomTypeLabel} has enough capacity for {$capacity} students.";
        }

        $availableRoomExists = $rooms->contains(function ($room) use ($schoolYearId, $semesterId, $day, $startTime, $endTime) {
            return !Schedule::where('school_year_id', $schoolYearId)
                ->where('semester_id', $semesterId)
                ->where('room_id', $room->id)
                ->where('day', $day)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->exists();
        });

        if (!$availableRoomExists) {
            return "All rooms with type {$roomTypeLabel} are already occupied during this time.";
        }

        return "No available room matched type {$roomTypeLabel} for {$sessionType}.";
    }

    public function generateForSections(
        array $sectionIds,
        int $schoolYearId,
        int $semesterId,
        bool $replaceExisting = false
    ): array {
        $sortedSectionIds = $this->sortSectionIdsByDifficulty(
            $sectionIds,
            $schoolYearId,
            $semesterId
        );

        $results = [];

        foreach ($sortedSectionIds as $sectionId) {
            $results[] = $this->generateForSection(
                (int) $sectionId,
                $schoolYearId,
                $semesterId,
                $replaceExisting
            );
        }

        return $results;
    }

    public function sortSectionIdsByDifficulty(
        array $sectionIds,
        int $schoolYearId,
        int $semesterId
    ): array {
        $sections = Section::with('course')
            ->whereIn('id', $sectionIds)
            ->get();

        return $this->sortSectionsByDifficulty(
            $sections,
            $schoolYearId,
            $semesterId
        )
        ->pluck('id')
        ->values()
        ->all();
    }

    public function sortSectionsByDifficulty(
        $sections,
        int $schoolYearId,
        int $semesterId
    ) {
        return $sections->sort(function ($a, $b) use ($schoolYearId, $semesterId) {
            $scoreA = $this->calculateSectionDifficulty($a, $schoolYearId, $semesterId);
            $scoreB = $this->calculateSectionDifficulty($b, $schoolYearId, $semesterId);

            if ($scoreA === $scoreB) {
                $courseA = $a->course->course_code ?? $a->course->course_name ?? '';
                $courseB = $b->course->course_code ?? $b->course->course_name ?? '';

                if ($courseA === $courseB) {
                    return ($a->year_level ?? '') <=> ($b->year_level ?? '');
                }

                return $courseA <=> $courseB;
            }

            return $scoreB <=> $scoreA;
        })->values();
    }

    protected function calculateSectionDifficulty(
        Section $section,
        int $schoolYearId,
        int $semesterId
    ): int {
        $curricula = Curriculum::with('subject')
            ->where('course_id', $section->course_id)
            ->where('year_level', $section->year_level)
            ->where('semester_id', $semesterId)
            ->where('active', true)
            ->get();

        if ($curricula->isEmpty()) {
            return -9999;
        }

        $score = 0;

        $totalSubjects = 0;
        $labSubjects = 0;
        $lectureLabSubjects = 0;
        $specialRoomSubjects = 0;
        $subjectsWithOneInstructor = 0;
        $subjectsWithNoInstructor = 0;

        foreach ($curricula as $curriculum) {
            $subject = $curriculum->subject;

            if (!$subject) {
                continue;
            }

            $totalSubjects++;

            $lectureHours = (float) ($subject->lecture_hours ?? 0);
            $laboratoryHours = (float) ($subject->laboratory_hours ?? 0);

            if ($laboratoryHours > 0) {
                $labSubjects++;
            }

            if ($lectureHours > 0 && $laboratoryHours > 0) {
                $lectureLabSubjects++;
            }

            if (
                !empty($subject->lecture_room_type_required) ||
                !empty($subject->laboratory_room_type_required) ||
                !empty($subject->room_type_required)
            ) {
                $specialRoomSubjects++;
            }

            $instructorCount = FacultySubject::where('subject_id', $subject->id)->count();

            if ($instructorCount === 0) {
                $subjectsWithNoInstructor++;
            } elseif ($instructorCount === 1) {
                $subjectsWithOneInstructor++;
            }

            $score += $this->calculateCurriculumDifficulty(
                $curriculum,
                $schoolYearId,
                $semesterId
            );
        }

        $score += $totalSubjects * 10;
        $score += $labSubjects * 120;
        $score += $lectureLabSubjects * 90;
        $score += $specialRoomSubjects * 70;
        $score += $subjectsWithOneInstructor * 80;
        $score += $subjectsWithNoInstructor * 150;

        return $score;
    }
}
