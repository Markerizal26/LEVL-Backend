<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;

class ActivityLogRepository extends BaseRepository
{
  /**
   * Allowed filter keys.
   *
   * @var array<int, string>
   */
  protected array $allowedFilters = [
    "log_name",
    "event",
    "subject_type",
    "subject_id",
    "causer_type",
    "causer_id",
    "device_type",
    "browser",
    "platform",
    "ip_address",
  ];

  /**
   * Allowed sort fields.
   *
   * @var array<int, string>
   */
  protected array $allowedSorts = ["id", "created_at", "event", "log_name"];

  /**
   * Default sort field.
   */
  protected string $defaultSort = "-created_at";

  /**
   * Default relations to load.
   *
   * @var array<int, string>
   */
  protected array $with = ["causer", "subject"];

  protected function model(): string
  {
    return ActivityLog::class;
  }

  /**
   * Get paginated activity logs.
   *
   * Supports:
   * - filter[log_name], filter[event], filter[subject_type], filter[subject_id]
   * - filter[causer_type], filter[causer_id]
   * - filter[device_type], filter[ip_address], filter[browser], filter[platform]
   * - sort: id, created_at, event, log_name (prefix with - for desc)
   */
    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
      $request = new Request($params);
      $search = trim((string) ($params['search'] ?? ''));

      $query = $this->query()->with(['causer' => function ($morphTo) {
        $morphTo->morphWith([
          \Modules\Auth\Models\User::class => ['latestActivity'],
        ]);
      }]);

      if ($search !== '') {
        $query->search($search);
      }

      $allowedFilters = array_merge(
        $this->allowedFilters,
        [
          AllowedFilter::callback('created_at', function ($builder, $value) {
            $from = Arr::get($value, 'from');
            $to = Arr::get($value, 'to');

            if ($from) {
              $builder->whereDate('created_at', '>=', Carbon::parse($from)->startOfDay());
            }

            if ($to) {
              $builder->whereDate('created_at', '<=', Carbon::parse($to)->endOfDay());
            }
          }),
        ],
      );

      return \Spatie\QueryBuilder\QueryBuilder::for($query, $request)
        ->allowedFilters($allowedFilters)
        ->allowedSorts($this->allowedSorts)
        ->defaultSort($this->defaultSort)
        ->paginate($perPage);
    }

  /**
   * Find activity log by ID.
   */
  public function find(int $id): ?ActivityLog
  {
    return $this->query()->find($id);
  }
}
