<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Penghapusan Akun</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5; line-height: 1.6; }
        .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border: 1px solid #e5e5e5; }
        .email-header { padding: 32px 40px; border-bottom: 1px solid #e5e5e5; }
        .logo { font-size: 24px; font-weight: 600; color: #1a1a1a; margin: 0; }
        .email-body { padding: 40px; }
        .email-body h1 { font-size: 20px; font-weight: 600; color: #dc2626; margin: 0 0 24px 0; }
        .email-body p { font-size: 15px; color: #404040; margin: 0 0 16px 0; }
        .btn-delete { display: inline-block; padding: 14px 32px; background-color: #dc2626; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 500; border-radius: 6px; margin: 24px 0; }
        .btn-delete:hover { background-color: #b91c1c; }
        .info-box { background-color: #fef2f2; border-left: 3px solid #ef4444; padding: 16px; margin: 24px 0; font-size: 14px; color: #991b1b; }
        .divider { height: 1px; background-color: #e5e5e5; margin: 32px 0; }
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
            <h1>Konfirmasi Penghapusan Akun</h1>
            <p>Halo {{ $user->name }},</p>
            <p>Kami menerima permintaan untuk menghapus akun Anda secara permanen.</p>
            <p>Tindakan ini <strong>tidak dapat dibatalkan</strong>. Semua data Anda akan dihapus secara permanen dari sistem kami.</p>
            
            <a href="{{ $verifyUrl }}" class="btn-delete" target="_blank" rel="noopener">Hapus Akun Saya Permanen</a>

            <div class="info-box">
                <strong>Penting:</strong> Link konfirmasi ini berlaku selama {{ $ttlMinutes }} menit dan hanya dapat digunakan satu kali.
            </div>

            <div class="divider"></div>
            <p style="font-size: 14px; color: #737373;">Jika Anda tidak meminta penghapusan akun, segera amankan akun Anda karena seseorang mungkin memiliki akses ke password Anda.</p>
        </div>
        <div class="email-footer">
            <p>Email ini dikirimkan secara otomatis oleh sistem keamanan.</p>
        </div>
    </div>
</body>
</html>
