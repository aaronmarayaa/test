<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automatic Schedule Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 30px;
            color: #222;
        }

        .container {
            max-width: 900px;
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

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #e8f7ec;
            color: #1f7a3f;
        }

        .alert-error {
            background: #fdeaea;
            color: #b42318;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select, input[type="checkbox"] {
            font-size: 15px;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background: #fff;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .btn-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        button {
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 15px;
            cursor: pointer;
        }

        .btn-primary {
            background: #355F43;
            color: white;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }
        .btn-blank {
            background: #8d8d8d;
            color: white;
        }

        .small {
            margin-top: 18px;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Automatic Schedule Generator</h1>
            <p>Select the section, school year, and semester, then generate its schedule automatically.</p>
            
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
                
            <form method="POST" action="{{ route('schedules.generate') }}">
                @csrf

                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" required>
                        <option value="">Select Course</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">
                                {{ $course->course_code }} - {{ $course->course_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="section_id">Section</label>
                    <select name="section_id" id="section_id" required>
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" data-course="{{ $section->course_id }}">
                                {{ $section->section_code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="school_year_id">School Year</label>
                    <select name="school_year_id" id="school_year_id" required>
                        <option value="">Select School Year</option>
                        @foreach($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear->id }}">
                                {{ $schoolYear->school_year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester_id">Semester</label>
                    <select name="semester_id" id="semester_id" required>
                        <option value="">Select Semester</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}">
                                {{ $semester->semester_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" name="replace_existing" id="replace_existing" value="1">
                    <label for="replace_existing" style="margin:0;">Replace existing schedule for this section</label>
                </div>
                
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div class="btn-row">
                        <button type="submit" class="btn-primary">Generate Schedule</button>
                        <a href="{{ route('schedules.summary') }}" class="btn-blank" style="text-decoration:none; display:inline-block; padding: 0.7rem 1rem; border-radius: 12px;">
                            View Generated Schedules
                        </a>
                        <a href="{{ route('schedules.management') }}" 
                        class="btn-primary" 
                        style="text-decoration:none; display:inline-block; padding: 0.7rem 1rem; border-radius: 12px;">
                            Manage System
                        </a>
                    </div>
                    <div class="btn-row" style="margin-bottom: 20px;">
                        
                    </div>
                    <div class="btn-row" style="margin-bottom: 20px;">
                        
                    </div>
                </div>
            </form>

            <div class="small">
                After generation, the system will redirect to the preview page.
            </div>   
        </div>
    </div>
</body>
<script>
    const courseSelect = document.getElementById('course_id');
    const sectionSelect = document.getElementById('section_id');

    courseSelect.addEventListener('change', function () {
        const selectedCourse = this.value;

        // reset section
        sectionSelect.value = "";

        Array.from(sectionSelect.options).forEach(option => {
            if (!option.value) return; // skip "Select Section"

            if (option.getAttribute('data-course') === selectedCourse) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
    });

    window.addEventListener('load', () => {
        Array.from(sectionSelect.options).forEach(option => {
            if (option.value) option.style.display = 'none';
        });
    });
</script>
</html>