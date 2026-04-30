<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Management Panel</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f7fb;
            padding: 30px;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        h1 {
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .btn {
            display: block;
            text-decoration: none;
            border: 1px solid #868686;
            color: rgb(0, 0, 0);
            padding: 14px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            box-shadow: 7px 5px #888888;
        }

        .btn:hover {
            border: 1px solid #355F43;
        }

        .back {
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
            color: #555;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h1>Management Panel</h1>

        <div class="grid">
            <a href="{{ route('schedules.management.school-years') }}" class="btn">School Year</a>
            <a href="{{ route('schedules.management.courses') }}" class="btn">Course</a>
            <a href="{{ route('schedules.management.rooms') }}" class="btn">Rooms</a>
            <a href="{{ route('schedules.management.instructors') }}" class="btn">Instructors</a>
            <a href="{{ route('schedules.management.sections') }}" class="btn">Section</a>
            <a href="{{ route('schedules.management.subjects') }}" class="btn">Subject</a>
            <a href="{{ route('schedules.management.curricula') }}" class="btn">Curriculum</a>
            <a href="{{ route('schedules.management.faculty-subjects') }}" class="btn">Faculty Subjects</a>
            <a href="{{ route('schedules.management.faculty-availability') }}" class="btn">Faculty Availability</a>
        </div>

        <a href="{{ route('schedules.home') }}" class="back">← Back to Home</a>
    </div>
</div>

</body>
</html>