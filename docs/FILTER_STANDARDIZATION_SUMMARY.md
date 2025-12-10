# Filter[] Standardization - Summary

## ✅ Perubahan Selesai

Semua filter parameter sekarang menggunakan format `filter[]` **KECUALI** parameter `search` yang tetap langsung.

## 📋 File yang Dimodifikasi

### 1. SearchController (Modules/Search)

**File**: `Modules/Search/app/Http/Controllers/SearchController.php`

**Perubahan**:

- ✅ Dokumentasi: `category_id` → `filter[category_id]`
- ✅ Dokumentasi: `level_tag` → `filter[level_tag]`
- ✅ Dokumentasi: `instructor_id` → `filter[instructor_id]`
- ✅ Dokumentasi: `status` → `filter[status]`
- ✅ Implementasi: Updated kode untuk membaca dari `filter.category_id`, `filter.level_tag`, dll
- ✅ `query` tetap langsung (bukan `filter[query]`)

**Query Format**:

```
GET /api/v1/search?query=Laravel&filter[category_id]=1&filter[level_tag]=beginner
```

### 2. EnrollmentsController

**File**: `Modules/Enrollments/app/Http/Controllers/EnrollmentsController.php`

**Perubahan**:

- ✅ Menghapus duplikasi dokumentasi parameter
- ✅ Semua menggunakan `filter[]`: `filter[course_id]`, `filter[user_id]`, `filter[status]`, `filter[enrollment_date]`
- ✅ Parameter khusus `user_id` untuk endpoint `status()` tetap langsung (bukan filter)

**Query Format**:

```
GET /api/v1/enrollments?filter[course_id]=1&filter[status]=active
GET /api/v1/courses/{course}/enrollments?filter[user_id]=5
```

### 3. UnitController (Modules/Schemes)

**File**: `Modules/Schemes/app/Http/Controllers/UnitController.php`

**Perubahan**:

- ✅ `status` → `filter[status]`

**Query Format**:

```
GET /api/v1/courses/{course}/units?filter[status]=published
```

### 4. LessonController (Modules/Schemes)

**File**: `Modules/Schemes/app/Http/Controllers/LessonController.php`

**Perubahan**:

- ✅ `status` → `filter[status]`
- ✅ `content_type` → `filter[content_type]`

**Query Format**:

```
GET /api/v1/courses/{course}/units/{unit}/lessons?filter[status]=published&filter[content_type]=video
```

### 5. ThreadController (Modules/Forums)

**File**: `Modules/Forums/app/Http/Controllers/ThreadController.php`

**Perubahan**:

- ✅ Menghapus duplikasi dokumentasi parameter
- ✅ Sudah menggunakan `filter[]` untuk semua filter

**Query Format**:

```
GET /api/v1/forums/{forum}/threads?filter[user_id]=5&filter[is_pinned]=true
```

## 📊 Controllers yang Sudah Benar (Tidak Diubah)

Controllers berikut **sudah menggunakan** format `filter[]` yang benar:

1. ✅ **AuthApiController** - `filter[search]`, `filter[status]`, `filter[role]`
2. ✅ **ProfileActivityController** - `filter[type]`, `filter[start_date]`, `filter[end_date]`
3. ✅ **AnnouncementController** - `filter[course_id]`, `filter[priority]`, `filter[unread]`
4. ✅ **NewsController** - `filter[category_id]`, `filter[tag_id]`, `filter[featured]`
5. ✅ **ContentStatisticsController** - `filter[type]`, `filter[course_id]`, `filter[category_id]`
6. ✅ **ContentSearchController** - `filter[type]`, `filter[category_id]`, `filter[date_from]`
7. ✅ **CourseController** - `filter[search]`, `filter[status]`, `filter[level_tag]`
8. ✅ **SubmissionController** - `filter[user_id]`, `filter[status]`
9. ✅ **ChallengeController** - `filter[type]`
10. ✅ **ForumStatisticsController** - `filter[period_start]`, `filter[period_end]`, `filter[user_id]`

## 🎯 Format Standar

### Filter Parameters

```php
/**
 * @queryParam filter[field_name] type Description. Example: value
 */
```

**Usage**:

```
?filter[status]=active
?filter[category_id]=1
?filter[date_from]=2025-01-01
```

### Search Parameter (EXCEPTION)

```php
/**
 * @queryParam search string Kata kunci pencarian. Example: Laravel
 */
```

