<?php

namespace App\Services;

use App\Models\MasterDataItem;
use App\Repositories\MasterDataRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;
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

class MasterDataService
{
  public function __construct(private readonly MasterDataRepository $repository) {}

  /**
   * Get all master data types.
   */
  public function getTypes(array $params = []): SupportCollection
  {
    // CRUD / database-backed types
    $crudTypes = $this->repository->getTypes($params);

    // Make sure we don't duplicate keys when merging with enum-based types
    $existingKeys = $crudTypes->pluck("key")->all();

    // Enum-based (read-only) master data types
    $enumTypes = $this->getEnumTypes()->reject(
      fn(array $type) => in_array($type["key"], $existingKeys, true)
    );

    return $crudTypes->concat($enumTypes)->values();
  }

  /**
   * Get all master data types with pagination.
   */
  public function getTypesPaginated(array $params = [], int $perPage = 15): LengthAwarePaginator
  {
    $types = $this->getTypes($params);
    
    // Get page from params or request
    $page = max(1, (int) ($params['page'] ?? request()->get('page', 1)));
    
    // Use Laravel's Paginator to create a LengthAwarePaginator from Collection
    return new \Illuminate\Pagination\LengthAwarePaginator(
      $types->forPage($page, $perPage)->values(),
      $types->count(),
      $perPage,
      $page,
      ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
    );
  }

  /**
   * Get enum-based master data types configuration.
   * This method centralizes all enum type definitions.
   */
  private function getEnumTypes(): SupportCollection
  {
    return collect([
      [
        "key" => "user-status",
        "label" => "Status Pengguna",
        "count" => count(UserStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "roles",
        "label" => "Peran",
        "count" => Role::count(),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "course-status",
        "label" => "Status Kursus",
        "count" => count(CourseStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "course-types",
        "label" => "Tipe Kursus",
        "count" => count(CourseType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "enrollment-types",
        "label" => "Tipe Pendaftaran",
        "count" => count(EnrollmentType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "level-tags",
        "label" => "Level Kesulitan",
        "count" => count(LevelTag::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "progression-modes",
        "label" => "Mode Progres",
        "count" => count(ProgressionMode::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "content-types",
        "label" => "Tipe Konten",
        "count" => count(ContentType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "enrollment-status",
        "label" => "Status Pendaftaran",
        "count" => count(EnrollmentStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "progress-status",
        "label" => "Status Progres",
        "count" => count(ProgressStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "assignment-status",
        "label" => "Status Tugas",
        "count" => count(AssignmentStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "submission-status",
        "label" => "Status Pengumpulan",
        "count" => count(SubmissionStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "submission-types",
        "label" => "Tipe Pengumpulan",
        "count" => count(SubmissionType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "content-status",
        "label" => "Status Konten",
        "count" => count(ContentStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "priorities",
        "label" => "Prioritas",
        "count" => count(Priority::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "target-types",
        "label" => "Tipe Target",
        "count" => count(TargetType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "challenge-types",
        "label" => "Tipe Tantangan",
        "count" => count(ChallengeType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "challenge-assignment-status",
        "label" => "Status Tantangan User",
        "count" => count(ChallengeAssignmentStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "challenge-criteria-types",
        "label" => "Tipe Kriteria Tantangan",
        "count" => count(ChallengeCriteriaType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "badge-types",
        "label" => "Tipe Badge",
        "count" => count(BadgeType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "point-source-types",
        "label" => "Sumber Poin",
        "count" => count(PointSourceType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "point-reasons",
        "label" => "Alasan Poin",
        "count" => count(PointReason::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "notification-types",
        "label" => "Tipe Notifikasi",
        "count" => count(NotificationType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "notification-channels",
        "label" => "Channel Notifikasi",
        "count" => count(NotificationChannel::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "notification-frequencies",
        "label" => "Frekuensi Notifikasi",
        "count" => count(NotificationFrequency::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "grade-status",
        "label" => "Status Nilai",
        "count" => count(GradeStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "grade-source-types",
        "label" => "Sumber Nilai",
        "count" => count(SourceType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "category-status",
        "label" => "Status Kategori",
        "count" => count(CategoryStatus::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
      [
        "key" => "setting-types",
        "label" => "Tipe Pengaturan",
        "count" => count(SettingType::cases()),
        "last_updated" => null,
        "is_crud" => false,
      ],
    ]);
  }

  /**
   * List master data by type with pagination.
   */
  public function paginate(string $type, int $perPage = 15): LengthAwarePaginator
  {
    return $this->repository->paginate($type, $perPage);
  }

  /**
   * Get all master data by type (no pagination).
   */
  public function all(string $type): Collection
  {
    return $this->repository->all($type);
  }

  /**
   * Find a master data item by type and id.
   */
  public function find(string $type, int $id): ?MasterDataItem
  {
    return $this->repository->find($type, $id);
  }

  /**
   * Create a new master data item.
   */
  public function create(string $type, array $data): MasterDataItem
  {
    $item = $this->repository->create([
      "type" => $type,
      "value" => $data["value"],
      "label" => $data["label"],
      "metadata" => $data["metadata"] ?? null,
      "is_system" => false,
      "is_active" => $data["is_active"] ?? true,
      "sort_order" => $data["sort_order"] ?? 0,
    ]);

    Log::info('Master data item created', [
      'type' => $type,
      'id' => $item->id,
      'value' => $item->value,
      'label' => $item->label,
    ]);

    return $item;
  }

  /**
   * Update a master data item.
   */
  public function update(string $type, int $id, array $data): ?MasterDataItem
  {
    $item = $this->repository->find($type, $id);
    if (!$item) {
      return null;
    }

    $oldData = $item->only(['value', 'label', 'is_active', 'sort_order']);

    // System items: don't allow changing value
    if ($item->is_system && isset($data["value"])) {
      unset($data["value"]);
    }

    $updatedItem = $this->repository->update($item, $data);

    Log::info('Master data item updated', [
      'type' => $type,
      'id' => $id,
      'old_data' => $oldData,
      'new_data' => $updatedItem->only(['value', 'label', 'is_active', 'sort_order']),
    ]);

    return $updatedItem;
  }

  /**
   * Delete a master data item.
   */
  public function delete(string $type, int $id): bool|string
  {
    $item = $this->repository->find($type, $id);
    if (!$item) {
      return "not_found";
    }

    if ($item->is_system) {
      return "system_protected";
    }

    $itemData = $item->only(['value', 'label']);
    $result = $this->repository->delete($item);

    if ($result === true) {
      Log::info('Master data item deleted', [
        'type' => $type,
        'id' => $id,
        'deleted_data' => $itemData,
      ]);
    }

    return $result;
  }

  /**
   * Check if value already exists.
   */
  public function valueExists(string $type, string $value, ?int $excludeId = null): bool
  {
    return $this->repository->valueExists($type, $value, $excludeId);
  }
}
