<?php

namespace Modules\Enrollments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Enrollments\Models\Enrollment;

class EnrollmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Enrollment $enrollment
    ) {}
}

