<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder for creating test roles and permissions.
 *
 * This seeder is specifically designed for testing environments
 * to ensure consistent role and permission setup across tests.
 */
class TestRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = 'api';

        // Create roles
        $superadmin = Role::firstOrCreate([
            'name' => 'Superadmin',
            'guard_name' => $guard,
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => $guard,
        ]);

        $instructor = Role::firstOrCreate([
            'name' => 'Instructor',
            'guard_name' => $guard,
        ]);

        $student = Role::firstOrCreate([
            'name' => 'Student',
            'guard_name' => $guard,
        ]);

        // Create basic permissions
        $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Course management
            'courses.view',
            'courses.create',
            'courses.update',
            'courses.delete',
            'courses.publish',

            // Enrollment management
            'enrollments.view',
            'enrollments.create',
            'enrollments.update',
            'enrollments.delete',

            // Assignment management
            'assignments.view',
            'assignments.create',
            'assignments.update',
            'assignments.delete',
            'assignments.grade',

            // Content management
            'content.view',
            'content.create',
            'content.update',
            'content.delete',
            'content.publish',

            // Forum management
            'forums.view',
            'forums.create',
            'forums.update',
            'forums.delete',
            'forums.moderate',

            // Grading
            'grades.view',
            'grades.create',
            'grades.update',

            // Reports
            'reports.view',
            'reports.generate',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        // Assign permissions to roles

        // Superadmin gets all permissions
        $superadmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // Admin gets most permissions except some superadmin-only ones
        $admin->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'courses.view',
            'courses.create',
            'courses.update',
            'courses.delete',
            'courses.publish',
            'enrollments.view',
            'enrollments.create',
            'enrollments.update',
            'enrollments.delete',
            'assignments.view',
            'assignments.create',
            'assignments.update',
            'assignments.delete',
            'assignments.grade',
            'content.view',
            'content.create',
            'content.update',
            'content.delete',
            'content.publish',
            'forums.view',
            'forums.create',
            'forums.update',
            'forums.delete',
            'forums.moderate',
            'grades.view',
            'grades.create',
            'grades.update',
            'reports.view',
            'reports.generate',
        ]);

        // Instructor gets course and assignment management permissions
        $instructor->syncPermissions([
            'courses.view',
            'courses.create',
            'courses.update',
            'courses.publish',
            'enrollments.view',
            'assignments.view',
            'assignments.create',
            'assignments.update',
            'assignments.delete',
            'assignments.grade',
            'content.view',
            'content.create',
            'content.update',
            'forums.view',
            'forums.create',
            'forums.update',
            'forums.moderate',
            'grades.view',
            'grades.create',
            'grades.update',
            'reports.view',
        ]);

        // Student gets basic view and participation permissions
        $student->syncPermissions([
            'courses.view',
            'enrollments.view',
            'enrollments.create',
            'assignments.view',
            'content.view',
            'forums.view',
            'forums.create',
            'forums.update',
            'grades.view',
        ]);
    }
}