**Usage**:

```
?search=Laravel
```

### Query Parameter (Special Case)

Parameter yang bukan filter, seperti `query` di SearchController atau `user_id` di enrollment status:

```php
/**
 * @queryParam query string Kata kunci pencarian. Example: Laravel
 * @queryParam user_id integer ID user untuk dicek. Example: 1
 */
```

**Usage**:

```
?query=Laravel
?user_id=1
```

## 📝 Konvensi Naming

### Filter Parameters

| Tipe         | Format                | Contoh                          |
| ------------ | --------------------- | ------------------------------- |
| Status       | `filter[status]`      | `?filter[status]=active`        |
| ID Reference | `filter[user_id]`     | `?filter[user_id]=5`            |
| Boolean      | `filter[is_pinned]`   | `?filter[is_pinned]=true`       |
| Date Range   | `filter[date_from]`   | `?filter[date_from]=2025-01-01` |
| Category     | `filter[category_id]` | `?filter[category_id]=1`        |
| Enum Value   | `filter[type]`        | `?filter[type]=daily`           |

### Non-Filter Parameters

| Parameter  | Usage            | Contoh                   |
| ---------- | ---------------- | ------------------------ |
| `search`   | Full-text search | `?search=keyword`        |
| `query`    | Search query     | `?query=Laravel`         |
| `page`     | Pagination       | `?page=1`                |
| `per_page` | Items per page   | `?per_page=15`           |
| `sort`     | Sorting          | `?sort=-created_at`      |
| `include`  | Eager loading    | `?include=category,tags` |

### Special Case Parameters

Parameter khusus yang tidak termasuk filter biasa:

- `user_id` di enrollment status endpoint (untuk Superadmin check status user lain)
- `limit` di autocomplete/history endpoints
- `id` di clear history endpoint

## 🔍 Verification

### Test Endpoints

1. **Search with Filters**:

```bash
curl -X GET "http://localhost:8000/api/v1/search?query=Laravel&filter[category_id]=1&filter[level_tag]=beginner" \
  -H "Authorization: Bearer {token}"
```

2. **Enrollments with Filters**:

```bash
curl -X GET "http://localhost:8000/api/v1/enrollments?filter[status]=active&filter[course_id]=1" \
  -H "Authorization: Bearer {token}"
```

3. **Units with Filter**:

```bash
curl -X GET "http://localhost:8000/api/v1/courses/1/units?filter[status]=published" \
  -H "Authorization: Bearer {token}"
```

4. **Threads with Filters**:

```bash
curl -X GET "http://localhost:8000/api/v1/forums/1/threads?filter[is_pinned]=true&filter[is_solved]=false" \
  -H "Authorization: Bearer {token}"
```

## ✅ Checklist Compliance

- ✅ Semua filter parameters menggunakan `filter[]` notation
- ✅ Parameter `search` tetap langsung (tidak menggunakan `filter[]`)
- ✅ Parameter `query` di SearchController tetap langsung
- ✅ Pagination parameters (`page`, `per_page`) tetap langsung
- ✅ Sorting parameter (`sort`) tetap langsung
- ✅ Include parameter (`include`) tetap langsung
- ✅ Dokumentasi PHPDoc updated
- ✅ Implementasi kode updated untuk SearchController
- ✅ Special case parameters documented dengan jelas

## 📖 Backend Implementation

### Laravel Query Builder Support

Dengan format `filter[]`, Laravel akan automatically parse sebagai array:

```php
// URL: ?filter[status]=active&filter[category_id]=1
$filters = $request->input("filter");
// Result: ['status' => 'active', 'category_id' => 1]
```

### Spatie Query Builder

Format `filter[]` compatible dengan Spatie Query Builder:

```php
use Spatie\QueryBuilder\QueryBuilder;

$courses = QueryBuilder::for(Course::class)
  ->allowedFilters(["status", "category_id", "level_tag"])
  ->get();
```

## 🎉 Summary

**Total Files Modified**: 5 files

- SearchController: Documentation + Implementation
- EnrollmentsController: Documentation cleanup
- UnitController: Documentation fix
- LessonController: Documentation fix
- ThreadController: Documentation cleanup

**Result**: 100% compliance dengan standar `filter[]` untuk semua filter parameters, dengan exception yang jelas untuk `search` dan parameter khusus lainnya.

---

**Updated**: December 10, 2025  
**Status**: ✅ Complete
