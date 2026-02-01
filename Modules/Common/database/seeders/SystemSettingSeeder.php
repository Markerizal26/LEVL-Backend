<?php

namespace Modules\Common\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Common\Models\SystemSetting as ModelsSystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ============================================
            // Application Settings
            // ============================================
            ['key' => 'app.name', 'value' => env('APP_NAME', 'LSP Prep'), 'type' => 'string'],
            ['key' => 'app.tagline', 'value' => 'Belajar, Latih, dan Bersertifikasi', 'type' => 'string'],
            ['key' => 'app.logo_url', 'value' => '/storage/assets/logo.png', 'type' => 'string'],
            ['key' => 'app.favicon_url', 'value' => '/storage/assets/favicon.ico', 'type' => 'string'],
            ['key' => 'app.timezone', 'value' => env('APP_TIMEZONE', 'Asia/Jakarta'), 'type' => 'string'],
            ['key' => 'app.locale', 'value' => env('APP_LOCALE', 'id'), 'type' => 'string'],
            ['key' => 'app.currency', 'value' => 'IDR', 'type' => 'string'],

            // ============================================
            // Authentication & Security Settings
            // ============================================
            ['key' => 'auth.allow_registration', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.require_email_verification', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.social_login_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.allowed_social_providers', 'value' => json_encode(['google']), 'type' => 'json'],
            ['key' => 'auth.jwt_expiration_minutes', 'value' => '1440', 'type' => 'number'],
            ['key' => 'auth.otp_expiration_minutes', 'value' => '10', 'type' => 'number'],
            ['key' => 'auth.password_reset_expiration_minutes', 'value' => '30', 'type' => 'number'],

            // Rate Limiting
            ['key' => 'auth.login_rate_limit_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.login_rate_limit_max_attempts', 'value' => '5', 'type' => 'number'],
            ['key' => 'auth.login_rate_limit_decay_minutes', 'value' => '1', 'type' => 'number'],

            // Account Lockout
            ['key' => 'auth.lockout_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'auth.lockout_failed_attempts_threshold', 'value' => '5', 'type' => 'number'],
            ['key' => 'auth.lockout_window_minutes', 'value' => '60', 'type' => 'number'],
            ['key' => 'auth.lockout_duration_minutes', 'value' => '15', 'type' => 'number'],

            // ============================================
            // Enrollment Settings
            // ============================================
            ['key' => 'enrollment.auto_approve', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'enrollment.max_courses_per_user', 'value' => '10', 'type' => 'number'],
            ['key' => 'enrollment.allow_unenroll', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'enrollment.require_approval', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'enrollment.waitlist_enabled', 'value' => 'false', 'type' => 'boolean'],

            // ============================================
            // Learning Settings
            // ============================================
            ['key' => 'learning.progression_mode', 'value' => 'sequential', 'type' => 'string'],
            ['key' => 'learning.max_attempts_per_quiz', 'value' => '3', 'type' => 'number'],
            ['key' => 'learning.auto_complete_lesson', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'learning.default_unit_order', 'value' => 'asc', 'type' => 'string'],
            ['key' => 'learning.enable_discussion_forum', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'learning.allow_resubmit', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'learning.late_penalty_percent', 'value' => '10', 'type' => 'number'],
            ['key' => 'learning.minimum_lesson_time_seconds', 'value' => '30', 'type' => 'number'],

            // ============================================
            // Grading Settings
            // ============================================
            ['key' => 'grading.passing_score_percent', 'value' => '70', 'type' => 'number'],

            ['key' => 'grading.auto_publish_grades', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'grading.grading_scale', 'value' => json_encode(['A' => 90, 'B' => 80, 'C' => 70, 'D' => 60, 'E' => 0]), 'type' => 'json'],
            ['key' => 'grading.show_correct_answers', 'value' => 'true', 'type' => 'boolean'],

            // ============================================
            // Gamification Settings
            // ============================================
            ['key' => 'gamification.enable', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'gamification.points.lesson_complete', 'value' => '10', 'type' => 'number'],
            ['key' => 'gamification.points.assignment_submit', 'value' => '20', 'type' => 'number'],
            ['key' => 'gamification.points.quiz_complete', 'value' => '30', 'type' => 'number'],
            ['key' => 'gamification.points.course_complete', 'value' => '50', 'type' => 'number'],
            ['key' => 'gamification.points.perfect_score_bonus', 'value' => '20', 'type' => 'number'],
            ['key' => 'gamification.challenge_bonus_multiplier', 'value' => '1.5', 'type' => 'number'],
            ['key' => 'gamification.level_thresholds', 'value' => json_encode([100, 300, 700, 1500, 3000]), 'type' => 'json'],
            ['key' => 'gamification.leaderboard_enabled', 'value' => 'true', 'type' => 'boolean'],

            // ============================================
            // Notification Settings
            // ============================================
            ['key' => 'notification.in_app_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'notification.email_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'notification.default_channel', 'value' => 'in_app', 'type' => 'string'],
            ['key' => 'notification.digest_frequency', 'value' => 'daily', 'type' => 'string'],
            ['key' => 'notification.retention_days', 'value' => '30', 'type' => 'number'],

            // ============================================
            // Mail Settings (Use .env for credentials!)
            // ============================================
            ['key' => 'mail.from_name', 'value' => env('MAIL_FROM_NAME', 'LSP Prep System'), 'type' => 'string'],
            ['key' => 'mail.from_address', 'value' => env('MAIL_FROM_ADDRESS', 'no-reply@lspprep.id'), 'type' => 'string'],
            ['key' => 'mail.smtp_host', 'value' => env('MAIL_HOST', 'smtp.mailtrap.io'), 'type' => 'string'],
            ['key' => 'mail.smtp_port', 'value' => env('MAIL_PORT', '2525'), 'type' => 'number'],
            ['key' => 'mail.smtp_encryption', 'value' => env('MAIL_ENCRYPTION', 'tls'), 'type' => 'string'],
            // Note: SMTP credentials should be configured via .env, not stored in database

            // ============================================
            // Media & Upload Settings
            // ============================================
            ['key' => 'media.max_upload_size_mb', 'value' => '50', 'type' => 'number'],
            ['key' => 'media.allowed_file_types', 'value' => json_encode(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'zip']), 'type' => 'json'],
            ['key' => 'media.storage_driver', 'value' => env('FILESYSTEM_DISK', 'local'), 'type' => 'string'],
            ['key' => 'media.image_max_width', 'value' => '2048', 'type' => 'number'],
            ['key' => 'media.image_max_height', 'value' => '2048', 'type' => 'number'],

            // ============================================
            // Certificate Settings
            // ============================================
            ['key' => 'certificate.auto_issue_on_completion', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'certificate.template_path', 'value' => '/storage/certificates/template.pdf', 'type' => 'string'],
            ['key' => 'certificate.signature_name', 'value' => 'Kepala LSP', 'type' => 'string'],
            ['key' => 'certificate.signature_title', 'value' => 'Direktur Sertifikasi', 'type' => 'string'],
            ['key' => 'certificate.signature_image', 'value' => '/storage/certificates/signature.png', 'type' => 'string'],
            ['key' => 'certificate.validity_years', 'value' => '3', 'type' => 'number'],

            // ============================================
            // Report Settings
            // ============================================
            ['key' => 'report.default_format', 'value' => 'pdf', 'type' => 'string'],
            ['key' => 'report.include_timestamp', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'report.include_logo', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'report.max_export_rows', 'value' => '10000', 'type' => 'number'],

            // ============================================
            // Search Settings
            // ============================================
            ['key' => 'search.engine', 'value' => env('SCOUT_DRIVER', 'meilisearch'), 'type' => 'string'],
            ['key' => 'search.results_per_page', 'value' => '20', 'type' => 'number'],
            ['key' => 'search.enable_fuzzy_search', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'search.min_query_length', 'value' => '2', 'type' => 'number'],

            // ============================================
            // Forums Settings
            // ============================================
            ['key' => 'forums.enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'forums.moderation_required', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'forums.allow_attachments', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'forums.max_post_length', 'value' => '10000', 'type' => 'number'],
            ['key' => 'forums.allow_anonymous', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'forums.auto_close_days', 'value' => '90', 'type' => 'number'],

            // ============================================
            // Security Settings
            // ============================================
            ['key' => 'security.force_https', 'value' => env('APP_ENV') === 'production' ? 'true' : 'false', 'type' => 'boolean'],
            ['key' => 'security.session_lifetime_minutes', 'value' => '120', 'type' => 'number'],
            ['key' => 'security.enforce_2fa', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'security.allowed_ip_addresses', 'value' => json_encode([]), 'type' => 'json'],
            ['key' => 'security.block_concurrent_sessions', 'value' => 'false', 'type' => 'boolean'],

            // ============================================
            // System Settings
            // ============================================
            ['key' => 'system.maintenance_mode', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'system.maintenance_message', 'value' => 'System sedang dalam pemeliharaan. Mohon coba lagi nanti.', 'type' => 'string'],
            ['key' => 'system.backup_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'system.backup_schedule', 'value' => '0 3 * * *', 'type' => 'string'],
            ['key' => 'system.backup_retention_days', 'value' => '30', 'type' => 'number'],
            ['key' => 'system.api_rate_limit', 'value' => '100', 'type' => 'number'],
            ['key' => 'system.api_rate_limit_window_minutes', 'value' => '1', 'type' => 'number'],
            ['key' => 'system.audit_retention_days', 'value' => '90', 'type' => 'number'],
            ['key' => 'system.enable_debug_mode', 'value' => env('APP_DEBUG', 'false'), 'type' => 'boolean'],
            ['key' => 'system.log_level', 'value' => env('LOG_LEVEL', 'error'), 'type' => 'string'],
        ];

        foreach ($settings as $s) {
            ModelsSystemSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
