<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'users.create',
            'users.read',
            'users.update',
            'users.delete',
            'users.assign-admin',
            
            // Course management
            'courses.create',
            'courses.read',
            'courses.update',
            'courses.delete',
            'courses.publish',
            'courses.assign-admin',
            'courses.assign-instructor',
            
            // Content management
            'units.create',
            'units.read',
            'units.update',
            'units.delete',
            'lessons.create',
            'lessons.read',
            'lessons.update',
            'lessons.delete',
            
            // Enrollment management
            'enrollments.create',
            'enrollments.read',
            'enrollments.update',
            'enrollments.delete',
            
            // Assessment management
            'assessments.create',
            'assessments.read',
            'assessments.update',
            'assessments.delete',
            'assessments.grade',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        // Create roles
        $superadmin = Role::firstOrCreate([
            'name' => 'Superadmin',
            'guard_name' => 'api',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'api',
        ]);

        $instructor = Role::firstOrCreate([
            'name' => 'Instructor',
            'guard_name' => 'api',
        ]);

        $student = Role::firstOrCreate([
            'name' => 'Student',
            'guard_name' => 'api',
        ]);

        // Assign all permissions to superadmin
        $superadmin->givePermissionTo(Permission::all());

        // Assign course management permissions to admin (will be scoped per course)
        $admin->givePermissionTo([
            'courses.read',
            'courses.update',
            'courses.publish',
            'courses.assign-admin',
            'courses.assign-instructor',
            'units.create',
            'units.read',
            'units.update',
            'units.delete',
            'lessons.create',
            'lessons.read',
            'lessons.update',
            'lessons.delete',
            'enrollments.create',
            'enrollments.read',
            'enrollments.update',
            'enrollments.delete',
        ]);

        // Assign content permissions to instructor
        $instructor->givePermissionTo([
            'courses.read',
            'units.read',
            'units.update',
            'lessons.read',
            'lessons.update',
            'enrollments.read',
            'assessments.create',
            'assessments.read',
            'assessments.update',
            'assessments.grade',
        ]);

        // Assign read-only permissions to student
        $student->givePermissionTo([
            'courses.read',
            'units.read',
            'lessons.read',
            'enrollments.read',
            'assessments.read',
        ]);
    }
}

