<?php

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\CourseRequest;
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

        $isPublicListing = ($params['visibility'] ?? null) === 'public' && (($params['status'] ?? 'published') === 'published');

        $paginator = $isPublicListing
            ? $this->service->listPublic($params)
            : $this->service->list($params);

        return $this->paginateResponse($paginator);
    }

    public function store(CourseRequest $request)
    {
        $data = $request->validated();
        $course = $this->service->create($data);

        return $this->created(['course' => $course], 'Course created');
    }

    public function show(int $course)
    {
        $found = $this->repository->findById($course);
        if (! $found) {
            return $this->error('Course not found', 404);
        }

        return $this->success(['course' => $found]);
    }

    public function update(CourseRequest $request, int $course)
    {
        $data = $request->validated();
        $updated = $this->service->update($course, $data);
        if (! $updated) {
            return $this->error('Course not found', 404);
        }

        return $this->success(['course' => $updated], 'Course updated');
    }

    public function destroy(int $course)
    {
        $ok = $this->service->delete($course);
        if (! $ok) {
            return $this->error('Course not found', 404);
        }

        return $this->success([], 'Course deleted');
    }
}
