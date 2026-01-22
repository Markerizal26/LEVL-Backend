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
        \Modules\Schemes\Models\Tag::class => \App\Policies\TagPolicy::class,
        \Modules\Learning\Models\Assignment::class => \App\Policies\AssignmentPolicy::class,
        \Modules\Learning\Models\Submission::class => \App\Policies\SubmissionPolicy::class,
        \Modules\Grading\Models\Grade::class => \App\Policies\GradePolicy::class,
        \Modules\Auth\Models\User::class => \Modules\Auth\Policies\UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, ?string $ability = null) {

            if ($user->hasRole('Superadmin')) {
                return true;
            }

            if ($user->hasRole('Admin') || $user->hasRole('Admin')) {
                return null;
            }

            return null;
        });
    }
}
