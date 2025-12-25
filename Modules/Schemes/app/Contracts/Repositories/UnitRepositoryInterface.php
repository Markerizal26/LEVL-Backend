<?php

namespace Modules\Schemes\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Schemes\Models\Unit;

interface UnitRepositoryInterface
{
    /**
     * Find units by course ID with pagination.
     * Uses custom QueryFilter for filter/sort from params or request.
     *
     * @param  int  $courseId  Course ID
     * @param  array  $params  Filter/sort parameters
     * @param  int  $perPage  Items per page
     */
    public function findByCourse(int $courseId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a unit by course ID and unit ID.
     *
     * @param  int  $courseId  Course ID
     * @param  int  $id  Unit ID
     */
    public function findByCourseAndId(int $courseId, int $id): ?Unit;

    /**
     * Get the maximum order value for units in a course.
     *
     * @param  int  $courseId  Course ID
     */
    public function getMaxOrderForCourse(int $courseId): int;

    /**
     * Reorder units within a course.
     *
     * @param  int  $courseId  Course ID
     * @param  array  $unitOrders  Array of unit ID => order mappings
     */
    public function reorderUnits(int $courseId, array $unitOrders): void;

    /**
     * Get all units for a course.
     *
     * @param  int  $courseId  Course ID
     */
    public function getAllByCourse(int $courseId): Collection;

    /**
     * Update order for a specific unit.
     *
     * @param  int  $unitId  Unit ID
     * @param  int  $order  New order value
     */
    public function updateOrder(int $unitId, int $order): bool;
}
