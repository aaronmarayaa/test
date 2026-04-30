<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conflict Logs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 30px;
            color: #222;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        p {
            color: #666;
            margin-top: 0;
            margin-bottom: 24px;
        }

        .meta {
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-link {
            display: inline-block;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-history {
            background: #2563eb;
            color: #fff;
        }

        .btn-preview {
            background: #16a34a;
            color: #fff;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-info {
            background: #e8f0ff;
            color: #1d4ed8;
        }

        .badge-warning {
            background: #fff7e6;
            color: #b26a00;
        }

        .badge-error {
            background: #fdeaea;
            color: #b42318;
        }

        .yes {
            color: #b42318;
            font-weight: bold;
        }

        .no {
            color: #1f7a3f;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f9fafb;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Conflict Logs</h1>
            <p>Detailed logs for this generation run, including both conflicts and successful schedules.</p>

            <div class="top-actions">
                <a href="{{ route('schedules.history') }}" class="btn-link btn-history">Back to History</a>
                <a
                    href="{{ route('schedules.preview', [
                        'section' => $run->section_id,
                        'school_year' => $run->school_year_id,
                        'semester' => $run->semester_id
                    ]) }}"
                    class="btn-link btn-preview"
                >
                    Go to Preview
                </a>
            </div>

            <div class="meta">
                <div><strong>Run ID:</strong> {{ $run->id }}</div>
                <div><strong>Course:</strong> {{ $run->section->course->course_code ?? 'N/A' }}</div>
                <div><strong>Section:</strong> {{ $run->section->section_code ?? 'N/A' }}</div>
                <div><strong>School Year:</strong> {{ $run->schoolYear->school_year ?? 'N/A' }}</div>
                <div><strong>Semester:</strong> {{ $run->semester->semester_name ?? 'N/A' }}</div>
                <div><strong>Status:</strong> {{ ucfirst($run->status) }}</div>
                <div><strong>Total Created:</strong> {{ $run->total_created }}</div>
                <div><strong>Total Failed:</strong> {{ $run->total_failed }}</div>
                <div>
                    <strong>Summary:</strong>
                    @if($run->status === 'success')
                        Generation completed successfully.
                    @elseif($run->status === 'partial')
                        Generation completed with some unresolved items.
                    @else
                        Generation failed.
                    @endif
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Conflict Type</th>
                        <th>Severity</th>
                        <th>Conflict?</th>
                        <th>Subject</th>
                        <th>Instructor</th>
                        <th>Room</th>
                        <th>Schedule</th>
                        <th>Message</th>
                        <th>Logged At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conflicts as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->conflict_type }}</td>
                            <td>
                                <span class="badge badge-{{ $log->severity }}">
                                    {{ ucfirst($log->severity) }}
                                </span>
                            </td>
                            <td>
                                @if($log->is_conflict)
                                    <span class="yes">Yes</span>
                                @else
                                    <span class="no">No</span>
                                @endif
                            </td>
                            <td>
                                @if($log->subject)
                                    {{ $log->subject->subject_code }} - {{ $log->subject->subject_name }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $log->instructor->instructor_name ?? 'N/A' }}</td>
                            <td>
                                @if($log->room)
                                    {{ $log->room->room_code ?? $log->room->room_name }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @if($log->schedule)
                                    {{ $log->schedule->day }}<br>
                                    {{ \Carbon\Carbon::parse($log->schedule->start_time)->format('g:i A') }}
                                    -
                                    {{ \Carbon\Carbon::parse($log->schedule->end_time)->format('g:i A') }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $log->message }}</td>
                            <td>{{ $log->created_at ? $log->created_at->format('M d, Y h:i A') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">No logs found for this generation run.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>