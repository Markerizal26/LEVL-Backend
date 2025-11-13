<?php

namespace Modules\Schemes\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class CourseCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Course $course,
        public Enrollment $enrollment
    ) {}
}

