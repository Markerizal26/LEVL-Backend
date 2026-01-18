# 🚀 Laravel Performance Optimization Roadmap

> **Purpose**: Step-by-step performance optimization guide for Laravel application
> 
> **Status**: Ready for execution
> 
> **Execution**: Run each section sequentially, verify results before proceeding

---

## 📋 Table of Contents

1. [Database Indexing Strategy](#1-database-indexing-strategy)
2. [Query Optimization](#2-query-optimization)
3. [Chunking for Large Datasets](#3-chunking-for-large-datasets)
4. [Caching Strategies](#4-caching-strategies)
5. [Database Connection Optimization](#5-database-connection-optimization)
6. [Monitoring & Debugging](#6-monitoring--debugging)

---

## 1. Database Indexing Strategy

### 🎯 Goal
Reduce query execution time by 70-90% through strategic indexing.

### 📊 Current Status
- [ ] Audit completed (see `database_index_audit.md`)
- [ ] Missing indexes identified
- [ ] Indexes created
- [ ] Performance verified

### ✅ Action Items

#### Step 1.1: Identify Columns to Index

**Rule**: Index columns used in:
- `WHERE` clauses
- `JOIN` conditions
- `ORDER BY` clauses
- Foreign keys

**Example Analysis**:
```php
// Query: Find pending orders from last 7 days
DB::table('orders')
    ->where('status', 'pending')           // ← Index needed
    ->where('created_at', '>', now()->subDays(7))  // ← Index needed
    ->orderBy('created_at', 'desc')        // ← Already indexed above
    ->get();

// Recommended indexes:
// 1. Single: status
// 2. Single: created_at
// 3. Composite: (status, created_at) ← Best for this query
```

#### Step 1.2: Create Index Migration

```bash
# Create migration
php artisan make:migration add_indexes_to_orders_table
```

**File**: `database/migrations/YYYY_MM_DD_add_indexes_to_orders_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 1. Foreign keys (verify auto-indexed)
            $table->index('user_id');
            
            // 2. WHERE clause columns
            $table->index('status');
            $table->index('created_at');
            
            // 3. Composite indexes (order matters!)
            $table->index(['status', 'created_at'], 'idx_orders_status_created');
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
            
            // 4. Unique constraints
            $table->unique('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status_created');
            $table->dropIndex('idx_orders_user_status');
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropUnique(['order_number']);
        });
    }
};
```

#### Step 1.3: Verify Index Usage

```php
// Check if query uses index
$explanation = DB::table('orders')
    ->where('status', 'pending')
    ->where('created_at', '>', now()->subDays(7))
    ->explain()
    ->get();

dd($explanation);
```

**Good Output**:
```
type: "ref" or "range" ✓ (not "ALL" ✗)
key: "idx_orders_status_created" ✓
rows: <1000 ✓ (lower is better)
```

#### Step 1.4: Index Best Practices

**✅ DO:**
- Index foreign keys
- Index columns in WHERE, JOIN, ORDER BY
- Use composite indexes for multi-column queries
- Monitor slow queries, add indexes as needed

**❌ DON'T:**
- Over-index (slows INSERT/UPDATE)
- Index low-cardinality columns (e.g., boolean with 2 values)
- Index columns rarely queried

**Composite Index Order**:
```php
// Index: ['status', 'created_at']
✓ WHERE status = 'x'
✓ WHERE status = 'x' AND created_at > 'y'
✗ WHERE created_at > 'y' (won't use index efficiently)

// Solution: Create separate index for created_at if needed
```

### 🧪 Verification

```bash
# Run migration
php artisan migrate

# Test query performance
php artisan tinker
>>> DB::enableQueryLog();
>>> DB::table('orders')->where('status', 'pending')->get();
>>> DB::getQueryLog();

# Check execution time (should be <10ms with index)
```

---

## 2. Query Optimization

### 🎯 Goal
Eliminate N+1 queries and optimize query structure.

### ✅ Action Items

#### Step 2.1: Enable Query Logging

**File**: `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    if (app()->environment('local')) {
        DB::listen(function ($query) {
            if ($query->time > 100) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            }
        });
    }
}
```

#### Step 2.2: Use Eager Loading

```php
// ❌ BAD - N+1 Query
$users = User::all();
foreach ($users as $user) {
    echo $user->posts->count(); // Query per user!
}

// ✅ GOOD - Eager Loading
$users = User::with('posts')->all();
foreach ($users as $user) {
    echo $user->posts->count(); // No additional query
}
```

#### Step 2.3: Use Query Detector

Already installed! Check responses for `query_detector` field.

### 🧪 Verification

```bash
# Check Telescope for N+1 queries
# Open: http://localhost:8000/telescope/queries

# Check Clockwork for duplicate queries
# Open: http://localhost:8000/clockwork
```

---

## 3. Chunking for Large Datasets

### 🎯 Goal
Process large datasets without memory exhaustion.

### ✅ Action Items

#### Step 3.1: Choose Right Method

```
┌─────────────┬──────────────┬─────────┬─────────────────┐
│ Method      │ Memory Usage │ Speed   │ Use Case        │
├─────────────┼──────────────┼─────────┼─────────────────┤
│ get()       │ High         │ Fast    │ < 1000 records  │
│ chunk()     │ Medium       │ Medium  │ Batch process   │
│ lazy()      │ Low          │ Medium  │ Transform data  │
│ cursor()    │ Lowest       │ Slower  │ Export/stream   │
└─────────────┴──────────────┴─────────┴─────────────────┘
```

#### Step 3.2: Implement Chunking

**Example: Send Newsletter**

```php
// Process 1000 users at a time
DB::table('users')
    ->where('subscribed', true)
    ->orderBy('id')
    ->chunk(1000, function ($users) {
        foreach ($users as $user) {
            $this->sendNewsletter($user);
        }
    });
```

**Example: Update Records**

```php
// Safe for modifications - uses ID-based pagination
DB::table('users')
    ->where('status', 'pending')
    ->chunkById(1000, function ($users) {
        foreach ($users as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['status' => 'processed']);
        }
    });
```

**Example: Export Data**

```php
// Memory efficient - one record at a time
foreach (DB::table('users')->cursor() as $user) {
    $csv->writeRow($user);
}
```

**Example: Lazy Collection**

```php
// Balance between memory and usability
$emails = DB::table('users')
    ->orderBy('id')
    ->lazy()
    ->filter(fn ($user) => $user->status === 'active')
    ->map(fn ($user) => $user->email)
    ->all();
```

### 🧪 Verification

```bash
# Monitor memory usage
php artisan tinker
>>> memory_get_usage(true) / 1024 / 1024; // MB before
>>> DB::table('users')->chunk(1000, fn($users) => null);
>>> memory_get_usage(true) / 1024 / 1024; // MB after
```

---

## 4. Caching Strategies

### 🎯 Goal
Reduce database hits by 80-95% through strategic caching.

### ✅ Action Items

#### Step 4.1: Basic Cache

```php
use Illuminate\Support\Facades\Cache;

// Cache for 1 hour
$categories = Cache::remember('product_categories', 3600, function () {
    return DB::table('categories')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
});

// Cache forever
$settings = Cache::rememberForever('app_settings', function () {
    return DB::table('settings')->pluck('value', 'key');
});
```

#### Step 4.2: Cache Tags (Redis only)

```php
// Cache with tags
$products = Cache::tags(['products', 'catalog'])
    ->remember('featured_products', 3600, function () {
        return DB::table('products')
            ->where('is_featured', true)
            ->get();
    });

// Invalidate all 'products' cache
Cache::tags(['products'])->flush();

// Invalidate specific key
Cache::tags(['products'])->forget('featured_products');
```

#### Step 4.3: Cache Invalidation

```php
class ProductService
{
    public function updateProduct(int $id, array $data): void
    {
        DB::table('products')
            ->where('id', $id)
            ->update($data);

        // Clear related caches
        Cache::forget('product_' . $id);
        Cache::tags(['products'])->flush();
    }

    public function getProduct(int $id)
    {
        return Cache::remember('product_' . $id, 3600, function () use ($id) {
            return DB::table('products')->find($id);
        });
    }
}
```

### 🧪 Verification

```bash
# Check cache hits
redis-cli INFO stats | grep keyspace_hits
redis-cli INFO stats | grep keyspace_misses

# Monitor cache keys
redis-cli KEYS "laravel_cache:*"
```

---

## 5. Database Connection Optimization

### 🎯 Goal
Scale database performance through read/write splitting.

### ✅ Action Items

#### Step 5.1: Configure Read/Write Splitting

**File**: `config/database.php`

```php
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '127.0.0.1'),
            env('DB_READ_HOST_2', '127.0.0.1'),
        ],
    ],
    'write' => [
        'host' => [
            env('DB_WRITE_HOST', '127.0.0.1'),
        ],
    ],
    'sticky' => true, // Use write connection for subsequent reads
    'driver' => 'mysql',
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    // ... other config
],
```

#### Step 5.2: Force Specific Connection

```php
// Force read connection
$users = DB::connection('mysql::read')->table('users')->get();

// Force write connection
$user = DB::connection('mysql::write')->table('users')->find(1);
```

### 🧪 Verification

```bash
# Check connection distribution
# Monitor slow query log on both read and write servers
```

---

## 6. Monitoring & Debugging

### 🎯 Goal
Continuous performance monitoring and optimization.

### ✅ Action Items

#### Step 6.1: Query Logging

**File**: `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    // Log all queries in local
    if (app()->environment('local')) {
        DB::listen(function ($query) {
            Log::debug('Query executed', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
            ]);
        });
    }
}
```

#### Step 6.2: Slow Query Detection

```php
public function boot(): void
{
    // Log queries > 100ms
    DB::listen(function ($query) {
        if ($query->time > 100) {
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
                'url' => request()->fullUrl(),
            ]);
        }
    });
}
```

#### Step 6.3: Use Monitoring Tools

**Already Installed**:
- ✅ Laravel Telescope - `/telescope`
- ✅ Clockwork - `/clockwork`
- ✅ Query Detector - Check API responses

**Monitor**:
- Slow queries (>100ms)
- N+1 queries
- Duplicate queries
- Memory usage
- Cache hit ratio

### 🧪 Verification

```bash
# Check Telescope
open http://localhost:8000/telescope/queries

# Check Clockwork
open http://localhost:8000/clockwork

# Check logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "Slow query"
```

---

## 📊 Performance Benchmarks

### Before Optimization
```
Average Response Time: 289ms
Database Queries: 12 queries, 2 duplicates
Controller Overhead: 259ms
Memory Usage: 66MB
```

### After Optimization (Target)
```
Average Response Time: <100ms (65% improvement)
Database Queries: <10 queries, 0 duplicates
Controller Overhead: <50ms (81% improvement)
Memory Usage: <40MB (40% reduction)
```

---

## 🎯 Execution Checklist

### Phase 1: Indexing (Week 1)
- [ ] Audit all tables for missing indexes
- [ ] Create index migrations
- [ ] Run migrations on staging
- [ ] Verify index usage with EXPLAIN
- [ ] Deploy to production
- [ ] Monitor query performance

### Phase 2: Query Optimization (Week 2)
- [ ] Enable query logging
- [ ] Identify N+1 queries
- [ ] Implement eager loading
- [ ] Verify with Query Detector
- [ ] Monitor Telescope/Clockwork

### Phase 3: Chunking (Week 3)
- [ ] Identify large dataset operations
- [ ] Implement chunking/lazy loading
- [ ] Test memory usage
- [ ] Deploy to production

### Phase 4: Caching (Week 4)
- [ ] Identify cacheable queries
- [ ] Implement cache layer
- [ ] Setup cache invalidation
- [ ] Monitor cache hit ratio
- [ ] Tune cache TTL

### Phase 5: Monitoring (Ongoing)
- [ ] Setup slow query alerts
- [ ] Monitor Telescope daily
- [ ] Review Clockwork weekly
- [ ] Optimize based on metrics

---

## 🚨 Important Notes

### For LLM Execution

**When implementing**:
1. ✅ Execute one section at a time
2. ✅ Verify results before proceeding
3. ✅ Run tests after each change
4. ✅ Monitor performance metrics
5. ✅ Rollback if issues occur

**Context to provide**:
- Current file being optimized
- Performance metrics before/after
- Any errors encountered
- Specific query patterns

### For Human Review

**Before each phase**:
- Review implementation plan
- Backup database
- Test on staging first
- Monitor production metrics

**After each phase**:
- Verify performance improvement
- Check for regressions
- Update documentation
- Communicate changes to team

---

## 📚 References

- [Database Index Audit](./database_index_audit.md)
- [Async Logging Implementation](./implementation_plan.md)
- [Queue Worker Setup](./queue_worker_setup.md)
- [Clockwork Guide](./walkthrough.md)

---

**Last Updated**: 2026-01-18
**Status**: Ready for execution
**Priority**: High
