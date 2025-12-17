<?php

return [
  "error" => "Terjadi kesalahan.",
  "success" => "Berhasil.",
  "unauthorized" => "Anda tidak berhak mengakses resource ini.",
  "forbidden" => "Anda tidak memiliki izin untuk melakukan aksi ini.",
  "not_found" => "Resource yang Anda cari tidak ditemukan.",
  "invalid_request" => "Permintaan tidak valid.",
  "validation_failed" => "Data yang Anda kirim tidak valid. Periksa kembali isian Anda.",
  "server_error" => "Terjadi kesalahan pada server. Silakan coba lagi nanti.",
  "unauthenticated" => "Anda harus login terlebih dahulu.",
  "token_expired" => "Token Anda telah kadaluarsa. Silakan login kembali.",
  "too_many_requests" => "Terlalu banyak permintaan. Silakan coba lagi nanti.",
  "method_not_allowed" => "Metode HTTP tidak diizinkan untuk resource ini.",
  "conflict" => "Permintaan Anda bertentangan dengan state resource yang ada.",
  "gone" => "Resource yang Anda minta telah dihapus secara permanen.",

  // Common action messages
  "created" => "Berhasil dibuat.",
  "updated" => "Berhasil diperbarui.",
  "deleted" => "Berhasil dihapus.",
  "restored" => "Berhasil dipulihkan.",
  "archived" => "Berhasil diarsipkan.",
  "published" => "Berhasil dipublikasikan.",
  "unpublished" => "Berhasil dibatalkan publikasinya.",
  "approved" => "Berhasil disetujui.",
  "rejected" => "Berhasil ditolak.",
  "sent" => "Berhasil dikirim.",
  "saved" => "Berhasil disimpan.",

  // Resource-specific messages
  "resource_created" => ":resource berhasil dibuat.",
  "resource_updated" => ":resource berhasil diperbarui.",
  "resource_deleted" => ":resource berhasil dihapus.",
  "resource_not_found" => ":resource tidak ditemukan.",

  // Authentication messages
  "login_success" => "Login berhasil.",
  "logout_success" => "Logout berhasil.",
  "password_changed" => "Password berhasil diubah.",
  "password_reset_sent" => "Link reset password telah dikirim ke email Anda.",
  "invalid_credentials" => "Kredensial tidak valid.",
  "account_locked" => "Akun Anda telah dikunci.",
  "account_inactive" => "Akun Anda tidak aktif.",

  // Permission messages
  "permission_denied" => "Izin ditolak.",
  "insufficient_permissions" => "Anda tidak memiliki izin yang cukup.",
  "role_required" => "Aksi ini memerlukan peran :role.",

  // Validation messages
  "invalid_input" => "Input yang diberikan tidak valid.",
  "missing_required_field" => "Field yang wajib diisi tidak ada.",
  "invalid_format" => "Format tidak valid.",
  "value_too_long" => "Nilai terlalu panjang.",
  "value_too_short" => "Nilai terlalu pendek.",

  // File upload messages
  "file_uploaded" => "File berhasil diunggah.",
  "file_upload_failed" => "Gagal mengunggah file.",
  "file_too_large" => "File terlalu besar.",
  "invalid_file_type" => "Tipe file tidak valid.",

  // Database messages
  "duplicate_entry" => "Entri duplikat.",
  "foreign_key_constraint" => "Tidak dapat menghapus karena ada record terkait.",
  "database_error" => "Terjadi kesalahan database.",

  // General messages
  "operation_successful" => "Operasi berhasil diselesaikan.",
  "operation_failed" => "Operasi gagal.",
  "no_changes_made" => "Tidak ada perubahan yang dibuat.",
  "processing" => "Memproses...",
  "please_wait" => "Mohon tunggu...",
  "try_again" => "Silakan coba lagi.",
  "contact_support" => "Silakan hubungi dukungan jika masalah berlanjut.",
  "feature_unavailable" => "Fitur belum tersedia.",
  "endpoint_unavailable" => "Endpoint tidak tersedia untuk saat ini.",

  // Common error messages
  "errors" => [
    "unauthorized" => "Tidak terotorisasi.",
    "not_found" => "Tidak ditemukan.",
    "forbidden" => "Anda tidak memiliki akses.",
    "invalid_token" => "Token tidak valid atau telah kedaluwarsa.",
    "expired_token" => "Token telah kedaluwarsa.",
    "user_not_found" => "Pengguna tidak ditemukan.",
    "password_mismatch" => "Password lama tidak cocok.",
    "already_exists" => "Sudah ada.",
  ],

  // Questions Module
  "questions" => [
    "retrieved" => "Pertanyaan berhasil diambil.",
    "list_retrieved" => "Daftar pertanyaan berhasil diambil.",
    "created" => "Pertanyaan berhasil dibuat.",
    "updated" => "Pertanyaan berhasil diperbarui.",
    "deleted" => "Pertanyaan berhasil dihapus.",
    "not_found" => "Pertanyaan tidak ditemukan.",
    "random_retrieved" => "Pertanyaan acak berhasil diambil.",
  ],

  // Categories Module
  "categories" => [
    "created" => "Kategori berhasil dibuat.",
    "updated" => "Kategori berhasil diperbarui.",
    "deleted" => "Kategori berhasil dihapus.",
    "retrieved" => "Kategori berhasil diambil.",
    "list_retrieved" => "Daftar kategori berhasil diambil.",
    "not_found" => "Kategori tidak ditemukan.",
  ],

  // Master Data
  "master_data" => [
    "types_retrieved" => "Daftar tipe master data berhasil diambil.",
    "roles_retrieved" => "Daftar peran berhasil diambil.",
  ],

  // News Module
  "news" => [
    "created" => "Berita berhasil dibuat.",
    "updated" => "Berita berhasil diperbarui.",
    "deleted" => "Berita berhasil dihapus.",
    "published" => "Berita berhasil dipublikasikan.",
    "scheduled" => "Berita berhasil dijadwalkan.",
    "retrieved" => "Berita berhasil diambil.",
    "list_retrieved" => "Daftar berita berhasil diambil.",
    "not_found" => "Berita tidak ditemukan.",
  ],

  // Announcements Module
  "announcements" => [
    "created" => "Pengumuman berhasil dibuat.",
    "updated" => "Pengumuman berhasil diperbarui.",
    "deleted" => "Pengumuman berhasil dihapus.",
    "retrieved" => "Pengumuman berhasil diambil.",
    "list_retrieved" => "Daftar pengumuman berhasil diambil.",
    "marked_read" => "Pengumuman ditandai sudah dibaca.",
    "not_found" => "Pengumuman tidak ditemukan.",
  ],

  // Search Module
  "search" => [
    "history_deleted" => "Riwayat pencarian berhasil dihapus.",
    "history_cleared" => "Riwayat pencarian berhasil dibersihkan.",
  ],

  // Notifications Module
  "notifications" => [
    "preferences_updated" => "Preferensi notifikasi berhasil diperbarui.",
    "preferences_reset" => "Preferensi notifikasi berhasil direset ke default.",
    "failed_update_preferences" => "Gagal memperbarui preferensi notifikasi.",
    "failed_reset_preferences" => "Gagal mereset preferensi notifikasi.",
  ],

  // Profile Module
  "profile" => [
    "updated" => "Profil berhasil diperbarui.",
    "avatar_uploaded" => "Avatar berhasil diunggah.",
    "avatar_deleted" => "Avatar berhasil dihapus.",
    "privacy_updated" => "Pengaturan privasi berhasil diperbarui.",
    "badge_pinned" => "Badge berhasil dipasang.",
    "badge_unpinned" => "Badge berhasil dilepas.",
    "badge_already_pinned" => "Badge is already pinned.",
  ],

  // Account Module
  "account" => [
    "deleted" => "Akun berhasil dihapus. Anda memiliki 30 hari untuk memulihkannya.",
    "restored" => "Akun berhasil dipulihkan.",
    "restore_success" => "Account restored successfully.",
  ],

  // Password Module
  "password" => [
    "reset_sent" =>
      "Jika email atau username terdaftar, kami telah mengirimkan instruksi reset kata sandi.",
    "reset_success" => "Kata sandi berhasil direset.",
    "updated" => "Kata sandi berhasil diperbarui.",
    "invalid_reset_token" => "Token reset tidak valid atau telah kedaluwarsa.",
    "user_not_found" => "Pengguna tidak ditemukan.",
    "expired_reset_token" => "Token reset telah kedaluwarsa.",
    "unauthorized" => "Tidak terotorisasi.",
    "old_password_mismatch" => "Password lama tidak cocok.",
  ],

  // Challenges Module
  "challenges" => [
    "retrieved" => "Tantangan berhasil diambil.",
    "list_retrieved" => "Daftar tantangan berhasil diambil.",
    "completions_retrieved" => "Daftar penyelesaian tantangan berhasil diambil.",
  ],

  // Learning Module - Assignments
  "assignments" => [
    "created" => "Tugas berhasil dibuat.",
    "updated" => "Tugas berhasil diperbarui.",
    "deleted" => "Tugas berhasil dihapus.",
    "published" => "Tugas berhasil dipublikasikan.",
    "unpublished" => "Tugas berhasil dibatalkan publikasinya.",
    "retrieved" => "Tugas berhasil diambil.",
    "list_retrieved" => "Daftar tugas berhasil diambil.",
    "not_found" => "Tugas tidak ditemukan.",
  ],

  // Learning Module - Submissions
  "submissions" => [
    "created" => "Pengumpulan berhasil dibuat.",
    "updated" => "Pengumpulan berhasil diperbarui.",
    "graded" => "Pengumpulan berhasil dinilai.",
    "retrieved" => "Pengumpulan berhasil diambil.",
    "list_retrieved" => "Daftar pengumpulan berhasil diambil.",
    "not_found" => "Pengumpulan tidak ditemukan.",
    "no_view_access" => "Anda tidak memiliki akses untuk melihat submission ini.",
    "no_update_access" => "Anda tidak memiliki akses untuk mengubah submission ini.",
    "only_draft_editable" => "Hanya submission dengan status draft yang dapat diubah.",
    "no_grade_access" => "Anda tidak memiliki akses untuk menilai submission ini.",
  ],

  // Enrollments Module
  "enrollments" => [
    "cancelled" => "Permintaan enrollment berhasil dibatalkan.",
    "expelled" => "Peserta berhasil dikeluarkan dari course.",
    "no_view_all_access" => "Anda tidak memiliki akses untuk melihat seluruh enrollment.",
    "no_view_course_access" => "Anda tidak memiliki akses untuk melihat enrollment course ini.",
    "no_view_access" => "Anda tidak memiliki akses untuk melihat enrollment ini.",
    "student_only" => "Hanya peserta yang dapat melakukan enrollment.",
    "request_not_found" => "Permintaan enrollment tidak ditemukan untuk course ini.",
    "no_cancel_access" => "Anda tidak memiliki akses untuk membatalkan enrollment ini.",
    "not_found" => "Enrollment tidak ditemukan untuk course ini.",
    "no_view_status_access" => "Anda tidak memiliki akses untuk melihat status enrollment ini.",
    "no_approve_access" => "Anda tidak memiliki akses untuk menyetujui enrollment ini.",
    "no_reject_access" => "Anda tidak memiliki akses untuk menolak enrollment ini.",
    "no_report_access" => "Anda tidak memiliki akses untuk melihat laporan course ini.",
    "no_report_view_access" => "Anda tidak memiliki akses untuk melihat laporan ini.",
    "no_export_access" => "Anda tidak memiliki akses untuk export data course ini.",
  ],

  // Progress Module
  "progress" => [
    "updated" => "Progress berhasil diperbarui.",
    "not_authenticated" => "Anda belum login.",
    "no_view_other_access" => "Anda tidak diperbolehkan melihat progress peserta lain.",
    "no_course_access" => "Anda tidak memiliki akses ke course ini.",
    "role_forbidden" => "Peran Anda tidak diperbolehkan mengakses progress course.",
    "enrollment_not_found" => "Progress peserta tidak ditemukan atau peserta belum terdaftar.",
    "student_only" => "Hanya peserta yang dapat menandai lesson sebagai selesai.",
    "unit_not_in_course" => "Unit tidak ditemukan di course ini.",
    "lesson_not_in_unit" => "Lesson tidak ditemukan di unit ini.",
    "lesson_unavailable" => "Lesson belum tersedia.",
    "not_enrolled" => "Anda belum terdaftar pada course ini.",
    "locked_prerequisite" => "Lesson masih terkunci karena prasyarat belum selesai.",
  ],

  // Tags Module
  "tags" => [
    "created" => "Tag berhasil dibuat.",
    "updated" => "Tag berhasil diperbarui.",
    "deleted" => "Tag berhasil dihapus.",
    "not_found" => "Tag tidak ditemukan.",
  ],

  // Courses Module
  "courses" => [
    "created" => "Course berhasil dibuat.",
    "updated" => "Course berhasil diperbarui.",
    "deleted" => "Course berhasil dihapus.",
    "published" => "Course berhasil dipublish.",
    "unpublished" => "Course berhasil diunpublish.",
    "enrollment_settings_updated" => "Enrollment settings berhasil diperbarui.",
    "not_found" => "Course tidak ditemukan.",
    "no_unpublish_access" => "Anda tidak memiliki akses untuk unpublish course ini.",
    "no_update_key_access" => "Anda tidak memiliki akses untuk update enrollment key course ini.",
    "no_remove_key_access" => "Anda tidak memiliki akses untuk remove enrollment key course ini.",
  ],

  // Units Module
  "units" => [
    "created" => "Unit berhasil dibuat.",
    "updated" => "Unit berhasil diperbarui.",
    "deleted" => "Unit berhasil dihapus.",
    "order_updated" => "Urutan unit berhasil diperbarui.",
    "published" => "Unit berhasil dipublish.",
    "unpublished" => "Unit berhasil diunpublish.",
    "not_found" => "Unit tidak ditemukan.",
    "no_create_access" =>
      "Anda hanya dapat membuat unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin.",
    "no_update_access" => "Anda tidak memiliki akses untuk mengubah unit ini.",
    "no_delete_access" => "Anda tidak memiliki akses untuk menghapus unit ini.",
    "no_reorder_access" =>
      "Anda hanya dapat mengatur urutan unit untuk course yang Anda buat atau course yang Anda kelola sebagai admin.",
    "some_not_found" => "Beberapa unit tidak ditemukan di course ini.",
    "no_publish_access" => "Anda tidak memiliki akses untuk mempublish unit ini.",
    "no_unpublish_access" => "Anda tidak memiliki akses untuk unpublish unit ini.",
    "not_in_course" => "Unit tidak ditemukan di course ini.",
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
    "no_view_access" => "Anda tidak memiliki akses untuk melihat lesson ini.",
    "no_view_list_access" => "Anda tidak memiliki akses untuk melihat lessons di course ini.",
    "no_create_access" =>
      "Anda hanya dapat membuat lesson untuk course yang Anda buat atau course yang Anda kelola sebagai admin.",
    "not_enrolled" => "Anda belum terdaftar pada course ini.",
    "locked_prerequisite" => "Lesson masih terkunci karena prasyarat belum selesai.",
    "no_update_access" => "Anda tidak memiliki akses untuk mengubah lesson ini.",
    "no_delete_access" => "Anda tidak memiliki akses untuk menghapus lesson ini.",
    "no_publish_access" => "Anda tidak memiliki akses untuk mempublish lesson ini.",
    "no_unpublish_access" => "Anda tidak memiliki akses untuk unpublish lesson ini.",
    "unavailable" => "Lesson belum tersedia.",
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
    "no_manage_access" => "Anda hanya dapat mengelola blok untuk course yang Anda kelola.",
    "no_update_access" => "Anda tidak memiliki akses untuk mengubah blok ini.",
    "no_delete_access" => "Anda tidak memiliki akses untuk menghapus blok ini.",
  ],

  // Users Module - Bulk Operations
  "users" => [
    "bulk_export_queued" => "Export sedang diproses dan akan dikirim ke email Anda",
    "bulk_activated" => ":count pengguna berhasil diaktifkan",
    "bulk_deactivated" => ":count pengguna berhasil dinonaktifkan",
    "bulk_deleted" => ":count pengguna berhasil dihapus",
    "no_export_access" => "Anda tidak memiliki akses untuk export pengguna",
    "no_activate_access" => "Anda tidak memiliki akses untuk mengaktifkan pengguna",
    "no_deactivate_access" => "Anda tidak memiliki akses untuk menonaktifkan pengguna",
    "no_delete_access" => "Anda tidak memiliki akses untuk menghapus pengguna",
    "cannot_deactivate_self" => "Tidak dapat menonaktifkan akun Anda sendiri",
    "cannot_delete_self" => "Tidak dapat menghapus akun Anda sendiri",
  ],
];
