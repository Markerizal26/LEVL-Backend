<?php

namespace Modules\Schemes\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Schemes\Entities\Course;

class CourseDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Course $course;

    public function __construct(Course $course)
    {
        $this->course = $course;
    }
}
