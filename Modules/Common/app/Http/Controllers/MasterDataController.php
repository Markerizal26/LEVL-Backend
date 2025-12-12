<?php

namespace Modules\Common\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\UserStatus;
use Modules\Common\Enums\CategoryStatus;
use Modules\Common\Enums\SettingType;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Enums\Priority;
use Modules\Content\Enums\TargetType;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Enums\ProgressStatus;
use Modules\Gamification\Enums\BadgeType;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;
use Modules\Gamification\Enums\ChallengeCriteriaType;
use Modules\Gamification\Enums\ChallengeType;
use Modules\Gamification\Enums\PointReason;
use Modules\Gamification\Enums\PointSourceType;
use Modules\Grading\Enums\GradeStatus;
use Modules\Grading\Enums\SourceType;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionStatus;
use Modules\Learning\Enums\SubmissionType;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Enums\NotificationFrequency;
use Modules\Notifications\Enums\NotificationType;
use Modules\Schemes\Enums\ContentType;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\LevelTag;
use Modules\Schemes\Enums\ProgressionMode;
use Spatie\Permission\Models\Role;

/**
 * @tags Data Master
 */
class MasterDataController extends Controller
{
  use ApiResponse;

  /**
   * Transform enum cases to value-label array
   */
  private function transformEnum(string $enumClass): array
  {
    return array_map(
      fn($case) => [
        "value" => $case->value,
        "label" => $case->label(),
      ],
      $enumClass::cases(),
    );
  }

