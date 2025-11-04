<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kredensial Akun</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5; line-height: 1.6; }
        .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border: 1px solid #e5e5e5; }
        .email-header { padding: 32px 40px; border-bottom: 1px solid #e5e5e5; }
        .logo { font-size: 24px; font-weight: 600; color: #1a1a1a; margin: 0; }
        .email-body { padding: 40px; }
        .email-body h1 { font-size: 20px; font-weight: 600; color: #1a1a1a; margin: 0 0 24px 0; }
        .email-body p { font-size: 15px; color: #404040; margin: 0 0 16px 0; }
        .btn-primary { display: inline-block; padding: 14px 32px; background-color: #1a1a1a; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 500; border-radius: 6px; margin: 24px 0; }
        .btn-primary:hover { background-color: #333333; }
        .divider { height: 1px; background-color: #e5e5e5; margin: 32px 0; }
        .code-box { background-color: #f8f8f8; border: 1px solid #e5e5e5; border-radius: 6px; padding: 20px; text-align: center; margin: 24px 0; }
        .code-label { font-size: 13px; color: #737373; margin-bottom: 8px; }
        .code-value { font-size: 20px; font-weight: 600; color: #1a1a1a; letter-spacing: 1px; font-family: 'Courier New', monospace; }
        .email-footer { padding: 32px 40px; background-color: #fafafa; border-top: 1px solid #e5e5e5; text-align: center; }
        .email-footer p { font-size: 13px; color: #737373; margin: 0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2 class="logo">Your Company</h2>
        </div>

        <div class="email-body">
            <h1>Akun Anda Telah Dibuat</h1>

            <p>Halo {{ $user->name }},</p>
            <p>Akun Anda telah dibuat oleh administrator. Gunakan kredensial berikut untuk masuk, lalu segera ubah password Anda setelah login.</p>

            <div class="code-box">
                <div class="code-label">Email</div>
                <div class="code-value">{{ $user->email }}</div>
            </div>

            <div class="code-box">
                <div class="code-label">Password Sementara</div>
                <div class="code-value">{{ $password }}</div>
            </div>

            <a href="{{ $loginUrl }}" class="btn-primary" target="_blank" rel="noopener">Masuk Sekarang</a>

            <div class="divider"></div>
            <p style="font-size: 14px; color: #737373;">Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
            <div style="background-color: #f8f8f8; padding: 12px 16px; border-radius: 6px; word-break: break-all; font-size: 13px; margin: 16px 0;">
                <a href="{{ $loginUrl }}" target="_blank" rel="noopener">{{ $loginUrl }}</a>
            </div>
        </div>

        <div class="email-footer">
            <p>Jika Anda merasa tidak terkait dengan pembuatan akun ini, abaikan email ini.</p>
        </div>
    </div>
</body>
</html>
