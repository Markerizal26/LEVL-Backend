<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \Modules\Schemes\Entities\Course::class => \App\Policies\CoursePolicy::class,
        \Modules\Schemes\Entities\Lesson::class => \App\Policies\LessonPolicy::class,
        \Modules\Learning\Entities\Assignment::class => \App\Policies\AssignmentPolicy::class,
        \Modules\Grading\Entities\Grade::class => \App\Policies\GradePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability = null) {
            return $user->hasRole('Admin') ? true : null;
        });
    }
}


