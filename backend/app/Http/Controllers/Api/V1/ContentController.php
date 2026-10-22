<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\PdfDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/** One lesson item: view it, mark it complete, open a PDF, submit an assignment. */
class ContentController extends Controller
{
    /** GET /api/v1/contents/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->data($this->catalog($request)->contentWithSubject($id));
    }

    /** POST /api/v1/contents/{id}/complete */
    public function complete(Request $request, int $id): JsonResponse
    {
        $this->catalog($request)->markComplete($id);

        return $this->data(null);
    }

    /**
     * GET /api/v1/contents/{id}/pages — short-lived signed links to the watermarked page images.
     * An empty list means the PDF is still being prepared.
     */
    public function pages(Request $request, int $id): JsonResponse
    {
        $content = $this->catalog($request)->findContent($id);
        abort_unless($content->type === ContentType::Pdf, 404);

        /** @var PdfDocument $pdf */
        $pdf = $content->contentable;
        if (! $pdf->processed_at) {
            return $this->data([]);
        }

        $urls = collect(range(1, $pdf->page_count))->map(fn (int $page) => URL::temporarySignedRoute(
            'pdf.page',
            now()->addMinutes(config('lms.pdf_link_minutes')),
            ['content' => $content->id, 'page' => $page, 'u' => $request->user()->id],
        ))->all();

        return $this->data($urls);
    }

    /** POST /api/v1/contents/{id}/submission — upload an assignment answer (PDF or image). */
    public function submitAssignment(Request $request, int $id): JsonResponse
    {
        $content = $this->catalog($request)->findContent($id);
        abort_unless($content->type === ContentType::Assignment, 404);

        $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('lms.assignment_max_kb')]]);

        /** @var Assignment $assignment */
        $assignment = $content->contentable;
        $existing = AssignmentSubmission::where('assignment_id', $assignment->id)->where('user_id', $request->user()->id)->first();
        if ($existing) {
            Storage::disk('local')->delete($existing->file_path); // re-submitting replaces the old file
        }

        $path = $request->file('file')->store("assignment-submissions/{$assignment->id}", 'local');
        $submission = AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $request->user()->id],
            ['file_path' => $path, 'submitted_at' => now()],
        );

        return $this->data(['submittedAt' => $submission->submitted_at->toIso8601String()]);
    }
}
