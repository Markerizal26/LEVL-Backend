# Auth Module Seeders - Comprehensive Documentation

## 📚 Overview

The Auth module seeders provide comprehensive test data for the authentication system, including:
- **1,000+ users** with various roles and statuses
- **Multi-device support** with JWT refresh tokens
- **Privacy settings** for all users
- **Activity tracking** with login and user activities
- **OTP codes** for verification flows
- **Audit logs** for profile changes

## 🚀 Quick Start

### Run All Seeders
```bash
# Run the comprehensive seeder with all data
php artisan db:seed --class="Modules\Auth\Database\Seeders\AuthComprehensiveDataSeeder"

# Or run the main Auth seeder
php artisan db:seed --class="Modules\Auth\Database\Seeders\AuthDatabaseSeeder"

# Run with specific environment
php artisan db:seed --class="Modules\Auth\Database\Seeders\AuthDatabaseSeeder" --env=testing
```

### Run Individual Seeders
```bash
# Seeds only
php artisan db:seed --class="Modules\Auth\Database\Seeders\UserSeeder"
php artisan db:seed --class="Modules\Auth\Database\Seeders\JwtRefreshTokenSeeder"
php artisan db:seed --class="Modules\Auth\Database\Seeders\OtpCodeSeeder"
php artisan db:seed --class="Modules\Auth\Database\Seeders\PasswordResetTokenSeeder"
php artisan db:seed --class="Modules\Auth\Database\Seeders\ProfileAuditLogSeeder"
php artisan db:seed --class="Modules\Auth\Database\Seeders\ProfilePrivacySettingSeeder"
```

## 📊 Data Distribution

### Users by Role (1,000 total)
```
Superadmin:  50 users (5%)
Admin:      100 users (10%)
Instructor: 200 users (20%)
Student:    650 users (65%)
```

### Users by Status (per role)
```
Active:     70% (700 users)
Pending:    15% (150 users)
Inactive:   10% (100 users)
Banned:      5% (50 users)
```

### Demo Accounts Created
| Email | Username | Role | Status | Password |
|-------|----------|------|--------|----------|
| superadmin@test.com | superadmin | Superadmin | Active |   |
| admin@test.com | admin | Admin | Active | password |
| instructor@test.com | instructor | Instructor | Active | password |
| student@test.com | student | Student | Active | password |
| student_pending@test.com | student_pending | Student | Pending | password |
| student_inactive@test.com | student_inactive | Student | Inactive | password |

## 📈 Generated Data Summary

| Data Type | Quantity | Details |
|-----------|----------|---------|
| Users | 1,000+ | 6 demo + 994+ random |
| Privacy Settings | 1,000+ | All users |
| Login Activities | 2,500+ | Average 2-3 per active user |
| User Activities | 5,000+ | Average 5-20 per active user |
| JWT Tokens | 2,000+ | 2-5 per active user |
| OTP Codes | 100+ | Verification & reset flows |
| Password Tokens | 15+ | Forgot password flows |
| Audit Logs | 3,000+ | Profile changes (5-10 per user) |

## 🔧 Seeder Classes Overview

### RolePermissionSeeder
Creates roles and permissions:
- **Roles**: Superadmin, Admin, Instructor, Student
- **Permissions**: User management, course management, content management, enrollment management

### UserSeeder
Generates 1,000 users with:
- All status types (Active, Pending, Inactive, Banned)
- Complete profile data (name, email, phone, bio)
- Varied profile completion levels
- Proper email verification states
- Demo users for testing
- Related data: login activities, user activities

### ProfilePrivacySettingSeeder
Creates privacy settings for users:
- **Visibility levels**: public, friends_only, private
- **Field visibility**: email, phone, activity history, achievements, statistics
- Status-based configurations (banned users always private)

### JwtRefreshTokenSeeder
Generates JWT refresh tokens:
- **Active users**: 2-5 valid tokens (multi-device support)
- **Pending users**: 0 tokens
- **Inactive users**: 1 revoked token
- **Banned users**: 1 revoked token

Token properties:
- Device ID hashing
- Proper TTL management (14 days idle, 90 days absolute)
- Revocation states

### OtpCodeSeeder
Creates OTP codes for various flows:
- **Email verification**: For all pending users
- **Password reset**: For 20% of active users
- **Email change**: For 5% of active users
- **Account deletion**: For 2% of active users

### PasswordResetTokenSeeder
Generates password reset tokens:
- Expired tokens (for testing)
- Valid tokens (for testing)
- Hashed token values

### ProfileAuditLogSeeder
Creates audit logs for profile changes:
- 1-10 logs per active user (500 users sampled)
- Actions: profile update, avatar upload/delete, email change, password change, privacy settings update
- Change tracking with before/after values
- IP and user agent tracking

### ProfileSeeder
Ensures complete profile data:
- Creates missing privacy settings
- Generates user activities
- Activity-based on user role and status

## 🧪 Testing with Seeders

### Test Login Flows
```bash
# Login as Superadmin
POST /api/v1/auth/login
{
  "login": "superadmin",
  "password": "password"
}

# Login as Student
POST /api/v1/auth/login
{
  "login": "student",
  "password": "password"
}
```

