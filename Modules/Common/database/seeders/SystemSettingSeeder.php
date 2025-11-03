<?php

namespace Modules\Common\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Common\Entities\SystemSetting;
use Modules\Common\Models\SystemSetting as ModelsSystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            ['key' => 'app.name', 'value' => 'LSP Prep', 'type' => 'string'],
            ['key' => 'app.tagline', 'value' => 'Belajar, Latih, dan Bersertifikasi', 'type' => 'string'],
            ['key' => 'app.logo_url', 'value' => '/storage/assets/logo.png', 'type' => 'string'],
            ['key' => 'app.favicon_url', 'value' => '/storage/assets/favicon.ico', 'type' => 'string'],
            ['key' => 'app.timezone', 'value' => 'Asia/Jakarta', 'type' => 'string'],
            ['key' => 'app.locale', 'value' => 'id', 'type' => 'string'],
            ['key' => 'app.currency', 'value' => 'IDR', 'type' => 'string'],

            ['key' => 'auth.allow_registration', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.require_email_verification', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.social_login_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.allowed_social_providers', 'value' => json_encode(['google']), 'type' => 'json'],
            ['key' => 'auth.jwt_expiration_minutes', 'value' => '1440', 'type' => 'number'],
            ['key' => 'auth.otp_expiration_minutes', 'value' => '10', 'type' => 'number'],
            ['key' => 'auth.password_reset_expiration_minutes', 'value' => '30', 'type' => 'number'],

            ['key' => 'auth.login_rate_limit_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.login_rate_limit_max_attempts', 'value' => '5', 'type' => 'number'],
            ['key' => 'auth.login_rate_limit_decay_minutes', 'value' => '1', 'type' => 'number'],

            ['key' => 'auth.lockout_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.lockout_failed_attempts_threshold', 'value' => '5', 'type' => 'number'],
            ['key' => 'auth.lockout_window_minutes', 'value' => '60', 'type' => 'number'],
            ['key' => 'auth.lockout_duration_minutes', 'value' => '15', 'type' => 'number'],

            ['key' => 'learning.progression_mode', 'value' => 'sequential', 'type' => 'string'],
            ['key' => 'learning.max_attempts_per_quiz', 'value' => '3', 'type' => 'number'],
            ['key' => 'learning.auto_complete_lesson', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'learning.default_unit_order', 'value' => 'asc', 'type' => 'string'],
            ['key' => 'learning.enable_discussion_forum', 'value' => 'false', 'type' => 'boolean'],

            ['key' => 'gamification.enable', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'gamification.points.lesson_complete', 'value' => '10', 'type' => 'number'],
            ['key' => 'gamification.points.assignment_submit', 'value' => '20', 'type' => 'number'],
            ['key' => 'gamification.points.quiz_complete', 'value' => '30', 'type' => 'number'],
            ['key' => 'gamification.challenge_bonus_multiplier', 'value' => '1.5', 'type' => 'number'],
            ['key' => 'gamification.level_thresholds', 'value' => json_encode([100, 300, 700, 1500]), 'type' => 'json'],

            ['key' => 'notification.in_app_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'notification.email_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'notification.default_channel', 'value' => 'in_app', 'type' => 'string'],
            ['key' => 'mail.from_name', 'value' => 'LSP Prep System', 'type' => 'string'],
            ['key' => 'mail.from_address', 'value' => 'no-reply@lspprep.id', 'type' => 'string'],
            ['key' => 'mail.smtp_host', 'value' => 'smtp.mailtrap.io', 'type' => 'string'],
            ['key' => 'mail.smtp_port', 'value' => '2525', 'type' => 'number'],
            ['key' => 'mail.smtp_encryption', 'value' => 'tls', 'type' => 'string'],
            ['key' => 'mail.smtp_username', 'value' => 'username123', 'type' => 'string'],
            ['key' => 'mail.smtp_password', 'value' => 'secret', 'type' => 'string'],

            ['key' => 'certificate.auto_issue_on_completion', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'certificate.template_path', 'value' => '/storage/certificates/template.pdf', 'type' => 'string'],
            ['key' => 'certificate.signature_name', 'value' => 'Kepala LSP Detikcom', 'type' => 'string'],
            ['key' => 'certificate.signature_image', 'value' => '/storage/certificates/signature.png', 'type' => 'string'],
            ['key' => 'report.default_format', 'value' => 'pdf', 'type' => 'string'],
            ['key' => 'report.include_timestamp', 'value' => 'true', 'type' => 'boolean'],

            ['key' => 'system.maintenance_mode', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'system.backup_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'system.backup_schedule', 'value' => '0 3 * * *', 'type' => 'string'],
            ['key' => 'system.api_rate_limit', 'value' => '100', 'type' => 'number'],
            ['key' => 'system.audit_retention_days', 'value' => '90', 'type' => 'number'],
        ];

        foreach ($settings as $s) {
            ModelsSystemSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
