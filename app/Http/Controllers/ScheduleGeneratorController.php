<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Semester;
use App\Services\AutoScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ScheduleGenerationRun;
use App\Models\ScheduleGenerationConflict;

class ScheduleGeneratorController extends Controller
{
    public function home()
    {
        $courses = \App\Models\Course::orderBy('course_code')->get();
        $sections = Section::with('course')->orderBy('section_code')->get();
        $schoolYears = SchoolYear::orderByDesc('id')->get();
        $semesters = Semester::orderBy('semester_order')->get();

        return view('schedules.home', compact(
            'courses',
            'sections',
            'schoolYears',
            'semesters'
        ));
    }

    public function generate(Request $request, AutoScheduleService $service)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'school_year_id' => 'required|exists:school_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'replace_existing' => 'nullable|boolean',
        ]);

        $result = $service->generateForSection(
            (int) $validated['section_id'],
            (int) $validated['school_year_id'],
            (int) $validated['semester_id'],
            (bool) ($validated['replace_existing'] ?? false)
        );

        return redirect()->route('schedules.preview', [
            'section' => $validated['section_id'],
            'school_year' => $validated['school_year_id'],
            'semester' => $validated['semester_id'],
        ])->with('generation_result', $result);
    }

    public function generateMultiple(Request $request, AutoScheduleService $service)
    {
        $validated = $request->validate([
            'section_ids' => 'required|array|min:1',
            'section_ids.*' => 'required|exists:sections,id',
            'school_year_id' => 'required|exists:school_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'replace_existing' => 'nullable|boolean',
        ]);

        $results = $service->generateForSections(
            array_map('intval', $validated['section_ids']),
            (int) $validated['school_year_id'],
            (int) $validated['semester_id'],
            (bool) ($validated['replace_existing'] ?? false)
        );

        return redirect()->route('schedules.summary')
            ->with('success', 'Schedules generated using hard-first section ordering.')
            ->with('generation_results', $results);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|exists:sections,id',
            'school_year' => 'required|exists:school_years,id',
            'semester' => 'required|exists:semesters,id',
        ]);

        $section = Section::with('course')->findOrFail($validated['section']);
        $schoolYear = SchoolYear::findOrFail($validated['school_year']);
        $semester = Semester::findOrFail($validated['semester']);

        $schedules = Schedule::with(['subject', 'instructor', 'room'])
            ->where('section_id', $validated['section'])
            ->where('school_year_id', $validated['school_year'])
            ->where('semester_id', $validated['semester'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        $generationRun = ScheduleGenerationRun::with('conflicts.subject')
            ->where('section_id', $validated['section'])
            ->where('school_year_id', $validated['school_year'])
            ->where('semester_id', $validated['semester'])
            ->latest()
            ->first();

        return view('schedules.preview', compact(
            'section',
            'schoolYear',
            'semester',
            'schedules',
            'generationRun'
        ));
    }

    public function summary()
    {
        $generatedSchedules = Schedule::query()
            ->select(
                'section_id',
                'school_year_id',
                'semester_id',
                DB::raw('COUNT(*) as total_subject_schedules')
            )
            ->with([
                'section.course:id,course_code,course_name',
                'schoolYear:id,school_year',
                'semester:id,semester_name,semester_order',
            ])
            ->groupBy('section_id', 'school_year_id', 'semester_id')
            ->orderByDesc('school_year_id')
            ->orderBy('semester_id')
            ->orderBy('section_id')
            ->get();

        return view('schedules.summary', compact('generatedSchedules'));
    }

    public function clear(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'school_year_id' => 'required|exists:school_years,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        Schedule::where('section_id', $validated['section_id'])
            ->where('school_year_id', $validated['school_year_id'])
            ->where('semester_id', $validated['semester_id'])
            ->delete();

        return redirect()->route('schedules.summary')
            ->with('success', 'Schedule cleared successfully.');
    }

    public function history()
    {
        $runs = ScheduleGenerationRun::with([
            'section.course:id,course_code,course_name',
            'schoolYear:id,school_year',
            'semester:id,semester_name,semester_order',
        ])
        ->orderByDesc('id')
        ->get();

        return view('schedules.history', compact('runs'));
    }

    public function conflictLogs($id)
    {
        $run = ScheduleGenerationRun::with([
            'section.course:id,course_code,course_name',
            'schoolYear:id,school_year',
            'semester:id,semester_name,semester_order',
            'conflicts.subject:id,subject_code,subject_name',
            'conflicts.instructor:id,instructor_name',
            'conflicts.room:id,room_code,room_name',
            'conflicts.schedule:id,day,start_time,end_time',
        ])->findOrFail($id);

        $conflicts = $run->conflicts()->orderBy('id')->get();

        return view('schedules.conflict-logs', compact('run', 'conflicts'));
    }
}