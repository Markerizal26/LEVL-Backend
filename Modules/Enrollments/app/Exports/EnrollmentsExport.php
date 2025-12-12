<?php

namespace Modules\Enrollments\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class EnrollmentsExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Student Name',
            'Email',
            'Status',
            'Progress %',
            'Enrolled At',
            'Completed At',
        ];
    }

    /**
     * @param mixed $enrollment
     * @return array
     */
    public function map($enrollment): array
    {
        $progress = $enrollment->courseProgress;

        return [
            $enrollment->id,
            $enrollment->user?->name ?? 'N/A',
            $enrollment->user?->email ?? 'N/A',
            $enrollment->status,
            $progress?->progress_percent ?? 0,
            $enrollment->enrolled_at?->toDateTimeString(),
            $enrollment->completed_at?->toDateTimeString() ?? 'N/A',
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Enrollments';
    }
}
