<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\CourseRequest;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Repositories\CourseRepository;
use Modules\Schemes\Services\CourseService;

class CourseController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CourseService $service,
        private CourseRepository $repository
    ) {}

    public function index(Request $request)
    {
        $params = $request->all();

        $isPublicListing = ($params['status'] ?? null) === 'published';

        $paginator = $isPublicListing
            ? $this->service->listPublic($params)
            : $this->service->list($params);

        return $this->paginateResponse($paginator);
    }

    public function store(CourseRequest $request)
    {
        $data = $request->validated();
        // Handle file uploads
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = app(\App\Services\UploadService::class)->storePublic($request->file('thumbnail'), 'courses/thumbnails');
        }
        if ($request->hasFile('banner')) {
            $data['banner_path'] = app(\App\Services\UploadService::class)->storePublic($request->file('banner'), 'courses/banners');
        }
        /** @var \Modules\Auth\Models\User|null $actor */
        $actor = auth('api')->user();

        try {
            $course = $this->service->create($data, $actor);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            return $this->handleCourseUniqueConstraint($e);
        }

        return $this->created(['course' => $course], 'Course berhasil dibuat.');
    }

    public function show(Course $course)
    {
        return $this->success(['course' => $course->load(['tags', 'outcomes', 'prerequisiteCourses'])]);
    }

    public function update(CourseRequest $request, Course $course)
    {
        $data = $request->validated();
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = app(\App\Services\UploadService::class)->storePublic($request->file('thumbnail'), 'courses/thumbnails');
        }
        if ($request->hasFile('banner')) {
            $data['banner_path'] = app(\App\Services\UploadService::class)->storePublic($request->file('banner'), 'courses/banners');
        }
        try {
            $updated = $this->service->update($course->id, $data);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            return $this->handleCourseUniqueConstraint($e);
        }

        return $this->success(['course' => $updated], 'Course berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $ok = $this->service->delete($course->id);

        return $this->success([], 'Course berhasil dihapus.');
    }

    public function publish(Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $course)) {
            return $this->error('Anda tidak memiliki akses untuk mempublish course ini.', 403);
        }

        $updated = $this->service->publish($course->id);

        return $this->success(['course' => $updated], 'Course berhasil dipublish.');
    }

    public function unpublish(Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();
        if (! \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $course)) {
            return $this->error('Anda tidak memiliki akses untuk unpublish course ini.', 403);
        }

        $updated = $this->service->unpublish($course->id);

        return $this->success(['course' => $updated], 'Course berhasil diunpublish.');
    }

    private function handleCourseUniqueConstraint(QueryException $e)
    {
        $message = $e->getMessage();

        $errors = [];
        if (str_contains($message, 'courses_code_unique')) {
            $errors['code'][] = 'Kode sudah digunakan.';
        }
        if (str_contains($message, 'courses_slug_unique')) {
            $errors['slug'][] = 'Slug sudah digunakan.';
        }

        if (! empty($errors)) {
            return $this->validationError($errors);
        }

        return $this->validationError([
            'general' => ['Data duplikat. Periksa kembali isian Anda.'],
        ]);
    }
}
