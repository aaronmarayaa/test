<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Preview</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 18px;
            color: #222;
            background: #f6f8fb;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0 0 6px 0;
            font-size: 26px;
        }

        .meta {
            line-height: 1.5;
            font-size: 14px;
        }

        .btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .btn-blue {
            background: #2563eb;
            color: #fff;
        }

        .btn-gray {
            background: #e5e7eb;
            color: #111827;
        }

        .run-box {
            background: #fff;
            border: 1px solid #d8dee7;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 14px;
        }

        .run-stats {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 10px;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background: #e8f7ec;
            color: #1f7a3f;
        }

        .badge-warning {
            background: #fff4db;
            color: #9a6700;
        }

        .badge-danger {
            background: #fdeaea;
            color: #b42318;
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 14px;
            align-items: start;
        }

        .paper {
            background: white;
            border: 1px solid #bbb;
            padding: 10px;
            overflow-x: auto;
        }

        .side-panel {
            background: white;
            border: 1px solid #d8dee7;
            border-radius: 10px;
            padding: 14px;
        }

        .side-panel h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .conflict-list,
        .success-list {
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
        }

        .conflict-list li {
            margin-bottom: 8px;
            color: #b42318;
        }

        .success-list li {
            margin-bottom: 8px;
            color: #1f7a3f;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: 70px repeat(6, 1fr);
            min-width: 980px;
        }

        .head {
            border: 1px solid #777;
            background: #f2f2f2;
            text-align: center;
            font-weight: bold;
            padding: 5px;
            font-size: 11px;
        }

        .time-col {
            position: relative;
            height: 780px;
            border-left: 1px solid #777;
            border-right: 1px solid #777;
            border-bottom: 1px solid #777;
            background:
                repeating-linear-gradient(
                    to bottom,
                    #fff 0px,
                    #fff 25px,
                    #d9d9d9 26px
                );
        }

        .day-col {
            position: relative;
            height: 780px;
            border-right: 1px solid #777;
            border-bottom: 1px solid #777;
            background:
                repeating-linear-gradient(
                    to bottom,
                    #fff 0px,
                    #fff 25px,
                    #d9d9d9 26px
                );
        }

        .time-slot {
            position: absolute;
            left: 0;
            width: 100%;
            height: 52px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 10px;
        }

        .time-label {
            font-size: 10px;
            font-weight: bold;
            line-height: 1.05;
            text-align: center;
        }

        .event {
            position: absolute;
            left: 3px;
            right: 3px;
            border: 1px solid #444;
            background: #fff;
            padding: 3px 2px;
            text-align: center;
            overflow: hidden;
        }

        .event.conflict {
            box-shadow: 0 0 0 2px #dc2626 inset;
        }

        .subject-code {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.05;
        }

        .subject-type {
            font-size: 10px;
            font-weight: 700;
            margin-top: 1px;
        }

        .teacher {
            margin-top: 4px;
            font-size: 10px;
            font-weight: 700;
        }

        .room {
            margin-top: 4px;
            font-size: 10px;
            font-weight: 700;
            color: #c1121f;
        }

        .time-range {
            margin-top: 3px;
            font-size: 9px;
            color: #444;
        }

        .legend {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
            font-size: 11px;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .legend-box {
            width: 12px;
            height: 12px;
            border: 1px solid #333;
            background: #fff;
        }

        .legend-conflict {
            box-shadow: 0 0 0 2px #dc2626 inset;
        }

        .summary-wrap {
            margin-top: 10px;
            background: #fff;
            border: 1px solid #bbb;
            padding: 10px;
        }

        .summary-title {
            margin: 0 0 8px 0;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px 7px;
            font-size: 11px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f3f3;
        }

        @page {
            size: landscape;
            margin: 6mm;
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
                font-size: 10px;
            }

            .topbar,
            .run-box,
            .side-panel {
                display: none !important;
            }

            .layout {
                display: block;
            }

            .paper,
            .summary-wrap {
                border: 1px solid #999;
                box-shadow: none;
                width: 100%;
                margin: 0 0 6px 0;
                padding: 5px;
                overflow: visible;
            }

            .legend {
                margin-bottom: 5px;
                font-size: 9px;
            }

            .schedule-grid {
                min-width: auto;
                width: 100%;
                grid-template-columns: 55px repeat(6, 1fr);
            }

            .head {
                font-size: 9px;
                padding: 3px;
            }

            .time-col,
            .day-col {
                height: 660px;
                background:
                    repeating-linear-gradient(
                        to bottom,
                        #fff 0px,
                        #fff 21px,
                        #d9d9d9 22px
                    );
            }

            .time-slot {
                height: 44px;
                padding-top: 1px;
            }

            .time-label {
                font-size: 8px;
                line-height: 1;
            }

            .event {
                left: 2px;
                right: 2px;
                padding: 2px 1px;
                page-break-inside: avoid;
            }

            .subject-code {
                font-size: 9px;
            }

            .subject-type,
            .teacher,
            .room,
            .time-range {
                font-size: 7px;
                margin-top: 1px;
            }

            .summary-wrap {
                margin-top: 5px;
            }

            .summary-title {
                font-size: 11px;
                margin-bottom: 5px;
            }

            th, td {
                font-size: 8px;
                padding: 3px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div>
            <h1>Schedule Preview</h1>
            <div class="meta">
                <div><strong>School Year:</strong> {{ $schoolYear->school_year ?? 'N/A' }}</div>
                <div><strong>Semester:</strong> {{ $semester->semester_name ?? 'N/A' }}</div>
                <div><strong>Section:</strong> {{ $section->section_code }}</div>
                <div><strong>Course:</strong> {{ $section->course->course_name ?? ($section->course->course_code ?? 'N/A') }}</div>
            </div>
        </div>

        <div class="btns">
            <a href="{{ route('schedules.home') }}" class="btn btn-blue">Back</a>
            <a href="{{ route('schedules.history') }}" class="btn btn-gray">Conflict Logs</a>
            <button class="btn btn-gray" onclick="window.print()">Print Preview</button>
        </div>
    </div>

    @php
        $conflicts = collect();
        $successLogs = collect();

        if (isset($generationRun) && $generationRun) {
            $conflicts = $generationRun->conflicts->where('is_conflict', true)->values();
            $successLogs = $generationRun->conflicts->where('is_conflict', false)->values();
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $startDay = \Carbon\Carbon::createFromTimeString('07:00:00');
        $pixelsPerMinute = 0.8667;

        $conflictScheduleIds = $conflicts
            ->pluck('schedule_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $uniqueSubjects = $schedules
            ->map(function ($item) {
                return [
                    'subject_code' => $item->subject->subject_code,
                    'subject_name' => $item->subject->subject_name,
                ];
            })
            ->unique('subject_code')
            ->values();
    @endphp

    @if(isset($generationRun) && $generationRun)
        <div class="run-box">
            <div>
                <strong>Generation Run #{{ $generationRun->id }}</strong>
                @if($generationRun->status === 'success')
                    <span class="badge badge-success">Success</span>
                @elseif($generationRun->status === 'partial')
                    <span class="badge badge-warning">Partial</span>
                @else
                    <span class="badge badge-danger">Failed</span>
                @endif
            </div>

            <div class="run-stats">
                <div><strong>Created:</strong> {{ $generationRun->total_created }}</div>
                <div><strong>Failed:</strong> {{ $generationRun->total_failed }}</div>
                <div>
                    <strong>Summary:</strong>
                    @if($generationRun->status === 'success')
                        Generation completed successfully.
                    @elseif($generationRun->status === 'partial')
                        Generation completed with some unresolved items.
                    @else
                        Generation failed.
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="layout">
        <div>
            <div class="paper">
                <div class="legend">
                    <div class="legend-item">
                        <strong>Section:</strong> {{ $section->section_code }}
                    </div>
                    
                </div>

                <div class="schedule-grid">
                    <div class="head">TIME</div>
                    @foreach($days as $day)
                        <div class="head">{{ strtoupper($day) }}</div>
                    @endforeach

                    <div class="time-col">
                        @for($hour = 7; $hour < 22; $hour++)
                            @php
                                $top = ($hour - 7) * 52;
                            @endphp
                            <div class="time-slot" style="top: {{ $top }}px;">
                                <div class="time-label">
                                    {{ \Carbon\Carbon::createFromTime($hour, 0)->format('h:i') }}<br>
                                    to<br>
                                    {{ \Carbon\Carbon::createFromTime($hour + 1, 0)->format('h:i') }}
                                </div>
                            </div>
                        @endfor
                    </div>

                    @foreach($days as $day)
                        <div class="day-col">
                            @foreach($schedules->where('day', $day) as $item)
                                @php
                                    $start = \Carbon\Carbon::createFromTimeString($item->start_time);
                                    $end = \Carbon\Carbon::createFromTimeString($item->end_time);

                                    $top = $startDay->diffInMinutes($start) * $pixelsPerMinute;
                                    $height = $start->diffInMinutes($end) * $pixelsPerMinute;

                                    $sessionType = strtolower(trim($item->session_type ?? ''));

                                    if ($sessionType === 'laboratory') {
                                        $subjectType = 'Lab';
                                    } else {
                                        $subjectType = 'Lec';
                                    }

                                    $hasConflictMarker = in_array($item->id, $conflictScheduleIds);
                                @endphp

                                <div class="event {{ $hasConflictMarker ? 'conflict' : '' }}"
                                     style="top: {{ $top }}px; height: {{ $height }}px;">
                                    <div class="subject-code">{{ $item->subject->subject_code }}</div>
                                    <div class="subject-type">{{ $subjectType }}</div>
                                    <div class="teacher">{{ $item->instructor->instructor_name }}</div>
                                    <div class="room">
                                        {{ $item->room->room_code }}
                                        @if(!empty($item->room->room_name))
                                            / {{ $item->room->room_name }}
                                        @endif
                                    </div>
                                    <div class="time-range">
                                        {{ \Carbon\Carbon::parse($item->start_time)->format('g:i A') }}
                                        -
                                        {{ \Carbon\Carbon::parse($item->end_time)->format('g:i A') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="summary-wrap">
                <h3 class="summary-title">Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($uniqueSubjects as $item)
                            <tr>
                                <td>{{ $item['subject_code'] }}</td>
                                <td>{{ $item['subject_name'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2">No schedules found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="side-panel">
            <h3>Conflict Logs</h3>

            @if($conflicts->count() > 0)
                <ul class="conflict-list">
                    @foreach($conflicts as $conflict)
                        <li>{{ $conflict->message }}</li>
                    @endforeach
                </ul>
            @else
                <p style="font-size:13px; color:#666;">No conflicts logged.</p>
            @endif

            <hr style="margin: 16px 0; border:none; border-top:1px solid #eee;">

            <h3>Scheduled Successfully</h3>

            @if($successLogs->count() > 0)
                <ul class="success-list">
                    @foreach($successLogs as $log)
                        <li>{{ $log->message }}</li>
                    @endforeach
                </ul>
            @else
                <p style="font-size:13px; color:#666;">No success logs found.</p>
            @endif
        </div>
    </div>
</body>
</html>