<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔐 Creating roles and permissions...');
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // User management
            'users.create', 'users.read', 'users.update', 'users.delete', 'users.assign-admin',
            // Course management
            'courses.create', 'courses.read', 'courses.update', 'courses.delete', 'courses.publish',
            'courses.assign-admin', 'courses.assign-instructor',
            // Content management
            'units.create', 'units.read', 'units.update', 'units.delete',
            'lessons.create', 'lessons.read', 'lessons.update', 'lessons.delete',
            'lesson-blocks.create', 'lesson-blocks.read', 'lesson-blocks.update', 'lesson-blocks.delete',
            // Enrollment management
            'enrollments.create', 'enrollments.read', 'enrollments.update', 'enrollments.delete',
            // Grading management
            'grades.create', 'grades.read', 'grades.update', 'grades.delete',
            'grades.approve', 'grades.review',
            // Assignment management
            'assignments.create', 'assignments.read', 'assignments.update', 'assignments.delete',
            'submissions.create', 'submissions.read', 'submissions.update', 'submissions.delete',
        ];

        $this->command->info("  📝 Creating " . count($permissions) . " permissions...");
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
        
        $this->command->info("  ✅ " . count($permissions) . " permissions created");

        $this->command->info("\n  👥 Creating roles...");
        
        $superadmin = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'api']);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'api']);
        $instructor = Role::firstOrCreate(['name' => 'Instructor', 'guard_name' => 'api']);
        $student = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'api']);
        
        $this->command->info("  ✅ 4 roles created");
        $this->command->info("\n  🔗 Assigning permissions to roles...");

        $allPermissions = Permission::all();
        $superadmin->syncPermissions($allPermissions);
        $this->command->info("    ✓ Superadmin: " . $allPermissions->count() . " permissions (all)");

        $adminPerms = [
            'courses.read', 'courses.update', 'courses.publish', 'courses.assign-admin', 'courses.assign-instructor',
            'units.create', 'units.read', 'units.update', 'units.delete',
            'lessons.create', 'lessons.read', 'lessons.update', 'lessons.delete',
            'lesson-blocks.create', 'lesson-blocks.read', 'lesson-blocks.update', 'lesson-blocks.delete',
            'enrollments.create', 'enrollments.read', 'enrollments.update', 'enrollments.delete',
            'grades.read', 'grades.review', 'grades.approve',
            'assignments.read', 'assignments.update', 'submissions.read',
        ];
        $admin->syncPermissions($adminPerms);
        $this->command->info("    ✓ Admin: " . count($adminPerms) . " permissions");

        $instructorPerms = [
            'courses.read', 'units.read', 'units.update',
            'lessons.read', 'lessons.update', 'lesson-blocks.read', 'lesson-blocks.update',
            'enrollments.read', 'grades.create', 'grades.read', 'grades.update',
            'assignments.create', 'assignments.read', 'assignments.update',
            'submissions.read', 'submissions.update',
        ];
        $instructor->syncPermissions($instructorPerms);
        $this->command->info("    ✓ Instructor: " . count($instructorPerms) . " permissions");

        $studentPerms = [
            'courses.read', 'units.read', 'lessons.read', 'lesson-blocks.read',
            'enrollments.read', 'submissions.create', 'submissions.read', 'submissions.update',
        ];
        $student->syncPermissions($studentPerms);
        $this->command->info("    ✓ Student: " . count($studentPerms) . " permissions");
        
        $this->command->info("\n✅ Roles and permissions setup completed!");
    }
}