### Test Email Verification
```bash
# Get pending user's OTP
SELECT * FROM otp_codes 
WHERE purpose = 'register_verification' AND consumed_at IS NULL
LIMIT 1;

# Verify email
POST /api/v1/auth/email/verify
{
  "token": "{token_from_meta.token_hash}",
  "uuid": "{uuid}"
}
```

### Test Multi-Device Support
```bash
# Create refresh token from different device
POST /api/v1/auth/refresh
{
  "refresh_token": "{token_from_login_1}"
}

# Then from another device
POST /api/v1/auth/refresh
{
  "refresh_token": "{token_from_login_2}"
}
```

### Test Privacy Settings
```bash
# Get user with private profile
SELECT * FROM users WHERE id = (
  SELECT user_id FROM profile_privacy_settings 
  WHERE profile_visibility = 'private'
  LIMIT 1
);

# Try to view their profile as another user
GET /api/v1/users/{private_user_id}/profile
```

## 🔄 Seeding Order and Dependencies

```
RolePermissionSeeder (must run first)
    ↓
UserSeeder (creates base users and related data)
    ↓
├─ ProfilePrivacySettingSeeder (privacy settings)
├─ ProfileSeeder (user activities)
├─ JwtRefreshTokenSeeder (tokens)
├─ OtpCodeSeeder (verification codes)
├─ PasswordResetTokenSeeder (password reset)
└─ ProfileAuditLogSeeder (audit logs)
```

## 🎯 Use Cases

### Development
Use full seeding for:
- Local development with realistic data
- Testing filtering and search
- Testing role-based access control
- Testing privacy settings
- Performance testing with large datasets

### Testing
Use specific seeders for:
- Unit tests with controlled data
- Feature tests with fixtures
- Integration tests with complete flows

### Staging
Run full seeding before:
- User acceptance testing
- Performance testing
- Load testing

## 📝 Customization

### Modify User Count
Edit `UserSeeder.php` `run()` method:
```php
// Change these numbers
$this->createUsersByRole('Superadmin', 50);  // Change 50
$this->createUsersByRole('Admin', 100);      // Change 100
$this->createUsersByRole('Instructor', 200); // Change 200
$this->createUsersByRole('Student', 650);    // Change 650
```

### Modify Status Distribution
Edit `createUsersByRole()` method:
```php
$activeCount = (int) ($count * 0.7);    // Change 0.7 (70%)
$pendingCount = (int) ($count * 0.15);  // Change 0.15 (15%)
$inactiveCount = (int) ($count * 0.1);  // Change 0.1 (10%)
$bannedCount = $count - ...;             // Rest becomes banned
```

### Customize Demo Users
Edit `createDemoUsers()` method in `UserSeeder.php`:
```php
$demoUsers = [
    [
        'name' => 'Your Name',
        'username' => 'your_username',
        'email' => 'your_email@test.com',
        'role' => 'Student',
        'status' => UserStatus::Active,
        'verified' => true,
    ],
    // Add more...
];
```

## 🐛 Troubleshooting

### Foreign Key Constraint Error
```bash
# Disable foreign key checks before seeding
php artisan tinker
>>> DB::statement('SET FOREIGN_KEY_CHECKS=0')
>>> exit
php artisan db:seed
>>> DB::statement('SET FOREIGN_KEY_CHECKS=1')
```

### Memory Issues with Large Data
```bash
# Increase PHP memory limit
php -d memory_limit=512M artisan db:seed

# Or use chunking in seeder
User::chunk(500, function($users) {
    // Process in chunks
});
```

### Duplicate Entry Error
```bash
# Fresh database before seeding
php artisan migrate:fresh
php artisan db:seed
```

## 📚 Related Documentation

- [Auth Module API Documentation](../../ANALISIS_DAN_UNIT_TEST.md)
- [Database Migrations](../migrations/)
- [Factories Documentation](../factories/)
- [Laravel Seeding Guide](https://laravel.com/docs/seeding)

## 🤝 Contributing

To add new seeders:
1. Create new seeder class in `database/seeders/`
2. Extend `Illuminate\Database\Seeder`
3. Implement `run()` method
4. Add to `AuthDatabaseSeeder.php` `$this->call()`

Example:
```php
<?php
namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;

class MyNewSeeder extends Seeder
{
    public function run(): void
    {
        // Your seeding logic
    }
}
```

## ✅ Verification Checklist

After running seeders, verify:
- [ ] 1,000+ users created
- [ ] All roles assigned correctly
- [ ] Privacy settings created for all users
- [ ] JWT tokens generated for active users
- [ ] OTP codes available for pending users
- [ ] Activity logs created
- [ ] Demo users can login with password "password"
- [ ] Admin users can view user list
- [ ] Privacy filters work correctly

```bash
# Quick verification commands
php artisan tinker
>>> User::count()                    // Should be 1000+
>>> User::where('status', 'active')->count()   // Should be ~700
>>> User::role('Student')->count()   // Should be ~650
>>> JwtRefreshToken::count()         // Should be 2000+
>>> OtpCode::count()                 // Should be 100+
```
