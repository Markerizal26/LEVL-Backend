# Design Document - Database Consistency Review

## Overview

This design document outlines the technical approach for reviewing and improving database schema consistency, PHP Enum implementation, naming conventions, and API routing consistency across the LMS Sertifikasi application. The improvements focus on ensuring production-ready code that follows Laravel best practices and maintains consistency across all modules.

## Architecture

### Current State Analysis

```
┌─────────────────────────────────────────────────────────────┐
│                    Current Issues                            │
├─────────────────────────────────────────────────────────────┤
│ Database Layer:                                              │
│ - 8 string columns that should be enum                       │
│ - 0 PHP Enum classes (35+ needed)                           │
│ - Inconsistent naming conventions                            │
│ - Missing deleted_by on some soft delete tables             │
│ - Redundant tags_json column                                │
├─────────────────────────────────────────────────────────────┤
│ Routing Layer:                                               │
│ - Mixed auth middleware (auth:api vs auth:sanctum)          │
│ - Inconsistent route binding (/{id} vs /{slug})             │
│ - Missing route names                                        │
│ - Potential route conflicts                                  │
│ - Inconsistent role middleware casing                        │
└─────────────────────────────────────────────────────────────┘
```

### Target State

```
┌─────────────────────────────────────────────────────────────┐
│                    Target State                              │
├─────────────────────────────────────────────────────────────┤
│ Database Layer:                                              │
│ - All status/type columns use database enum                  │
│ - PHP Enum classes for all enum columns                      │
│ - Models cast enums to PHP Enum types                        │
│ - Consistent naming conventions                              │
│ - Consistent soft delete pattern with deleted_by            │
├─────────────────────────────────────────────────────────────┤
│ Routing Layer:                                               │
│ - Consistent auth:api middleware                             │
│ - Consistent route model binding                             │
│ - All routes have explicit names                             │
│ - No route conflicts                                         │
│ - Consistent role middleware format                          │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. PHP Enum Classes Structure

**Location:** `Modules/{Module}/app/Enums/`

```php
<?php

namespace Modules\Assessments\Enums;

enum ExerciseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Get all enum values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum for validation rules.
     */
    public static function rule(): string
    {
        return 'in:' . implode(',', self::values());
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::Draft => __('enums.status.draft'),
            self::Published => __('enums.status.published'),
            self::Archived => __('enums.status.archived'),
        };
    }
}
```

### 2. Model Enum Casting

**Example Model Update:**

```php
<?php

namespace Modules\Assessments\Models;

use Modules\Assessments\Enums\ExerciseStatus;
use Modules\Assessments\Enums\ExerciseType;
use Modules\Assessments\Enums\ScopeType;

class Exercise extends Model
{
    protected $casts = [
        'status' => ExerciseStatus::class,
        'type' => ExerciseType::class,
        'scope_type' => ScopeType::class,
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'allow_retake' => 'boolean',
    ];
}
```

### 3. Migration for String to Enum Conversion

**Example Migration:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL, we need to modify the column type
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') 
            DEFAULT 'pending'");
        
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') 
            DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
        
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN payment_status VARCHAR(50) DEFAULT 'pending'");
    }
};
```

### 4. Route Standardization Pattern

