<?php

return [
  // HTTP status messages
  "success" => "Berhasil.",
  "data_retrieved" => "Data berhasil diambil.",
  "error" => "Terjadi kesalahan.",
  "not_found" => "Resource yang Anda cari tidak ditemukan.",
  "unauthorized" => "Anda tidak berhak mengakses resource ini.",
  "unauthenticated" => "Sesi anda telah berakhir. Silakan login kembali.",
  "forbidden" => "Anda tidak memiliki izin untuk melakukan aksi ini.",
  "validation_error" => "Validasi gagal.",
  "validation_failed" => "Data yang Anda kirim tidak valid. Periksa kembali isian Anda.",
  "server_error" => "Terjadi kesalahan server.",
  "bad_request" => "Permintaan tidak valid.",
  "conflict" => "Permintaan Anda bertentangan dengan state resource yang ada.",
  "gone" => "Resource yang Anda minta telah dihapus secara permanen.",
  "session_expired" => "Sesi Anda telah kedaluwarsa. Silakan login kembali.",
  "session_invalid" => "Sesi tidak valid. Silakan login kembali.",
  "session_blacklisted" => "Sesi telah diblokir. Silakan login kembali.",
  "session_not_found" => "Sesi tidak ditemukan. Silakan login kembali.",
  "user_data_not_found" => "Data pengguna tidak ditemukan.",
  "invalid_credentials" => "Kredensial tidak valid.",

  // Common action messages
  "resource_created" => ":resource berhasil dibuat.",
  "items_count" => "{0} Tidak ada item|{1} 1 item|[2,*] :count item",
  "users_count" => "{0} Tidak ada pengguna|{1} 1 pengguna|[2,*] :count pengguna",
  "minutes" => "{1} :count menit|[2,*] :count menit",
  "hours" => "{1} :count jam|[2,*] :count jam",
  "records_found" => "{0} Tidak ada record ditemukan|{1} 1 record ditemukan|[2,*] :count record ditemukan",

  // Common Module
  "categories" => [
    "list_retrieved" => "Daftar kategori berhasil diambil.",
    "created" => "Kategori berhasil dibuat.",
    "updated" => "Kategori berhasil diperbarui.",
    "deleted" => "Kategori berhasil dihapus.",
    "not_found" => "Kategori tidak ditemukan.",
  ],

  // Tags Module
  "tags" => [
    "created" => "Tag berhasil dibuat.",
    "updated" => "Tag berhasil diperbarui.",
    "deleted" => "Tag berhasil dihapus.",
    "not_found" => "Tag tidak ditemukan.",
    "list_retrieved" => "Daftar tag berhasil diambil.",
  ],

  // Units Module
  "units" => [
    "created" => "Unit berhasil dibuat.",
    "updated" => "Unit berhasil diperbarui.",
    "deleted" => "Unit berhasil dihapus.",
    "published" => "Unit berhasil dipublish.",
    "unpublished" => "Unit berhasil diunpublish.",
    "reordered" => "Urutan unit berhasil diperbarui.",
    "order_updated" => "Urutan unit berhasil diperbarui.",
    "not_found" => "Unit tidak ditemukan.",
    "not_in_course" => "Unit tidak ditemukan di course ini.",
    "no_create_access" => "Anda tidak memiliki akses untuk membuat unit di course ini.",
    "no_update_access" => "Anda tidak memiliki akses untuk mengubah unit ini.",
    "no_delete_access" => "Anda tidak memiliki akses untuk menghapus unit ini.",
    "no_publish_access" => "Anda tidak memiliki akses untuk mempublish unit ini.",
    "no_unpublish_access" => "Anda tidak memiliki akses untuk unpublish unit ini.",
    "no_reorder_access" => "Anda tidak memiliki akses untuk mengatur urutan unit di course ini.",
    "some_not_found" => "Beberapa unit tidak ditemukan di course ini.",
  ],

  // Lessons Module
  "lessons" => [
    "created" => "Lesson berhasil dibuat.",
    "updated" => "Lesson berhasil diperbarui.",
    "deleted" => "Lesson berhasil dihapus.",
    "published" => "Lesson berhasil dipublish.",
    "unpublished" => "Lesson berhasil diunpublish.",
    "not_found" => "Lesson tidak ditemukan.",
    "not_in_unit" => "Lesson tidak ditemukan di unit ini.",
    "no_view_list_access" => "Anda tidak memiliki akses untuk melihat daftar lesson.",
    "no_view_access" => "Anda tidak memiliki akses untuk melihat lesson ini.",
    "no_create_access" => "Anda tidak memiliki akses untuk membuat lesson di unit ini.",
    "no_update_access" => "Anda tidak memiliki akses untuk mengubah lesson ini.",
    "no_delete_access" => "Anda tidak memiliki akses untuk menghapus lesson ini.",
    "no_publish_access" => "Anda tidak memiliki akses untuk mempublish lesson ini.",
    "no_unpublish_access" => "Anda tidak memiliki akses untuk unpublish lesson ini.",
    "not_enrolled" => "Anda harus terdaftar untuk mengakses lesson ini.",
    "locked_prerequisite" => "Lesson ini terkunci. Selesaikan lesson prasyarat terlebih dahulu.",
    "unavailable" => "Lesson belum tersedia.",
  ],

  // Questions Module
  "questions" => [
    "created" => "Pertanyaan berhasil dibuat.",
    "updated" => "Pertanyaan berhasil diperbarui.",
    "deleted" => "Pertanyaan berhasil dihapus.",
    "not_found" => "Pertanyaan tidak ditemukan.",
  ],

  // Lesson Blocks Module
  "lesson_blocks" => [
    "created" => "Blok lesson berhasil dibuat.",
    "updated" => "Blok lesson berhasil diperbarui.",
    "deleted" => "Blok lesson berhasil dihapus.",
    "not_found" => "Blok lesson tidak ditemukan.",
    "lesson_not_in_course" => "Lesson tidak ditemukan di course ini.",
    "course_not_found" => "Course tidak ditemukan.",
    "no_view_access" => "Anda tidak memiliki akses untuk melihat blok lesson ini.",
    "no_manage_access" => "Anda tidak memiliki akses untuk mengelola blok lesson di lesson ini.",
    "no_update_access" => "Anda tidak memiliki akses untuk mengubah blok lesson ini.",
    "no_delete_access" => "Anda tidak memiliki akses untuk menghapus blok lesson ini.",
  ],

  "common" => [
    "master_data_retrieved" => "Master data berhasil diambil.",
    "not_found" => "Data tidak ditemukan.",
  ],

  "master_data" => [
    "types_retrieved" => "Daftar tipe master data berhasil diambil.",
    "roles_retrieved" => "Daftar peran berhasil diambil.",
    "user_statuses" => "Daftar status pengguna",
    "course_statuses" => "Daftar status kursus",
    "course_types" => "Daftar tipe kursus",
    "enrollment_types" => "Daftar tipe pendaftaran",
    "level_tags" => "Daftar level kesulitan",
    "progression_modes" => "Daftar mode progres",
    "content_types" => "Daftar tipe konten",
    "enrollment_statuses" => "Daftar status pendaftaran",
    "progress_statuses" => "Daftar status progres",
    "assignment_statuses" => "Daftar status tugas",
    "submission_statuses" => "Daftar status pengumpulan",
    "submission_types" => "Daftar tipe pengumpulan",
    "content_statuses" => "Daftar status konten",
    "priorities" => "Daftar prioritas",
    "target_types" => "Daftar tipe target",
    "challenge_types" => "Daftar tipe tantangan",
    "challenge_assignment_statuses" => "Daftar status tantangan user",
    "challenge_criteria_types" => "Daftar tipe kriteria tantangan",
    "badge_types" => "Daftar tipe badge",
    "point_source_types" => "Daftar sumber poin",
    "point_reasons" => "Daftar alasan poin",
    "notification_types" => "Daftar tipe notifikasi",
    "notification_channels" => "Daftar channel notifikasi",
    "notification_frequencies" => "Daftar frekuensi notifikasi",
    "grade_statuses" => "Daftar status nilai",
    "grade_source_types" => "Daftar sumber nilai",
    "category_statuses" => "Daftar status kategori",
    "setting_types" => "Daftar tipe pengaturan",
  ],

  // Courses Module
  "courses" => [
    "created" => "Course berhasil dibuat.",
    "updated" => "Course berhasil diperbarui.",
    "deleted" => "Course berhasil dihapus.",
    "published" => "Course berhasil dipublish.",
    "unpublished" => "Course berhasil diunpublish.",
    "enrollment_settings_updated" => "Pengaturan enrollment berhasil diperbarui.",
    "not_found" => "Course tidak ditemukan.",
    "no_unpublish_access" => "Anda tidak memiliki akses untuk unpublish course ini.",
    "no_update_key_access" => "Anda tidak memiliki akses untuk mengubah enrollment key course ini.",
    "no_remove_key_access" =>
      "Anda tidak memiliki akses untuk menghapus enrollment key course ini.",
    "no_generate_key_access" =>
      "Anda tidak memiliki akses untuk generate enrollment key course ini.",
    "key_generated" => "Enrollment key berhasil di-generate.",
    "key_removed" => "Enrollment key dihapus dan tipe enrollment diubah ke auto_accept.",
    "code_exists" => "Kode sudah digunakan.",
    "slug_exists" => "Slug sudah digunakan.",
    "duplicate_data" => "Data duplikat. Silakan periksa input Anda.",
  ],

  // Auth Module
  "auth" => [
    "login_success" => "Login berhasil.",
    "logout_success" => "Logout berhasil.",
    "register_success" => "Registrasi berhasil. Silakan cek email Anda untuk verifikasi.",
    "user_created_success" => "User berhasil dibuat.",
    "invalid_credentials" => "Kredensial tidak valid.",
    "account_inactive" => "Akun Anda tidak aktif.",
    "account_suspended" => "Akun Anda telah ditangguhkan.",
    "email_not_verified" => "Silakan verifikasi email Anda terlebih dahulu.",
    "google_oauth_failed" => "Tidak dapat memulai Google OAuth. Silakan login manual.",
    "email_already_verified" => "Email Anda sudah diverifikasi.",
    "verification_sent" =>
      "Link verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali.",
    "email_change_sent" => "Link verifikasi perubahan email telah dikirim. Berlaku 3 menit.",
    "email_changed" => "Email berhasil diubah dan diverifikasi.",
    "verification_expired" => "Kode verifikasi telah kedaluwarsa.",
    "verification_invalid" => "Kode verifikasi salah.",
    "verification_invalid_or_token" => "Kode verifikasi salah atau token tidak valid.",
    "email_taken" => "Email sudah digunakan oleh akun lain.",
    "verification_not_found" => "Link verifikasi tidak ditemukan.",
    "verification_failed" => "Verifikasi gagal.",
    "email_verified" => "Email Anda berhasil diverifikasi.",
    "link_expired" => "Link verifikasi telah kedaluwarsa.",
    "link_invalid" => "Link verifikasi tidak valid atau sudah digunakan.",
    "link_not_found" => "Link verifikasi tidak ditemukan.",
    "email_change_not_found" => "Link verifikasi perubahan email tidak ditemukan.",
    "email_change_invalid" => "Link verifikasi perubahan email tidak valid atau sudah digunakan.",
    "email_change_expired" => "Link verifikasi perubahan email telah kedaluwarsa.",
    "email_change_success" => "Email Anda berhasil diubah.",
    "credentials_resent" => "Kredensial berhasil dikirim ulang.",
    "user_not_found" => "User tidak ditemukan",
    "admin_only" => "Hanya untuk akun Admin, Superadmin, atau Instructor dengan status pending.",
    "status_updated" => "Status user berhasil diperbarui.",
    "student_creation_forbidden" => "Pendaftaran Student tidak diperbolehkan melalui Admin API. Student harus melakukan registrasi secara mandiri.",
    "status_cannot_be_pending" => "Mengubah status ke pending tidak diperbolehkan.",
    "status_cannot_be_changed_from_pending" => "Status user yang masih 'pending' tidak dapat diubah secara manual.",
    "password_changed" => "Password berhasil diubah.",
    "password_set_success" => "Password berhasil diatur.",
    "password_already_set" => "Password sudah diatur sebelumnya.",
    "avatar_deleted" => "Avatar berhasil dihapus.",
    "username_already_set" => "Username sudah diatur untuk akun Anda.",
    "username_set_success" => "Username berhasil diatur.",
    "cannot_deactivate_self" => "Tidak dapat menonaktifkan akun Anda sendiri.",
    "cannot_delete_self" => "Tidak dapat menghapus akun Anda sendiri.",
    "no_access_to_user" => "Anda tidak memiliki akses untuk melihat pengguna ini.",
    "current_password_incorrect" => "Password saat ini salah.",
    "password_min_length" => "Password baru harus minimal 8 karakter.",
    "password_incorrect" => "Password salah.",
    "profile_retrieved" => "Profil berhasil diambil.",
    "refresh_success" => "Token berhasil diperbarui.",
    "email_not_verified" => "Email belum terverifikasi. Silakan verifikasi email Anda terlebih dahulu.",
    "middleware_refresh_only" => "Middleware ini hanya untuk endpoint refresh.",
    "refresh_token_required" => "Refresh token diperlukan.",
    "refresh_token_invalid" => "Refresh token tidak valid atau kadaluarsa.",
    "account_not_active" => "Akun tidak aktif.",
    "avatar_upload_failed" => "Avatar gagal diunggah. Pastikan format file benar (JPG, PNG, GIF) dan ukuran tidak melebihi 2MB.",
    "avatar_single_file" => "Hanya boleh mengunggah 1 avatar.",
    "deletion_request_sent" => "Link konfirmasi penghapusan akun telah dikirim ke email Anda.",
    "account_deleted_success" => "Akun Anda telah berhasil dihapus.",
    "deletion_failed" => "Gagal menghapus akun. Link konfirmasi tidak valid atau sudah kadaluarsa.",
    "bulk_export_queued" => "Ekspor user sedang diproses dan akan dikirim ke email Anda.",
  ],

  "account" => [
    "restore_success" => "Akun berhasil dipulihkan.",
    "deletion_in_progress" => "Akun Anda sudah dalam proses penghapusan.",
    "restore_not_deleted" => "Hanya akun dengan status terhapus yang dapat dipulihkan.",
    "restore_expired" => "Masa tenggang pemulihan akun (:days hari) telah berakhir.",
    "cleanup_success" => "Berhasil menghapus :count akun secara permanen.",
  ],

  // User Module
  "user" => [
    "not_found" => "Pengguna tidak ditemukan.",
  ],

  // Password Module
  "password" => [
    "reset_sent" => "Link reset password telah dikirim ke email Anda.",
    "reset_success" => "Password berhasil direset.",
    "invalid_reset_token" => "Token reset tidak valid.",
    "expired_reset_token" => "Token reset telah kedaluwarsa.",
    "user_not_found" => "User tidak ditemukan.",
    "unauthorized" => "Tidak terotorisasi.",
    "old_password_mismatch" => "Password lama salah.",
    "updated" => "Password berhasil diperbarui.",
    "current_required" => "Kata sandi saat ini wajib diisi.",
    "new_required" => "Kata sandi baru wajib diisi.",
    "min_length" => "Kata sandi baru harus minimal 8 karakter.",
    "confirmation_mismatch" => "Konfirmasi kata sandi tidak cocok.",
    "strength_requirements" => "Kata sandi harus mengandung minimal satu huruf besar, satu huruf kecil, satu angka, dan satu karakter spesial (@$!%*?&#).",
    "token_invalid" => "Token reset tidak valid.",
    "token_expired" => "Token reset telah kadaluarsa.",
  ],

  // Profile Module
  "profile" => [
    "updated" => "Profil berhasil diperbarui.",
    "achievement_retrieved" => "Pencapaian berhasil diambil.",
    "activity_retrieved" => "Log aktivitas berhasil diambil.",
    "privacy_updated" => "Pengaturan privasi berhasil diperbarui.",
    "statistics_retrieved" => "Statistik berhasil diambil.",
    "not_found" => "Profil tidak ditemukan.",
    "account_updated" => "Informasi akun berhasil diperbarui.",
    "account_deleted" => "Akun berhasil dihapus. Anda memiliki 30 hari untuk memulihkannya.",
    "updated_success" => "Profil berhasil diperbarui.",
    "suspended_success" => "Akun berhasil ditangguhkan.",
    "activated_success" => "Akun berhasil diaktifkan.",
    "no_permission" => "Anda tidak memiliki izin untuk melihat profil ini.",
  ],

  // Achievement Module
  "achievement" => [
    "badge_not_owned" => "Anda tidak memiliki badge ini.",
    "badge_not_pinned" => "Badge tidak disematkan.",
  ],

  // Announcements Module
  "announcements" => [
    "created" => "Pengumuman berhasil dibuat.",
    "updated" => "Pengumuman berhasil diperbarui.",
    "deleted" => "Pengumuman berhasil dihapus.",
    "published" => "Pengumuman berhasil dipublikasikan.",
    "scheduled" => "Pengumuman berhasil dijadwalkan.",
    "not_found" => "Pengumuman tidak ditemukan.",
    "list_retrieved" => "Daftar pengumuman berhasil diambil.",
    "marked_read" => "Pengumuman ditandai sudah dibaca.",
  ],

  // News Module
  "news" => [
    "created" => "Berita berhasil dibuat.",
    "updated" => "Berita berhasil diperbarui.",
    "deleted" => "Berita berhasil dihapus.",
    "published" => "Berita berhasil dipublikasikan.",
    "scheduled" => "Berita berhasil dijadwalkan.",
    "not_found" => "Berita tidak ditemukan.",
    "list_retrieved" => "Daftar berita berhasil diambil.",
  ],

  // Enrollments Module
  "enrollments" => [
    "enrolled" => "Berhasil mendaftar ke course.",
    "unenrolled" => "Berhasil membatalkan pendaftaran dari course.",
    "completed" => "Course berhasil diselesaikan.",
    "already_enrolled" => "Sudah terdaftar di course ini.",
    "not_enrolled" => "Belum terdaftar di course ini.",
    "cancelled" => "Permintaan enrollment berhasil dibatalkan.",
    "withdrawn" => "Anda berhasil mengundurkan diri dari course.",
    "course_list_retrieved" => "Daftar enrollment course berhasil diambil.",
    "list_retrieved" => "Daftar enrollment berhasil diambil.",
    "status_retrieved" => "Status enrollment berhasil diambil.",
    "course_not_managed" => "Course tidak ditemukan atau tidak berada di bawah pengelolaan Anda.",
    "no_view_all_access" => "Anda tidak memiliki akses untuk melihat seluruh enrollment.",
    "no_view_course_access" => "Anda tidak memiliki akses untuk melihat enrollment course ini.",
    "no_view_access" => "Anda tidak memiliki akses untuk melihat enrollment ini.",
    "no_view_status_access" => "Anda tidak memiliki akses untuk melihat status enrollment ini.",
    "no_cancel_access" => "Anda tidak memiliki akses untuk membatalkan enrollment ini.",
    "no_withdraw_access" =>
      "Anda tidak memiliki akses untuk mengundurkan diri dari enrollment ini.",
    "no_approve_access" => "Anda tidak memiliki akses untuk menyetujui enrollment ini.",
    "no_reject_access" => "Anda tidak memiliki akses untuk menolak enrollment ini.",
    "no_remove_access" => "Anda tidak memiliki akses untuk mengeluarkan peserta dari course ini.",
    "student_only" => "Hanya peserta yang dapat melakukan enrollment.",
    "request_not_found" => "Permintaan enrollment tidak ditemukan untuk course ini.",
    "expelled" => "Peserta berhasil dikeluarkan dari course.",
    "not_enrolled" => "Anda belum terdaftar pada course ini.",
    "approved" => "Permintaan enrollment disetujui.",
    "rejected" => "Permintaan enrollment ditolak.",
    "key_required" => "Kode enrollment wajib diisi.",
    "key_invalid" => "Kode enrollment tidak valid.",
  ],

  // Assignments Module
  "assignments" => [
    "submitted" => "Tugas berhasil dikumpulkan.",
    "not_found" => "Tugas tidak ditemukan.",
  ],

  // Submissions Module
  "submissions" => [
    "created" => "Pengumpulan berhasil dibuat.",
    "not_found" => "Pengumpulan tidak ditemukan.",
  ],

  // Learning Module
  "learning" => [
    "progress_saved" => "Progres pembelajaran berhasil disimpan.",
  ],

  // Challenges Module
  "challenges" => [
    "created" => "Tantangan berhasil dibuat.",
    "completed" => "Tantangan berhasil diselesaikan.",
    "not_found" => "Challenge tidak ditemukan.",
    "retrieved" => "Challenge berhasil diambil.",
    "completions_retrieved" => "Riwayat penyelesaian challenge berhasil diambil.",
    "reward_claimed" => "Reward berhasil diklaim!",
  ],

  // Gamification Module
  "gamification" => [
    "points_awarded" => "Poin berhasil diberikan.",
  ],

  // Forums Module
  "forums" => [
    "reaction_added" => "Reaksi berhasil ditambahkan.",
    "statistics_retrieved" => "Statistik forum berhasil diambil.",
  ],

  // Notifications Module
  "notifications" => [
    "list_retrieved" => "Notifikasi berhasil diambil.",
    "marked_read" => "Notifikasi ditandai sudah dibaca.",
    "preferences_updated" => "Preferensi notifikasi berhasil diperbarui.",
    "preferences_reset" => "Preferensi notifikasi berhasil direset ke default.",
    "failed_update_preferences" => "Gagal memperbarui preferensi notifikasi.",
    "failed_reset_preferences" => "Gagal mereset preferensi notifikasi.",
  ],

  // Search Module  
  "search" => [
    "history_cleared" => "Riwayat pencarian berhasil dihapus",
    "history_deleted" => "Riwayat pencarian berhasil dihapus",
  ],

  // Common action messages
  "created" => "Berhasil dibuat.",
  "updated" => "Berhasil diperbarui.",
  "deleted" => "Berhasil dihapus.",
  "restored" => "Berhasil dipulihkan.",
  "archived" => "Berhasil diarsipkan.",
  "published" => "Berhasil dipublikasi.",
  "unpublished" => "Berhasil dibatalkan publikasinya.",
  "approved" => "Berhasil disetujui.",
  "rejected" => "Berhasil ditolak.",
  "sent" => "Berhasil dikirim.",
  "saved" => "Berhasil disimpan.",

  // Permission messages
  "permission_denied" => "Akses ditolak.",
  "insufficient_permissions" => "Anda tidak memiliki izin yang cukup.",
  "role_required" => "Aksi ini memerlukan role :role.",

  // Validation messages
  "invalid_input" => "Input tidak valid.",
  "missing_required_field" => "Field wajib tidak ada.",
];
