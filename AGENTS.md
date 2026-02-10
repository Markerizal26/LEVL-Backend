# AGENTS

## Build/Lint/Test Commands

### Code Style (Laravel Pint)
- `vendor/bin/pint` - Fix code style issues automatically
- `vendor/bin/pint --test` - Check for style errors without fixing
- `vendor/bin/pint --bail` - Stop on first error

### Testing (Pest PHP)
- `composer test` - Run all tests (clears config first)
- `vendor/bin/pest` - Run all tests
- `vendor/bin/pest tests/Unit/ExampleTest.php` - Run specific test file
- `vendor/bin/pest --filter "test_name"` - Run specific test by name
- `vendor/bin/pest --filter "ClassName"` - Run all tests in a class
- `vendor/bin/pest --bail` - Stop on first failure

### Static Analysis (PHPStan)
- `composer phpstan` - Run static analysis (level from phpstan.neon)
- `vendor/bin/phpstan analyse app` - Analyze app directory
- `vendor/bin/phpstan analyse Modules/Learning` - Analyze specific module

### Development Server
- `composer dev` - Start dev server with queue, logs, and vite (Octane+FrankenPHP)
- `php artisan serve` - Start PHP development server only

---

## Code Style Guidelines

### PHP Standards
- **Strict Types**: `declare(strict_types=1);` REQUIRED for all files
- **Type Hints**: Full type hints REQUIRED for all parameters and return types
- **PSR-12**: Follow PSR-12 coding standards (enforced by Pint)
- **One Class Per File**: File name MUST equal class name
- **Namespace**: MUST follow PSR-4 folder structure

### Imports
- **No Unused Imports**: All imports must be used
- **Import Order**: Laravel core → Module classes → Vendor packages
- **Aliasing**: Use `as` for conflicting names

### Naming Conventions
- **Classes**: PascalCase (e.g., `CourseService`)
- **Methods**: camelCase (e.g., `createUser`)
- **Variables**: camelCase (e.g., `$userId`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `MAX_ATTEMPTS`)
- **Database Tables**: snake_case (e.g., `user_profiles`)

### Comments Policy
- **No New Comments**: PHPDoc, inline `//`, or block `/* */` may NOT be added
- **Remove Existing**: Remove comments encountered during edits
- **Clear Naming**: Use descriptive names instead of explanatory comments

---

## Architectural Patterns

### Controller Discipline (STRICT)
Controllers are THIN coordinators. MAXIMUM 10 lines per method.

**Required Structure:**
```php
public function methodName(FormRequest $request, ?Model $model = null)
{
    $this->authorize('action', $model);
    $data = $request->validated();
    $actor = auth('api')->user();
    $result = $this->service->methodName($data, $actor);
    return $this->success(new Resource($result));
}
```

**FORBIDDEN in Controllers:**
- Business logic (if/else for domain rules)
- Direct Model access (create/update/delete)
- File/media handling
- Exception message parsing
- Filter/query interpretation
- Private methods (move to Service)

### Service Pattern
- One Service per domain entity/aggregate
- Accept primitives, arrays, or DTOs (NOT Request)
- Accept uploaded files as array parameter if needed
- Handle ALL business logic, media operations, transactions
- Throw domain-specific custom exceptions
- Use `readonly` constructor injection

```php
public function __construct(
    private readonly CourseRepository $repository,
) {}

public function create(array $data, User $actor, array $files = []): Course
{
    return DB::transaction(function () use ($data, $actor, $files) {
        // Business logic here
    });
}
```

### Repository Pattern
- Extend `App\Repositories\BaseRepository`
- Use for complex queries + reusable queries
- No business logic (pure query methods)
- Service calls Repository, NOT Model directly

### Cross-Module Communication
- ONLY via Public Service + Contract
- NO direct Model/DB access across modules
- Use Dependency Injection with interfaces

---

## Laravel Octane Safety (CRITICAL)

### State Management Rules (FrankenPHP keeps app in memory)

**FORBIDDEN:**
- ❌ Mutable static properties storing request data
- ❌ Global variables (`$globalVar = ...`)
- ❌ Singleton services with mutable state
- ❌ Class-level caching in properties
- ❌ Instance properties in Controllers/Middleware

**CORRECT:**
- ✅ Stateless services (all data via parameters)
- ✅ Request-scoped bindings via `scoped()`
- ✅ Laravel Cache (Redis/Database)
- ✅ Use DI for all dependencies

```php
// ❌ FORBIDDEN - State leaks across requests
class UserService {
    private static $currentUser = null;
    public function setUser($user) { self::$currentUser = $user; }
}

// ✅ CORRECT - Stateless
class UserService {
    public function getCurrentUser(User $authUser): User {
        return $authUser;
    }
}
```

### Required Octane Listeners
Verify `config/octane.php` has:
```php
OperationTerminated::class => [
    FlushOnce::class,
    FlushTemporaryContainerInstances::class,
    DisconnectFromDatabases::class,
    CollectGarbage::class,
],
```

---

## Testing Requirements

### Test Coverage
- **Service Tests**: 100% behavior (all branches, exceptions)
- **Repository Tests**: All filters, sorts, pagination
- **Controller Tests**: HTTP response + JSON format
- **Policy Tests**: All authorization rules
- **Resource Tests**: All field transformations

### Test Organization
- **Unit Tests**: `tests/Unit/` - Business logic (mock dependencies)
- **Feature Tests**: `tests/Feature/` - Full HTTP cycle
- **Module Tests**: `Modules/*/tests/`

### Running Tests
- Always run Pint and PHPStan before committing
- Test MUST pass before merge
- Use factories for test data (NOT seeders)

---

## Security & Data Handling

### Authentication/Authorization
- Use `middleware(['auth:api'])` for authenticated routes
- Use `$this->authorize('action', $model)` in controllers
- Policies MUST be registered in `AuthServiceProvider`
- `Gate::before()` handles Superadmin bypass

### Input Validation
- FormRequest classes for validation
- `$fillable` MUST be strict (no wildcards)
- Use Eloquent/Query Builder (no raw SQL)

### Response Format
- Use `JsonResource` for ALL data responses
- Structure: `{success, message, data, meta, errors}`
- Use translation keys for messages (`__('messages.key')`)

---

## Database & Migrations

### Migration Rules
- Format: `{YYYY}_{MM}_{DD}_{HHMMSS}_{description}.php`
- Foreign keys MUST have indexes
- Foreign key naming: `{table}_{column}_foreign`
- Destructive ops require guard: `if (app()->environment('production'))`
- Zero-downtime: Add nullable first, backfill, then make required

### Transaction Rules
- Multi-table writes MUST use `DB::transaction`
- Transaction boundary: Service layer
- Use transaction callbacks, NOT nested transactions

---

## Tech Stack
- Laravel 11, PHP 8.2+, PostgreSQL
- Laravel Modules (nWidart)
- Pest PHP (Testing)
- Spatie Permission, Query Builder, Media Library
- PostgreSQL FTS (Full Text Search) via `PgSearchable` trait
- Laravel Octane (FrankenPHP)

## File Paths
- Controller: `Modules/{Module}/app/Http/Controllers`
- Service: `Modules/{Module}/app/Services`
- Repository: `Modules/{Module}/app/Repositories`
- Request: `Modules/{Module}/app/Http/Requests`
- Resource: `Modules/{Module}/app/Http/Resources`
- Policy: `Modules/{Module}/app/Policies`
- Contract: `Modules/{Module}/app/Contracts`
