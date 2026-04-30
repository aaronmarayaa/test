<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generated Schedule Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 30px;
            color: #222;
        }

        .container {
            max-width: 1200px;
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
            background: #355F43;
            color: #fff;
        }

        .btn-preview {
            background: #16a34a;
            color: #fff;
            border: none;
            cursor: pointer;
            padding: 8px 14px;
            border-radius: 8px;
        }

        .btn-clear {
            background: #dc2626;
            color: #fff;
            border: none;
            cursor: pointer;
            padding: 8px 14px;
            border-radius: 8px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #e8f7ec;
            color: #1f7a3f;
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

        .btn-blank {
            background: #8d8d8d;
            color: white;
        }

        form {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Generated Schedule Summary</h1>
            <p>View all generated schedules. You can preview or clear each generated schedule set.</p>

            <div class="top-actions">
                <a href="{{ route('schedules.home') }}" class="btn-link btn-home">Back to Generator</a>
                <a href="{{ route('schedules.history') }}" class="btn-blank"  style="text-decoration:none; display: flex; align-items: center; padding: 10px; border-radius: 12px;">
                    View Generation History
                </a>
            </div>
            

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>School Year</th>
                        <th>Semester</th>
                        <th>Total Scheduled Entries</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($generatedSchedules as $row)
                        <tr>
                            <td>{{ $row->section->course->course_code ?? 'N/A' }}</td>
                            <td>{{ $row->section->section_code ?? 'N/A' }}</td>
                            <td>{{ $row->schoolYear->school_year ?? 'N/A' }}</td>
                            <td>{{ $row->semester->semester_name ?? 'N/A' }}</td>
                            <td>{{ $row->total_subject_schedules }}</td>
                            <td>
                                <div class="actions">
                                    <a
                                        href="{{ route('schedules.preview', [
                                            'section' => $row->section_id,
                                            'school_year' => $row->school_year_id,
                                            'semester' => $row->semester_id
                                        ]) }}"
                                        class="btn-link btn-preview"
                                    >
                                        Preview
                                    </a>

                                    <form method="POST" action="{{ route('schedules.clear') }}" onsubmit="return confirm('Clear this generated schedule?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="section_id" value="{{ $row->section_id }}">
                                        <input type="hidden" name="school_year_id" value="{{ $row->school_year_id }}">
                                        <input type="hidden" name="semester_id" value="{{ $row->semester_id }}">
                                        <button type="submit" class="btn-clear">Clear</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No generated schedules found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>