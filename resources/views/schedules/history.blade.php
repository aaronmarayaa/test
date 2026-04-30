<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Generation History</title>
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

        .btn-home {
            background: #2563eb;
            color: #fff;
        }

        .btn-preview {
            background: #16a34a;
            color: #fff;
        }

        .btn-logs {
            background: #f59e0b;
            color: #fff;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-success {
            background: #e8f7ec;
            color: #1f7a3f;
        }

        .status-partial {
            background: #fff7e6;
            color: #b26a00;
        }

        .status-failed {
            background: #fdeaea;
            color: #b42318;
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
            vertical-align: middle;
        }

        th {
            background: #f9fafb;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            <h1>Schedule Generation History</h1>
            <p>View all generation runs and inspect their preview or conflict logs.</p>

            <div class="top-actions">
                <a href="{{ route('schedules.home') }}" class="btn-link btn-home">Back to Generator</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Run ID</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>School Year</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Failed</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->id }}</td>
                            <td>{{ $run->section->course->course_code ?? 'N/A' }}</td>
                            <td>{{ $run->section->section_code ?? 'N/A' }}</td>
                            <td>{{ $run->schoolYear->school_year ?? 'N/A' }}</td>
                            <td>{{ $run->semester->semester_name ?? 'N/A' }}</td>
                            <td>
                                <span class="status status-{{ $run->status }}">
                                    {{ ucfirst($run->status) }}
                                </span>
                            </td>
                            <td>{{ $run->total_created }}</td>
                            <td>{{ $run->total_failed }}</td>
                            <td>{{ $run->created_at ? $run->created_at->format('M d, Y h:i A') : 'N/A' }}</td>
                            <td>
                                <div class="actions">
                                    <a
                                        href="{{ route('schedules.preview', [
                                            'section' => $run->section_id,
                                            'school_year' => $run->school_year_id,
                                            'semester' => $run->semester_id
                                        ]) }}"
                                        class="btn-link btn-preview"
                                    >
                                        Preview
                                    </a>

                                    <a
                                        href="{{ route('schedules.conflict-logs', $run->id) }}"
                                        class="btn-link btn-logs"
                                    >
                                        View Logs
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">No generation runs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>