<?php

namespace App\Providers;

use App\Support\LoadtestBypass;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \Modules\Schemes\Models\Course::class => \Modules\Schemes\Policies\CoursePolicy::class,
        \Modules\Schemes\Models\Unit::class => \Modules\Schemes\Policies\UnitPolicy::class,
        \Modules\Schemes\Models\Lesson::class => \Modules\Schemes\Policies\LessonPolicy::class,
        \Modules\Schemes\Models\Tag::class => \Modules\Schemes\Policies\TagPolicy::class,
        \Modules\Grading\Models\Grade::class => \Modules\Grading\Policies\GradePolicy::class,
        User::class => \Modules\Auth\Policies\UserPolicy::class,
        \Modules\Notifications\app\Models\Post::class => \Modules\Notifications\Policies\PostPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, ?string $ability = null) {
            if (LoadtestBypass::isEnabled(request())) {
                return true;
            }

            if ($user->hasRole('Superadmin')) {
                return true;
            }

            if ($user->hasRole('Admin') || $user->hasRole('Admin')) {
                return null;
            }

            return null;
        });

        
        Gate::define('manage-gamification', function (User $user) {
            return $user->hasRole('Superadmin') || $user->hasRole('Admin');
        });
    }
}