**Standardized Route Structure:**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    // Resource routes with explicit names
    Route::prefix('announcements')->as('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::get('/{announcement}', [AnnouncementController::class, 'show'])->name('show');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
        
        // Custom actions
        Route::post('/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('publish');
    });
});
```

## Data Models

### PHP Enum Classes to Create

| Module | Enum Class | File Path |
|--------|-----------|-----------|
| Auth | UserStatus | `Modules/Auth/app/Enums/UserStatus.php` |
| Schemes | CourseStatus | `Modules/Schemes/app/Enums/CourseStatus.php` |
| Schemes | CourseType | `Modules/Schemes/app/Enums/CourseType.php` |
| Schemes | LevelTag | `Modules/Schemes/app/Enums/LevelTag.php` |
| Schemes | EnrollmentType | `Modules/Schemes/app/Enums/EnrollmentType.php` |
| Schemes | ProgressionMode | `Modules/Schemes/app/Enums/ProgressionMode.php` |
| Schemes | ContentType | `Modules/Schemes/app/Enums/ContentType.php` |
| Enrollments | EnrollmentStatus | `Modules/Enrollments/app/Enums/EnrollmentStatus.php` |
| Assessments | ExerciseType | `Modules/Assessments/app/Enums/ExerciseType.php` |
| Assessments | ExerciseStatus | `Modules/Assessments/app/Enums/ExerciseStatus.php` |
| Assessments | ScopeType | `Modules/Assessments/app/Enums/ScopeType.php` |
| Assessments | QuestionType | `Modules/Assessments/app/Enums/QuestionType.php` |
| Assessments | AttemptStatus | `Modules/Assessments/app/Enums/AttemptStatus.php` |
| Assessments | RegistrationStatus | `Modules/Assessments/app/Enums/RegistrationStatus.php` |
| Assessments | PaymentStatus | `Modules/Assessments/app/Enums/PaymentStatus.php` |
| Learning | SubmissionType | `Modules/Learning/app/Enums/SubmissionType.php` |
| Learning | SubmissionStatus | `Modules/Learning/app/Enums/SubmissionStatus.php` |
| Learning | AssignmentStatus | `Modules/Learning/app/Enums/AssignmentStatus.php` |
| Grading | SourceType | `Modules/Grading/app/Enums/SourceType.php` |
| Grading | GradeStatus | `Modules/Grading/app/Enums/GradeStatus.php` |
| Gamification | PointSourceType | `Modules/Gamification/app/Enums/PointSourceType.php` |
| Gamification | PointReason | `Modules/Gamification/app/Enums/PointReason.php` |
| Gamification | BadgeType | `Modules/Gamification/app/Enums/BadgeType.php` |
| Gamification | ChallengeType | `Modules/Gamification/app/Enums/ChallengeType.php` |
| Content | ContentStatus | `Modules/Content/app/Enums/ContentStatus.php` |
| Content | TargetType | `Modules/Content/app/Enums/TargetType.php` |
| Content | Priority | `Modules/Content/app/Enums/Priority.php` |
| Notifications | NotificationType | `Modules/Notifications/app/Enums/NotificationType.php` |
| Notifications | NotificationChannel | `Modules/Notifications/app/Enums/NotificationChannel.php` |
| Notifications | NotificationFrequency | `Modules/Notifications/app/Enums/NotificationFrequency.php` |
| Common | CategoryStatus | `Modules/Common/app/Enums/CategoryStatus.php` |
| Common | SettingType | `Modules/Common/app/Enums/SettingType.php` |

### Database Migrations Needed

| Migration | Purpose |
|-----------|---------|
| `convert_assessment_registration_strings_to_enum` | Convert status, payment_status, payment_method, result to enum |
| `convert_notification_preferences_strings_to_enum` | Convert category, channel, frequency to enum |
| `add_deleted_by_to_courses` | Add deleted_by column to courses table |
| `remove_redundant_tags_json` | Remove tags_json column (use pivot table instead) |



## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Enum Column PHP Class Correspondence
*For any* database enum column in any migration file, there SHALL exist a corresponding PHP Enum class in the module's `app/Enums` directory with matching values.
**Validates: Requirements 1.2, 7.1**

### Property 2: Model Enum Casting Completeness
*For any* model with enum columns, the `$casts` array SHALL cast those columns to their corresponding PHP Enum classes, not strings.
**Validates: Requirements 1.3, 7.3**

### Property 3: Naming Convention Compliance
*For any* database column, the naming SHALL follow these patterns:
- Foreign keys: `{table_singular}_id`
- Booleans: `is_*` or `has_*` prefix
- Timestamps: `*_at` suffix
- Counts: `*_count` suffix
**Validates: Requirements 2.1, 2.3, 2.4, 2.5**

### Property 4: Polymorphic Index Existence
*For any* polymorphic relationship columns (`*_type`, `*_id`), there SHALL exist a composite index on both columns.
**Validates: Requirements 3.3**

### Property 5: Foreign Key Constraint Existence
*For any* column ending in `_id` that references another table, there SHALL exist a foreign key constraint with defined ON DELETE behavior.
**Validates: Requirements 3.5, 6.1, 6.2**

### Property 6: Status Value Consistency
*For any* status enum across all modules, the values SHALL be consistent:
- Content status: `draft`, `published`, `archived` OR `draft`, `published`, `scheduled`
- Enrollment status: `pending`, `active`, `completed`, `cancelled`
- User status: `pending`, `active`, `inactive`, `banned`
**Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

### Property 7: Soft Delete Pattern Consistency
*For any* table with `deleted_at` column (soft deletes), there SHALL also exist a `deleted_by` column with foreign key to users table.
**Validates: Requirements 8.1, 8.3**

### Property 8: Timestamp Column Existence
*For any* database table (except pivot tables), there SHALL exist `created_at` and `updated_at` timestamp columns.
**Validates: Requirements 8.2**

### Property 9: Data Type Consistency
*For any* column type across tables:
- Score columns SHALL use the same numeric type (integer or decimal)
- Path columns SHALL use `string(255)`
- Count columns SHALL use `unsignedInteger` or `unsignedBigInteger`
**Validates: Requirements 9.1, 9.3, 9.4, 9.5**

### Property 10: Auth Middleware Consistency
*For any* protected API route across all modules, the authentication middleware SHALL be `auth:api`, not `auth:sanctum` or other variants.
**Validates: Requirements 10.3, 12.1**

### Property 11: Route Name Existence
*For any* API route definition, there SHALL exist an explicit route name following the pattern `{resource}.{action}` or `{parent}.{resource}.{action}`.
**Validates: Requirements 11.1, 11.2, 11.5**

### Property 12: Role Middleware Format Consistency
*For any* role middleware usage, the format SHALL be `role:RoleName` with PascalCase role names (e.g., `role:Admin`, not `role:admin`).
**Validates: Requirements 12.2**

### Property 13: Enum Magic String Elimination
*For any* code that checks or sets enum values, the code SHALL reference PHP Enum cases instead of magic strings.
**Validates: Requirements 7.4**

### Property 14: Validation Rule Enum Usage
*For any* validation rule that validates enum values, the rule SHALL use the PHP Enum's `values()` or `cases()` method instead of hardcoded arrays.
**Validates: Requirements 7.5**

## Error Handling

### Migration Error Handling

When converting string columns to enum:
1. Validate existing data matches new enum values before migration
2. Provide rollback capability
3. Log any data that doesn't match expected values

```php
public function up(): void
{
    // Check for invalid data first
    $invalidRecords = DB::table('assessment_registrations')
        ->whereNotIn('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
        ->count();
    
    if ($invalidRecords > 0) {
        throw new \RuntimeException("Found {$invalidRecords} records with invalid status values. Please fix before migration.");
    }
    
    // Proceed with migration
    DB::statement("ALTER TABLE assessment_registrations ...");
}
```

### Enum Casting Error Handling

When model casts fail due to invalid enum values:

```php
// In model boot method
protected static function boot()
{
    parent::boot();
    
    static::saving(function ($model) {
        // Validate enum values before saving
        if ($model->status && !ExerciseStatus::tryFrom($model->status)) {
            throw new \InvalidArgumentException("Invalid status value: {$model->status}");
        }
    });
}
```

## Testing Strategy

### Unit Testing

**Testing PHP Enum Classes:**

```php
public function test_exercise_status_enum_has_expected_values(): void
{
    $values = ExerciseStatus::values();
    
    $this->assertContains('draft', $values);
    $this->assertContains('published', $values);
    $this->assertContains('archived', $values);
    $this->assertCount(3, $values);
}

public function test_exercise_status_enum_rule_returns_valid_string(): void
{
    $rule = ExerciseStatus::rule();
    
    $this->assertEquals('in:draft,published,archived', $rule);
}
```

**Testing Model Enum Casting:**

```php
public function test_exercise_model_casts_status_to_enum(): void
{
    $exercise = Exercise::factory()->create(['status' => 'draft']);
    
    $this->assertInstanceOf(ExerciseStatus::class, $exercise->status);
    $this->assertEquals(ExerciseStatus::Draft, $exercise->status);
}
```

### Property-Based Testing

Using PHPUnit with data providers for property-based testing:

**Property Test: Enum Correspondence**

```php
/**
 * @dataProvider enumColumnProvider
 * **Feature: database-consistency-review, Property 1: Enum Column PHP Class Correspondence**
 */
public function test_enum_columns_have_corresponding_php_enum_classes(
    string $table,
    string $column,
    string $expectedEnumClass
): void {
    $this->assertTrue(
        class_exists($expectedEnumClass),
        "PHP Enum class {$expectedEnumClass} does not exist for {$table}.{$column}"
    );
    
    $this->assertTrue(
        enum_exists($expectedEnumClass),
        "{$expectedEnumClass} is not a valid PHP Enum"
    );
}

public static function enumColumnProvider(): array
{
    return [
        ['exercises', 'status', ExerciseStatus::class],
        ['exercises', 'type', ExerciseType::class],
        ['attempts', 'status', AttemptStatus::class],
        // ... more enum columns
    ];
}
```

**Property Test: Model Casting**

```php
/**
 * @dataProvider modelEnumCastProvider
 * **Feature: database-consistency-review, Property 2: Model Enum Casting Completeness**
 */
public function test_models_cast_enum_columns_to_php_enums(
    string $modelClass,
    string $column,
    string $expectedEnumClass
): void {
    $model = new $modelClass();
    $casts = $model->getCasts();
    
    $this->assertArrayHasKey($column, $casts);
    $this->assertEquals($expectedEnumClass, $casts[$column]);
}
```

**Property Test: Auth Middleware Consistency**

```php
/**
 * **Feature: database-consistency-review, Property 10: Auth Middleware Consistency**
 */
public function test_all_protected_routes_use_auth_api_middleware(): void
{
    $routes = Route::getRoutes();
    
    foreach ($routes as $route) {
        $middleware = $route->middleware();
        
        if (in_array('auth:sanctum', $middleware)) {
            $this->fail("Route {$route->uri()} uses auth:sanctum instead of auth:api");
        }
    }
}
```

**Property Test: Route Names Existence**

```php
/**
 * **Feature: database-consistency-review, Property 11: Route Name Existence**
 */
public function test_all_api_routes_have_names(): void
{
    $routes = Route::getRoutes();
    
    foreach ($routes as $route) {
        if (str_starts_with($route->uri(), 'v1/')) {
            $this->assertNotNull(
                $route->getName(),
                "Route {$route->uri()} does not have a name"
            );
        }
    }
}
```

### Integration Testing

**Testing Database Schema:**

```php
public function test_foreign_key_columns_have_indexes(): void
{
    $tables = Schema::getAllTables();
    
    foreach ($tables as $table) {
        $columns = Schema::getColumnListing($table->name);
        $indexes = Schema::getIndexes($table->name);
        
        foreach ($columns as $column) {
            if (str_ends_with($column, '_id') && $column !== 'id') {
                $hasIndex = collect($indexes)->contains(function ($index) use ($column) {
                    return in_array($column, $index['columns']);
                });
                
                $this->assertTrue(
                    $hasIndex,
                    "Column {$table->name}.{$column} should have an index"
                );
            }
        }
    }
}
```

## Implementation Notes

### Migration Order

1. Create PHP Enum classes first (no database changes)
2. Update models to use enum casting
3. Run migrations to convert string columns to enum
4. Update routes to fix middleware and naming
5. Update validation rules to use enum classes

### Backward Compatibility

- Enum values are stored as strings in database, so existing data is compatible
- PHP Enum casting is transparent to API consumers
- Route changes may require frontend updates for named routes

### Performance Considerations

- PHP Enum casting has minimal performance impact
- Database enum columns are more efficient than varchar for fixed values
- Composite indexes on polymorphic columns improve query performance
