<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Enrollment Ditolak</title>
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
        .info-box {
            background-color: #fef2f2;
            border-left: 3px solid #ef4444;
            padding: 16px;
            margin: 24px 0;
            font-size: 14px;
            color: #991b1b;
        }
        .course-box {
            background-color: #f8f8f8;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 20px;
            margin: 24px 0;
        }
        .course-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 8px 0;
        }
        .course-code {
            font-size: 14px;
            color: #737373;
            margin: 0;
        }
        .email-footer {
            padding: 32px 40px;
            background-color: #fafafa;
            border-top: 1px solid #e5e5e5;
            text-align: center;
        }
        .email-footer p {
            font-size: 13px;
            color: #737373;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2 class="logo">Prep LSP</h2>
        </div>

        <div class="email-body">
            <h1>Permintaan Enrollment Ditolak</h1>

            <p>Halo {{ $student->name }},</p>

            <p>Kami memberitahu bahwa permintaan enrollment Anda untuk course berikut telah ditolak:</p>

            <div class="course-box">
                <div class="course-title">{{ $course->title }}</div>
                <div class="course-code">{{ $course->code }}</div>
            </div>

            <div class="info-box">
                <strong>Status:</strong> Permintaan enrollment Anda telah ditolak oleh admin atau instructor course.
            </div>

            <p>Jika Anda memiliki pertanyaan mengenai keputusan ini, silakan hubungi admin atau instructor course terkait.</p>
        </div>

        <div class="email-footer">
            <p>Terima kasih atas pengertian Anda.</p>
        </div>
    </div>
</body>
</html>
