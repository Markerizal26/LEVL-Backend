<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Perubahan Email</title>
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
        .btn-verify {
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
        .btn-verify:hover {
            background-color: #333333;
        }
        .divider {
            height: 1px;
            background-color: #e5e5e5;
            margin: 32px 0;
        }
        .code-box {
            background-color: #f8f8f8;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .code-label {
            font-size: 13px;
            color: #737373;
            margin-bottom: 8px;
        }
        .code-value {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 4px;
            color: #1a1a1a;
        }
        .info-box {
            font-size: 14px;
            color: #737373;
            background-color: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 12px 16px;
            margin-top: 12px;
        }
        .email-footer {
            padding: 24px 40px;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #737373;
        }
        .url-box {
            display: block;
            font-size: 13px;
            overflow-wrap: anywhere;
            background-color: #f8f8f8;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 12px 16px;
            margin-top: 8px;
        }
    </style>
    <!--[if mso]>
    <style type="text/css">
        .btn-verify { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2 class="logo">Your Company</h2>
        </div>
        <div class="email-body">
            <h1>Verifikasi Perubahan Email</h1>
            <p>Halo {{ $user->name }},</p>
            <p>Kami menerima permintaan untuk <strong>mengubah email akun</strong> Anda menjadi <strong>{{ $newEmail }}</strong>.</p>
            <p>Jika ini benar, silakan konfirmasi perubahan dengan mengklik tombol di bawah ini:</p>
            <a href="{{ $verifyUrl }}" class="btn-verify" target="_blank" rel="noopener">Verifikasi Perubahan Email</a>

            <div class="info-box">
                <strong>Penting:</strong> Link ini berlaku selama {{ $ttlMinutes }} menit dan hanya dapat digunakan satu kali.
            </div>

            <div class="divider"></div>

            <p style="font-size: 14px; color: #737373;">Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
            <div class="url-box">
                <a href="{{ $verifyUrl }}" target="_blank" rel="noopener">{{ $verifyUrl }}</a>
            </div>

            <div class="code-box">
                <div class="code-label">Atau gunakan kode verifikasi:</div>
                <div class="code-value">{{ $code }}</div>
            </div>
        </div>
        <div class="email-footer">
            <p>Jika Anda tidak meminta perubahan ini, abaikan email ini dan jangan bagikan kode di atas kepada siapapun.</p>
        </div>
    </div>
</body>
</html>


