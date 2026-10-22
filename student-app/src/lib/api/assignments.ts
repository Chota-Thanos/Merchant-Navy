/** Assignment uploads. Backend: ContentController::submitAssignment */
import { USE_MOCK, mockDelay, request } from "./client";

/** Uploads the student's answer (PDF or image). Returns when it was submitted (ISO). */
export async function submit(contentId: number, file: File): Promise<string> {
  if (USE_MOCK) {
    await mockDelay(600);
    return new Date().toISOString();
  }
  const body = new FormData();
  body.append("file", file);
  const res = await request<{ submittedAt: string }>(`/contents/${contentId}/submission`, { method: "POST", body });
  return res.submittedAt;
}
