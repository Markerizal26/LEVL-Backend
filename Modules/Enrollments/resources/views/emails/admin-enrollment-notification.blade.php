<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Enrollment Baru</title>
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
        .btn-primary {
            display: inline-block;
            padding: 14px 32px;
            background-color: #1a1a1a;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            border-radius: 6px;
            margin: 24px 0;
        }
        .btn-primary:hover {
            background-color: #333333;
        }
        .status-box {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            margin: 8px 0;
        }
        .status-pending {
            background-color: #fffbeb;
            color: #92400e;
        }
        .status-active {
            background-color: #f0fdf4;
            color: #166534;
        }
        .info-box {
            background-color: #f8f8f8;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 20px;
            margin: 24px 0;
        }
        .info-row {
            margin: 12px 0;
        }
        .info-label {
            font-size: 13px;
            color: #737373;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 15px;
            color: #1a1a1a;
            font-weight: 500;
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
            <h1>Notifikasi Enrollment Baru</h1>

            <p>Halo {{ $admin->name }},</p>

            <p>Ada enrollment baru pada course yang Anda kelola:</p>

            <div class="info-box">
                <div class="info-row">
                    <div class="info-label">Course</div>
                    <div class="info-value">{{ $course->title }} ({{ $course->code }})</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Peserta</div>
                    <div class="info-value">{{ $student->name }} ({{ $student->email }})</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status</div>
                    <div>
                        <span class="status-box status-{{ $enrollment->status->value }}">
                            @if($enrollment->status === \Modules\Enrollments\Enums\EnrollmentStatus::Pending)
                                Menunggu Persetujuan
                            @elseif($enrollment->status === \Modules\Enrollments\Enums\EnrollmentStatus::Active)
                                Terdaftar Aktif
                            @else
                                {{ ucfirst($enrollment->status->value) }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            @if($enrollment->status === \Modules\Enrollments\Enums\EnrollmentStatus::Pending)
                <p>Silakan tinjau dan setujui atau tolak permintaan enrollment ini melalui dashboard admin.</p>
                <a href="{{ $enrollmentsUrl }}" class="btn-primary" target="_blank" rel="noopener">Kelola Enrollments</a>
            @else
                <p>Peserta telah terdaftar aktif pada course ini.</p>
                <a href="{{ $enrollmentsUrl }}" class="btn-primary" target="_blank" rel="noopener">Lihat Enrollments</a>
            @endif

            <p style="font-size: 14px; color: #737373; margin-top: 24px;">Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
            <div style="background-color: #f8f8f8; padding: 12px 16px; border-radius: 6px; word-break: break-all; font-size: 13px; margin: 16px 0;">
                <a href="{{ $enrollmentsUrl }}" target="_blank" rel="noopener">{{ $enrollmentsUrl }}</a>
            </div>
        </div>

        <div class="email-footer">
            <p>Email ini dikirim secara otomatis dari sistem Prep LSP.</p>
        </div>
    </div>
</body>
</html>

