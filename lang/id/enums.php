<?php

return [
  // Auth
  "user_status" => [
    "pending" => "Menunggu",
    "active" => "Aktif",
    "inactive" => "Tidak Aktif",
    "banned" => "Diblokir",
  ],

  "roles" => [
    "superadmin" => "Super Admin",
    "admin" => "Admin",
    "instructor" => "Instruktur",
    "student" => "Siswa",
  ],

  // Schemes
  "course_status" => [
    "draft" => "Draf",
    "published" => "Dipublikasikan",
    "archived" => "Diarsipkan",
  ],

  "course_type" => [
    "okupasi" => "Okupasi",
    "kluster" => "Kluster",
  ],

  "enrollment_type" => [
    "auto_accept" => "Otomatis Diterima",
    "key_based" => "Berbasis Kunci",
    "approval" => "Perlu Persetujuan",
  ],

  "level_tag" => [
    "dasar" => "Dasar",
    "menengah" => "Menengah",
    "mahir" => "Mahir",
  ],

  "progression_mode" => [
    "sequential" => "Berurutan",
    "free" => "Bebas",
  ],

  "content_type" => [
    "markdown" => "Markdown",
    "video" => "Video",
    "link" => "Tautan",
  ],

  // Enrollments
  "enrollment_status" => [
    "pending" => "Menunggu",
    "active" => "Aktif",
    "completed" => "Selesai",
    "cancelled" => "Dibatalkan",
  ],

  "progress_status" => [
    "not_started" => "Belum Dimulai",
    "in_progress" => "Sedang Berlangsung",
    "completed" => "Selesai",
  ],

  // Learning
  "assignment_status" => [
    "draft" => "Draf",
    "published" => "Dipublikasikan",
    "archived" => "Diarsipkan",
  ],

  "submission_status" => [
    "draft" => "Draf",
    "submitted" => "Dikumpulkan",
    "graded" => "Dinilai",
    "late" => "Terlambat",
  ],

  "submission_type" => [
    "text" => "Teks",
    "file" => "File",
    "mixed" => "Campuran",
  ],

  // Content
  "content_status" => [
    "draft" => "Draf",
    "submitted" => "Diajukan",
    "in_review" => "Sedang Ditinjau",
    "approved" => "Disetujui",
    "rejected" => "Ditolak",
    "scheduled" => "Dijadwalkan",
    "published" => "Dipublikasikan",
    "archived" => "Diarsipkan",
  ],

  "priority" => [
    "low" => "Rendah",
    "medium" => "Sedang",
    "high" => "Tinggi",
  ],

  "target_type" => [
    "all" => "Semua",
    "role" => "Berdasarkan Peran",
    "user" => "Pengguna Tertentu",
  ],

  // Gamification
  "challenge_type" => [
    "daily" => "Harian",
    "weekly" => "Mingguan",
    "special" => "Spesial",
  ],

  "challenge_assignment_status" => [
    "pending" => "Menunggu",
    "in_progress" => "Sedang Berlangsung",
    "completed" => "Selesai",
    "claimed" => "Diklaim",
    "expired" => "Kedaluwarsa",
  ],

  "badge_type" => [
    "achievement" => "Pencapaian",
    "milestone" => "Milestone",
    "completion" => "Penyelesaian",
  ],

  "point_source_type" => [
    "lesson" => "Lesson",
    "assignment" => "Tugas",
    "attempt" => "Percobaan",
    "challenge" => "Tantangan",
    "system" => "Sistem",
  ],

  "point_reason" => [
    "completion" => "Penyelesaian",
    "score" => "Skor",
    "bonus" => "Bonus",
    "penalty" => "Penalti",
  ],

  // Notifications
  "notification_type" => [
    "system" => "Sistem",
    "assignment" => "Tugas",
    "assessment" => "Penilaian",
    "grading" => "Nilai",
    "gamification" => "Gamifikasi",
    "news" => "Berita",
    "custom" => "Kustom",
    "course_completed" => "Kursus Selesai",
    "enrollment" => "Pendaftaran",
    "forum_reply_to_thread" => "Balasan Forum Thread",
    "forum_reply_to_reply" => "Balasan Forum Reply",
  ],

  "notification_channel" => [
    "in_app" => "Dalam Aplikasi",
    "email" => "Email",
    "push" => "Push Notification",
  ],

  "notification_frequency" => [
    "immediate" => "Langsung",
    "daily" => "Harian",
    "weekly" => "Mingguan",
  ],

  // Grading
  "grade_status" => [
    "pending" => "Menunggu",
    "graded" => "Dinilai",
    "reviewed" => "Ditinjau",
  ],

  "source_type" => [
    "assignment" => "Tugas",
    "attempt" => "Percobaan",
  ],

  // Common
  "category_status" => [
    "active" => "Aktif",
    "inactive" => "Tidak Aktif",
  ],

  // Questions
  "question_type" => [
    "multiple_choice" => "Pilihan Ganda",
    "essay" => "Esai",
    "file_upload" => "Unggah File",
    "true_false" => "Benar/Salah",
  ],

  "question_difficulty" => [
    "easy" => "Mudah",
    "medium" => "Sedang",
    "hard" => "Sulit",
  ],

  "question_status" => [
    "active" => "Aktif",
    "inactive" => "Tidak Aktif",
    "archived" => "Diarsipkan",
  ],
];
