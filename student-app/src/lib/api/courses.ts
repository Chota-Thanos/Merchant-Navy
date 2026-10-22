/** Courses → subjects → chapters → content. Backend: app/Http/Controllers/Api/V1/CourseController.php */
import type { ContentItem, Course, CourseSummary, Subject } from "@/types";
import { USE_MOCK, mockDelay, request } from "./client";
import { mockCourses, mockSubjects } from "../mock/catalog";
import {
  findCourse,
  findSubject,
  findTest,
  toContentItem,
  toCourseSummary,
  toSubjectSummary,
  toTestSummary,
} from "../mock/mappers";
import { writeState } from "../mock/store";

/** Only the courses an admin has assigned to this student. */
export async function getMyCourses(): Promise<CourseSummary[]> {
  if (USE_MOCK) {
    await mockDelay();
    return mockCourses.map((c) => toCourseSummary(c.id));
  }
  return request<CourseSummary[]>("/courses");
}

export async function getCourse(id: number): Promise<Course> {
  if (USE_MOCK) {
    await mockDelay();
    const c = findCourse(id);
    if (!c) throw new Error("Course not found");
    return {
      ...toCourseSummary(id),
      curriculum: c.curriculum.map((item) =>
        "subjectId" in item
          ? { kind: "subject" as const, subject: toSubjectSummary(findSubject(item.subjectId)!) }
          : { kind: "test" as const, test: toTestSummary(findTest(item.testId)!) },
      ),
    };
  }
  return request<Course>(`/courses/${id}`);
}

export async function getSubject(id: number): Promise<Subject> {
  if (USE_MOCK) {
    await mockDelay();
    const s = findSubject(id);
    if (!s) throw new Error("Subject not found");
    return {
      id: s.id,
      courseId: s.courseId,
      courseTitle: findCourse(s.courseId)!.title,
      title: s.title,
      description: s.description,
      chapters: s.chapters.map((ch) => ({ id: ch.id, title: ch.title, contents: ch.contents.map(toContentItem) })),
    };
  }
  return request<Subject>(`/subjects/${id}`);
}

/** One content item plus its subject, so the player can show the chapter list beside it. */
export async function getContent(id: number): Promise<{ content: ContentItem; subject: Subject }> {
  if (USE_MOCK) {
    const subject = mockSubjects.find((s) => s.chapters.some((ch) => ch.contents.some((c) => c.id === id)));
    if (!subject) throw new Error("Content not found");
    const full = await getSubject(subject.id);
    const content = full.chapters.flatMap((ch) => ch.contents).find((c) => c.id === id)!;
    return { content, subject: full };
  }
  return request(`/contents/${id}`);
}

export async function markContentComplete(id: number): Promise<void> {
  if (USE_MOCK) {
    writeState((s) => ({ ...s, completedContentIds: [...new Set([...s.completedContentIds, id])] }));
    return;
  }
  await request(`/contents/${id}/complete`, { method: "POST" });
}

/**
 * Protected PDF pages. The server renders each page to an image with the
 * student's name + phone watermarked in, behind a short-lived signed URL.
 * The original PDF file is never sent to the browser.
 *
 * Returns `null` in mock mode (the viewer then shows sample pages) and an empty list while the
 * server is still preparing the PDF.
 */
export async function getPdfPageUrls(contentId: number): Promise<string[] | null> {
  if (USE_MOCK) return null;
  return request<string[]>(`/contents/${contentId}/pages`);
}
