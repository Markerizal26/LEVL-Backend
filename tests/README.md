# Test Suite Documentation

This directory contains the comprehensive test suite for the PREP LSP application. The tests are organized using Pest PHP and follow best practices for Laravel testing.

## Directory Structure

```
tests/
├── Feature/           # Feature tests (API endpoints, integration tests)
├── Unit/             # Unit tests (services, repositories, helpers)
├── Traits/           # Reusable test traits
├── reports/          # Test coverage and reports
├── ApiTestCase.php   # Base class for API tests
├── TestCase.php      # Base test case
├── Pest.php          # Pest configuration and helpers
└── README.md         # This file
```

## Test Organization

### Unit Tests
Unit tests focus on testing individual components in isolation:
- **Services**: Business logic testing with mocked dependencies
- **Repositories**: Database query testing
- **Helpers**: Utility function testing
- **Models**: Model methods and relationships

### Feature Tests
Feature tests verify complete workflows:
- **API Endpoints**: HTTP request/response testing
- **Integration**: Multi-component interaction testing
- **User Flows**: End-to-end scenarios

### Module Tests
Each module has its own test directory:
```
Modules/{ModuleName}/tests/
├── Unit/
└── Feature/
```

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Modules
```

### Run Tests with Coverage
```bash
php artisan test --coverage
php artisan test --coverage --min=80
```

### Run Specific Test File
```bash
php artisan test tests/Unit/Services/UserServiceTest.php
```

### Run Tests in Parallel
```bash
php artisan test --parallel
```

## Writing Tests

### Naming Conventions

**Test Files**: `{ClassName}Test.php`
- Example: `UserServiceTest.php`, `CourseRepositoryTest.php`

**Test Methods**: Descriptive names explaining what is being tested
- Use `it()` or `test()` functions in Pest
- Example: `it('creates a user with valid data')`

### Test Structure (AAA Pattern)

```php
it('creates a user with valid data', function () {
    // Arrange - Set up test data
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];
    
    // Act - Execute the code being tested
    $user = User::factory()->create($userData);
    
    // Assert - Verify the results
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com');
});
```

### Using Factories

```php
// Create a single model
$user = User::factory()->create();

// Create multiple models
$users = User::factory()->count(5)->create();

// Create with specific attributes
$admin = User::factory()->create(['role' => 'admin']);

// Create with state
$inactiveUser = User::factory()->inactive()->create();
```

### Using Test Traits

```php
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithFactories;

it('allows authenticated users to access profile', function () {
    $user = $this->authenticateAsStudent();
    
    $response = $this->getJson('/api/v1/profile');
    
    $response->assertOk();
});
```

### Helper Functions

Available global helper functions in `tests/Pest.php`:

```php
// Database assertions
assertDatabaseHas('users', ['email' => 'test@example.com']);
assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
assertDatabaseCount('users', 10);

// API helpers
$response = authenticatedRequest('GET', '/api/v1/courses');
$url = api('/courses'); // Returns '/api/v1/courses'

// Role helpers
createTestRoles();
seedTestRoles();
$user = createUserWithRole('Admin');

// Validation helpers
assertValidationError('email', $response);
assertValidationErrors(['email', 'password'], $response);

// Time helpers
$time = freezeTime('2024-01-01 12:00:00');
unfreezeTime();

// File helpers
$file = createTestFile('document.pdf', 'content');
$image = createTestImage('photo.jpg', 800, 600);

// Event/Job/Mail assertions
assertEventDispatched(UserRegistered::class);
assertJobPushed(SendWelcomeEmail::class);
assertMailSent(WelcomeMail::class);
assertNotificationSent($user, WelcomeNotification::class);
```

## Best Practices

### 1. Test Isolation
- Each test should be independent
- Use database transactions (RefreshDatabase trait)
- Clean up after tests

### 2. Descriptive Test Names
```php
// Good
it('prevents duplicate enrollments for the same course')

// Bad
it('test enrollment')
```

### 3. One Assertion Per Concept
```php
// Good - Testing one concept
it('validates required email field', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'John',
        'password' => 'password',
    ]);
    
    assertValidationError('email', $response);
});

// Avoid - Testing multiple unrelated concepts
it('validates all fields', function () {
    // Tests email, password, name validation all at once
});
```

### 4. Use Factories Over Manual Creation
```php
// Good
$user = User::factory()->create();

// Avoid
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => Hash::make('password'),
    // ... many more fields
]);
```

### 5. Mock External Dependencies
```php
it('sends email notification', function () {
    Mail::fake();
    
    $user = User::factory()->create();
    $user->sendWelcomeEmail();
    
    Mail::assertSent(WelcomeMail::class);
});
```

### 6. Test Edge Cases
- Empty inputs
- Null values
- Boundary values (min/max)
- Invalid data types
- Special characters
- Large datasets

### 7. Keep Tests Fast
- Use in-memory SQLite for faster tests when possible
- Mock external API calls
- Avoid unnecessary database queries
- Use `--parallel` flag for parallel execution

## Coverage Goals

- **Overall Coverage**: Minimum 80%
- **Auth Module**: 100%
- **Core Modules**: 95%
- **All Modules**: 80%

## Continuous Integration

Tests run automatically on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop`

CI pipeline includes:
- Running all test suites
- Generating coverage reports
- Enforcing minimum coverage threshold (80%)
- Uploading coverage to Codecov

## Troubleshooting

### Tests Failing Locally But Passing in CI
- Check PHP version matches CI (8.2)
- Verify database configuration
- Clear cache: `php artisan config:clear`

### Database Connection Errors
- Ensure MySQL is running
- Check `.env.testing` configuration
- Verify database exists: `prep_lsp_test`

### Memory Issues
- Increase PHP memory limit in `php.ini`
- Run tests in smaller batches
- Use `--parallel` with fewer processes

### Slow Tests
- Profile tests: `php artisan test --profile`
- Identify slow tests and optimize
- Use database transactions instead of migrations
- Mock external services

## Resources

- [Pest PHP Documentation](https://pestphp.com)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

## Contributing

When adding new features:
1. Write tests first (TDD approach recommended)
2. Ensure all tests pass
3. Maintain or improve coverage
4. Follow naming conventions
5. Document complex test scenarios
