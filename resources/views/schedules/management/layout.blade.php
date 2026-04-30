<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Management' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 30px;
            color: #1f2937;
        }

        .container {
            max-width: 1450px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .title h1 {
            margin: 0 0 6px 0;
            font-size: 28px;
            font-weight: 700;
            color: #111827;
        }

        .title p {
            margin: 0;
            color: #6b7280;
            font-size: 15px;
        }

        .back-btn {
            text-decoration: none;
            background: #e5e7eb;
            color: #111827;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 700;
            display: inline-block;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 18px;
            align-items: start;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .card h2 {
            margin: 0 0 22px 0;
            font-size: 26px;
            color: #111827;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 15px;
            box-sizing: border-box;
            background: white;
            color: #111827;
        }

        .form-group textarea {
            resize: vertical;
        }

        .save-btn {
            border: none;
            background: #166534;
            color: white;
            padding: 13px 22px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
        }

        .list-table {
            width: 100%;
            border-collapse: collapse;
        }

        .list-table th,
        .list-table td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 14px;
        }

        .list-table th {
            background: #f9fafb;
            font-weight: 700;
            color: #374151;
        }

        .tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
        }

        .tag-green {
            background: #dcfce7;
            color: #166534;
        }

        .tag-yellow {
            background: #fef3c7;
            color: #92400e;
        }

        .tag-gray {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-edit {
            background: #e5e7eb;
            color: #111827;
            border: none;
            padding: 9px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
            border: none;
            padding: 9px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-row {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .filter-btn {
            border: none;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            background: #e5e7eb;
            color: #111827;
        }

        .filter-btn.active {
            background: #166534;
            color: white;
        }

        .notice {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div class="title">
                <h1>{{ $pageTitle ?? 'Management' }}</h1>
                <p>{{ $pageSubtitle ?? '' }}</p>
            </div>

            <div style="diplay: flex; ">
                <a href="{{ route('schedules.home') }}" class="back-btn">Back to Dashboard</a>
                <a href="{{ route('schedules.management') }}" class="back-btn">← Back to Panel</a>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                @yield('form')
            </div>

            <div class="card">
                @yield('list')
            </div>
        </div>
    </div>
</body>
</html>