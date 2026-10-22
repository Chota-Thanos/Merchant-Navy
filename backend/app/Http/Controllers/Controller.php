<?php

namespace App\Http\Controllers;

use App\Services\Student\StudentCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /** What the logged-in student is allowed to see (see StudentCatalog). */
    protected function catalog(Request $request): StudentCatalog
    {
        return app(StudentCatalog::class, ['user' => $request->user()]);
    }

    /** All API responses are shaped { "data": ... } */
    protected function data(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }
}