  /**
   * Daftar Tipe Master Data
   *
   * Mengambil daftar semua tipe master data yang tersedia di sistem, termasuk tipe CRUD (database) dan Enum (read-only).
   *
   * @summary Daftar Tipe Master Data
   *
   * @response 200 scenario="Success" {"success":true,"message":"Daftar tipe master data","data":[{"key":"categories","label":"Kategori","type":"crud"},{"key":"tags","label":"Tags","type":"crud"},{"key":"user-status","label":"Status Pengguna","type":"enum"},{"key":"roles","label":"Peran","type":"enum"},{"key":"course-status","label":"Status Kursus","type":"enum"}]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function index(): JsonResponse
  {
    $types = [
      // Database-backed master data (CRUD)
      ["key" => "categories", "label" => "Kategori", "type" => "crud"],
      ["key" => "tags", "label" => "Tags", "type" => "crud"],
      // Enum-based master data (Read-only)
      ["key" => "user-status", "label" => "Status Pengguna", "type" => "enum"],
      ["key" => "roles", "label" => "Peran", "type" => "enum"],
      ["key" => "course-status", "label" => "Status Kursus", "type" => "enum"],
      ["key" => "course-types", "label" => "Tipe Kursus", "type" => "enum"],
      ["key" => "enrollment-types", "label" => "Tipe Pendaftaran", "type" => "enum"],
      ["key" => "level-tags", "label" => "Level Kesulitan", "type" => "enum"],
      ["key" => "progression-modes", "label" => "Mode Progres", "type" => "enum"],
      ["key" => "content-types", "label" => "Tipe Konten", "type" => "enum"],
      ["key" => "enrollment-status", "label" => "Status Pendaftaran", "type" => "enum"],
      ["key" => "progress-status", "label" => "Status Progres", "type" => "enum"],
      ["key" => "assignment-status", "label" => "Status Tugas", "type" => "enum"],
      ["key" => "submission-status", "label" => "Status Pengumpulan", "type" => "enum"],
      ["key" => "submission-types", "label" => "Tipe Pengumpulan", "type" => "enum"],
      ["key" => "content-status", "label" => "Status Konten", "type" => "enum"],
      ["key" => "priorities", "label" => "Prioritas", "type" => "enum"],
      ["key" => "target-types", "label" => "Tipe Target", "type" => "enum"],
      ["key" => "challenge-types", "label" => "Tipe Tantangan", "type" => "enum"],
      [
        "key" => "challenge-assignment-status",
        "label" => "Status Tantangan User",
        "type" => "enum",
      ],
      ["key" => "challenge-criteria-types", "label" => "Tipe Kriteria Tantangan", "type" => "enum"],
      ["key" => "badge-types", "label" => "Tipe Badge", "type" => "enum"],
      ["key" => "point-source-types", "label" => "Sumber Poin", "type" => "enum"],
      ["key" => "point-reasons", "label" => "Alasan Poin", "type" => "enum"],
      ["key" => "notification-types", "label" => "Tipe Notifikasi", "type" => "enum"],
      ["key" => "notification-channels", "label" => "Channel Notifikasi", "type" => "enum"],
      ["key" => "notification-frequencies", "label" => "Frekuensi Notifikasi", "type" => "enum"],
      ["key" => "grade-status", "label" => "Status Nilai", "type" => "enum"],
      ["key" => "grade-source-types", "label" => "Sumber Nilai", "type" => "enum"],
      ["key" => "category-status", "label" => "Status Kategori", "type" => "enum"],
      ["key" => "setting-types", "label" => "Tipe Pengaturan", "type" => "enum"],
    ];

    return $this->success($types, __("messages.master_data.types_retrieved"));
  }

  // ==================== AUTH ====================

  /**
   * Daftar Status Pengguna
   *
   * Mengambil enum values untuk status pengguna (pending, active, suspended, inactive).
   *
   * @summary Daftar Status Pengguna
   *
   * @response 200 scenario="Success" {"success":true,"message":"Daftar status pengguna","data":[{"value":"pending","label":"Pending"},{"value":"active","label":"Aktif"},{"value":"suspended","label":"Ditangguhkan"},{"value":"inactive","label":"Tidak Aktif"}]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function userStatuses(): JsonResponse
  {
    return $this->success($this->transformEnum(UserStatus::class), "Daftar status pengguna");
  }

  /**
   * Daftar Peran
   *
   * Mengambil daftar semua peran (roles) yang tersedia di sistem (Student, Instructor, Admin, Superadmin).
   *
   * @summary Daftar Peran
   *
   * @response 200 scenario="Success" {"success":true,"message":"Daftar peran","data":[{"value":"Student","label":"Siswa"},{"value":"Instructor","label":"Instruktur"},{"value":"Admin","label":"Admin"},{"value":"Superadmin","label":"Super Admin"}]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function roles(): JsonResponse
  {
    $roles = Role::all()
      ->map(
        fn($role) => [
          "value" => $role->name,
          "label" => __("enums.roles." . strtolower($role->name)),
        ],
      )
      ->toArray();

    return $this->success($roles, __("messages.master_data.roles_retrieved"));
  }

  // ==================== SCHEMES ====================

  /**
   * Daftar Status Kursus
   *
   * Mengambil enum values untuk status kursus (draft, published, archived, cancelled).
   *
   * @summary Daftar Status Kursus
   *
   * @response 200 scenario="Success" {"success":true,"message":"Daftar status kursus","data":[{"value":"draft","label":"Draft"},{"value":"published","label":"Dipublikasikan"},{"value":"archived","label":"Diarsipkan"},{"value":"cancelled","label":"Dibatalkan"}]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function courseStatuses(): JsonResponse
  {
    return $this->success($this->transformEnum(CourseStatus::class), "Daftar status kursus");
  }

  /**
   * Daftar Tipe Kursus
   *
   * Mengambil enum values untuk tipe kursus (scheme, course).
   *
   * @summary Daftar Tipe Kursus
   *
   * @response 200 scenario="Success" {"success":true,"message":"Daftar tipe kursus","data":[{"value":"scheme","label":"Skema Sertifikasi"},{"value":"course","label":"Kursus Mandiri"}]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function courseTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(CourseType::class), "Daftar tipe kursus");
  }

  /**
   * Daftar Tipe Pendaftaran
   *
   * Mengambil enum values untuk tipe enrollment kursus (open, key, invite_only, approval).
   *
   * @summary Daftar Tipe Pendaftaran
   *
   * @response 200 scenario="Success" {"success":true,"message":"Daftar tipe pendaftaran","data":[{"value":"open","label":"Terbuka untuk Semua"},{"value":"key","label":"Memerlukan Kunci Pendaftaran"},{"value":"invite_only","label":"Hanya dengan Undangan"},{"value":"approval","label":"Memerlukan Persetujuan"}]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function enrollmentTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(EnrollmentType::class), "Daftar tipe pendaftaran");
  }

  /**
   * Daftar Level Kesulitan
   *
   *
   * @summary Daftar Level Kesulitan
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function levelTags(): JsonResponse
  {
    return $this->success($this->transformEnum(LevelTag::class), "Daftar level kesulitan");
  }

  /**
   * Daftar Mode Progres
   *
   *
   * @summary Daftar Mode Progres
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function progressionModes(): JsonResponse
  {
    return $this->success($this->transformEnum(ProgressionMode::class), "Daftar mode progres");
  }

  /**
   * Daftar Tipe Konten Lesson
   *
   *
   * @summary Daftar Tipe Konten Lesson
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function contentTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(ContentType::class), "Daftar tipe konten");
  }

  // ==================== ENROLLMENTS ====================

  /**
   * Daftar Status Pendaftaran
   *
   *
   * @summary Daftar Status Pendaftaran
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function enrollmentStatuses(): JsonResponse
  {
    return $this->success(
      $this->transformEnum(EnrollmentStatus::class),
      "Daftar status pendaftaran",
    );
  }

  /**
   * Daftar Status Progres
   *
   *
   * @summary Daftar Status Progres
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function progressStatuses(): JsonResponse
  {
    return $this->success($this->transformEnum(ProgressStatus::class), "Daftar status progres");
  }

  // ==================== LEARNING ====================

  /**
   * Daftar Status Tugas
   *
   *
   * @summary Daftar Status Tugas
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function assignmentStatuses(): JsonResponse
  {
    return $this->success($this->transformEnum(AssignmentStatus::class), "Daftar status tugas");
  }

  /**
   * Daftar Status Pengumpulan
   *
   *
   * @summary Daftar Status Pengumpulan
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function submissionStatuses(): JsonResponse
  {
    return $this->success(
      $this->transformEnum(SubmissionStatus::class),
      "Daftar status pengumpulan",
    );
  }

  /**
   * Daftar Tipe Pengumpulan
   *
   *
   * @summary Daftar Tipe Pengumpulan
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function submissionTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(SubmissionType::class), "Daftar tipe pengumpulan");
  }

  // ==================== CONTENT ====================

  /**
   * Daftar Status Konten
   *
   *
   * @summary Daftar Status Konten
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function contentStatuses(): JsonResponse
  {
    return $this->success($this->transformEnum(ContentStatus::class), "Daftar status konten");
  }

  /**
   * Daftar Prioritas
   *
   *
   * @summary Daftar Prioritas
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function priorities(): JsonResponse
  {
    return $this->success($this->transformEnum(Priority::class), "Daftar prioritas");
  }

  /**
   * Daftar Tipe Target
   *
   *
   * @summary Daftar Tipe Target
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function targetTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(TargetType::class), "Daftar tipe target");
  }

  // ==================== GAMIFICATION ====================

  /**
   * Daftar Tipe Tantangan
   *
   *
   * @summary Daftar Tipe Tantangan
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function challengeTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(ChallengeType::class), "Daftar tipe tantangan");
  }

  /**
   * Daftar Status Tantangan User
   *
   *
   * @summary Daftar Status Tantangan User
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function challengeAssignmentStatuses(): JsonResponse
  {
    return $this->success(
      $this->transformEnum(ChallengeAssignmentStatus::class),
      "Daftar status tantangan user",
    );
  }

  /**
   * Daftar Kriteria Tantangan
   *
   *
   * @summary Daftar Kriteria Tantangan
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function challengeCriteriaTypes(): JsonResponse
  {
    return $this->success(
      $this->transformEnum(ChallengeCriteriaType::class),
      "Daftar tipe kriteria tantangan",
    );
  }

  /**
   * Daftar Tipe Badge
   *
   *
   * @summary Daftar Tipe Badge
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function badgeTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(BadgeType::class), "Daftar tipe badge");
  }

  /**
   * Daftar Sumber Poin
   *
   *
   * @summary Daftar Sumber Poin
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function pointSourceTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(PointSourceType::class), "Daftar sumber poin");
  }

  /**
   * Daftar Alasan Poin
   *
   *
   * @summary Daftar Alasan Poin
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function pointReasons(): JsonResponse
  {
    return $this->success($this->transformEnum(PointReason::class), "Daftar alasan poin");
  }

  // ==================== NOTIFICATIONS ====================

  /**
   * Daftar Tipe Notifikasi
   *
   *
   * @summary Daftar Tipe Notifikasi
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function notificationTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(NotificationType::class), "Daftar tipe notifikasi");
  }

  /**
   * Daftar Channel Notifikasi
   *
   *
   * @summary Daftar Channel Notifikasi
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function notificationChannels(): JsonResponse
  {
    return $this->success(
      $this->transformEnum(NotificationChannel::class),
      "Daftar channel notifikasi",
    );
  }

  /**
   * Daftar Frekuensi Notifikasi
   *
   *
   * @summary Daftar Frekuensi Notifikasi
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function notificationFrequencies(): JsonResponse
  {
    return $this->success(
      $this->transformEnum(NotificationFrequency::class),
      "Daftar frekuensi notifikasi",
    );
  }

  // ==================== GRADING ====================

  /**
   * Daftar Status Nilai
   *
   *
   * @summary Daftar Status Nilai
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function gradeStatuses(): JsonResponse
  {
    return $this->success($this->transformEnum(GradeStatus::class), "Daftar status nilai");
  }

  /**
   * Daftar Sumber Nilai
   *
   *
   * @summary Daftar Sumber Nilai
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function gradeSourceTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(SourceType::class), "Daftar sumber nilai");
  }

  // ==================== COMMON ====================

  /**
   * Daftar Status Kategori
   *
   *
   * @summary Daftar Status Kategori
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function categoryStatuses(): JsonResponse
  {
    return $this->success($this->transformEnum(CategoryStatus::class), "Daftar status kategori");
  }

  /**
   * Daftar Tipe Pengaturan
   *
   *
   * @summary Daftar Tipe Pengaturan
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example MasterData"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function settingTypes(): JsonResponse
  {
    return $this->success($this->transformEnum(SettingType::class), "Daftar tipe pengaturan");
  }
}
