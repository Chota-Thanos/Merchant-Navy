<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /** GET /api/v1/mock-tests — mock tests of all the student's courses */
    public function mockTests(Request $request): JsonResponse
    {
        return $this->data($this->catalog($request)->mockTests());
    }

    /** GET /api/v1/tests/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $catalog = $this->catalog($request);

        return $this->data($catalog->testSummary($catalog->findTest($id)));
    }
}
