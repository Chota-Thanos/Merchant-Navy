<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Courses assigned to the student, with their subjects. */
class CourseController extends Controller
{
    /** GET /api/v1/courses */
    public function index(Request $request): JsonResponse
    {
        return $this->data($this->catalog($request)->courses());
    }

    /** GET /api/v1/courses/{id} — the course page: subjects and mock tests in order */
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->data($this->catalog($request)->course($id));
    }

    /** GET /api/v1/subjects/{id} — chapters with their content */
    public function subject(Request $request, int $id): JsonResponse
    {
        return $this->data($this->catalog($request)->subject($id));
    }
}
