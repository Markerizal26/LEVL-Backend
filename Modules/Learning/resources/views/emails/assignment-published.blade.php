<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Baru</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border: 1px solid #e5e5e5;
        }
        .email-header {
            padding: 32px 40px;
            border-bottom: 1px solid #e5e5e5;
        }
        .logo {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }
        .email-body {
            padding: 40px;
        }
        .email-body h1 {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 24px 0;
        }
        .email-body p {
            font-size: 15px;
            color: #404040;
            margin: 0 0 16px 0;
        }
        .assignment-info {
            background-color: #f9f9f9;
            border-left: 4px solid #1a1a1a;
            padding: 20px;
            margin: 24px 0;
        }
        .assignment-info h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 12px 0;
        }
        .assignment-info p {
            font-size: 14px;
            color: #666666;
            margin: 8px 0;
        }
        .deadline {
            color: #d97706;
            font-weight: 500;
        }
        .btn-primary {
            display: inline-block;
            padding: 14px 32px;
            background-color: #1a1a1a;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            margin: 24px 0;
        }
        .btn-secondary {
            display: inline-block;
            padding: 14px 32px;
            background-color: #f5f5f5;
            color: #1a1a1a !important;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            margin: 24px 8px 24px 0;
        }
        .email-footer {
            padding: 32px 40px;
            background-color: #f9f9f9;
            border-top: 1px solid #e5e5e5;
            text-align: center;
        }
        .email-footer p {
            font-size: 13px;
            color: #666666;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1 class="logo">LSP Prep</h1>
        </div>
        <div class="email-body">
            <h1>Assignment Baru Tersedia</h1>
            <p>Halo, <strong>{{ $user->name }}</strong>!</p>
            <p>Assignment baru telah dipublikasikan untuk course yang Anda ikuti:</p>
            
            <div class="assignment-info">
                <h2>{{ $assignment->title }}</h2>
                @if($assignment->description)
                <p>{{ Str::limit($assignment->description, 200) }}</p>
                @endif
                <p><strong>Course:</strong> {{ $course->title }}</p>
                @if($assignment->available_from)
                <p><strong>Tersedia dari:</strong> {{ $assignment->available_from->format('d F Y, H:i') }}</p>
                @endif
                @if($assignment->deadline_at)
                <p class="deadline"><strong>Deadline:</strong> {{ $assignment->deadline_at->format('d F Y, H:i') }}</p>
                @endif
                <p><strong>Maksimal Score:</strong> {{ $assignment->max_score }}</p>
            </div>

            <p>Silakan akses assignment ini dan kirimkan submission Anda sebelum deadline.</p>

            <a href="{{ $assignmentUrl }}" class="btn-primary">Lihat Assignment</a>
            <a href="{{ $courseUrl }}" class="btn-secondary">Lihat Course</a>
        </div>
        <div class="email-footer">
            <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>

