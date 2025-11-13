<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \Modules\Schemes\Models\Course::class => \App\Policies\CoursePolicy::class,
        \Modules\Schemes\Models\Unit::class => \App\Policies\UnitPolicy::class,
        \Modules\Schemes\Models\Lesson::class => \App\Policies\LessonPolicy::class,
        \Modules\Learning\Models\Assignment::class => \App\Policies\AssignmentPolicy::class,
        \Modules\Grading\Models\Grade::class => \App\Policies\GradePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, ?string $ability = null) {

            if ($user->hasRole('superadmin')) {
                return true;
            }

            if ($user->hasRole('admin') || $user->hasRole('Admin')) {
                return null;
            }

            return null;
        });
    }
}
