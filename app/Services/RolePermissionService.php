<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionService
{
    public const GUARD = 'api';

    public function syncDefault(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = $this->getPermissionsByModule();

        $allPermissions = [];
        foreach ($modules as $module => $permissions) {
            foreach ($permissions as $permission) {
                $allPermissions[] = $permission;
                Permission::query()->firstOrCreate(
                    ['name' => $permission, 'guard_name' => self::GUARD]
                );
            }
        }

        $admin = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => self::GUARD]);
        $instructor = Role::query()->firstOrCreate(['name' => 'Instructor', 'guard_name' => self::GUARD]);
        $student = Role::query()->firstOrCreate(['name' => 'Student', 'guard_name' => self::GUARD]);

        $admin->syncPermissions($allPermissions);

        $instructorPermissions = array_values(array_unique(array_merge(
            $modules['learning'],
            $modules['question_bank'],
            [
                'grading.tasks.score',
                'grading.quizzes.score',
                'grading.view',
                'profiles.edit',
                'profiles.view',
                'news.view',
                'notifications.view',
            ],
        )));
        $instructor->syncPermissions($instructorPermissions);

        $studentPermissions = [
            'auth.register',
            'auth.login',
            'enrollments.register',
            'search.schemes',
            'learning.materials.reading.view',
            'learning.materials.video.view',
            'learning.tasks.view',
            'learning.tasks.submit',
            'learning.forum.view',
            'learning.forum.create',
            'learning.forum.reply',
            'assessments.take',
            'gamification.view',
            'gamification.leaderboard.view',
            'profiles.view',
            'profiles.edit',
            'news.view',
            'notifications.view',
        ];
        $student->syncPermissions($studentPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function getPermissionsByModule(): array
    {
        return [
            'auth' => [
                'auth.login',
                'auth.register',
            ],
            'operations' => [
                'operations.users.view',
                'operations.users.manage',
            ],
            'enrollments' => [
                'enrollments.classes.manage',
                'enrollments.assessments.manage',
                'enrollments.register',
            ],
            'schemes' => [
                'schemes.view',
                'schemes.manage',
                'search.schemes',
            ],
            'units' => [
                'units.view',
                'units.manage',
                'elements.view',
                'elements.manage',
            ],
            'learning' => [
                'learning.materials.reading.view',
                'learning.materials.reading.create',
                'learning.materials.reading.edit',
                'learning.materials.reading.delete',
                'learning.materials.video.view',
                'learning.materials.video.create',
                'learning.materials.video.edit',
                'learning.materials.video.delete',
                'learning.tasks.view',
                'learning.tasks.create',
                'learning.tasks.edit',
                'learning.tasks.delete',
                'learning.tasks.submit',
                'learning.forum.view',
                'learning.forum.create',
                'learning.forum.reply',
                'learning.forum.moderate',
            ],
            'question_bank' => [
                'question_bank.multiple_choice.manage',
                'question_bank.free_text.manage',
                'question_bank.file_upload.manage',
                'question_bank.answers.manage',
                'question_bank.answers.key.manage',
            ],
            'assessments' => [
                'assessments.manage',
                'assessments.take',
                'assessments.answers.manage',
            ],
            'grading' => [
                'grading.view',
                'grading.tasks.score',
                'grading.quizzes.score',
            ],
            'gamification' => [
                'gamification.view',
                'gamification.points.manage',
                'gamification.badges.manage',
                'gamification.levels.manage',
                'gamification.leaderboard.view',
            ],
            'news' => [
                'news.view',
                'news.manage',
            ],
            'notifications' => [
                'notifications.view',
                'notifications.manage',
            ],
            'profiles' => [
                'profiles.view',
                'profiles.edit',
            ],
        ];
    }
}
