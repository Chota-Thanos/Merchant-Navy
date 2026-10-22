/** Practice & mock tests and attempts. Backend: app/Http/Controllers/Api/V1/TestController.php + AttemptController.php */
import type { AnswerState, TestDetail, TestSummary } from "@/types";
import { USE_MOCK, mockDelay, request } from "./client";
import { mockCourses, mockTests } from "../mock/catalog";
import { findTest, toQuestion, toTestSummary } from "../mock/mappers";
import { readState, writeState, type MockAttempt } from "../mock/store";

/** All mock tests across the student's assigned courses. */
export async function getMockTests(): Promise<TestSummary[]> {
  if (USE_MOCK) {
    await mockDelay();
    const courseIds = mockCourses.map((c) => c.id);
    return mockTests.filter((t) => t.kind === "mock" && courseIds.includes(t.courseId)).map(toTestSummary);
  }
  return request<TestSummary[]>("/mock-tests");
}

export async function getTest(id: number): Promise<TestSummary> {
  if (USE_MOCK) {
    await mockDelay();
    const t = findTest(id);
    if (!t) throw new Error("Test not found");
    return toTestSummary(t);
  }
  return request<TestSummary>(`/tests/${id}`);
}

export interface AttemptSession {
  attemptId: string;
  /** Server time when the test must end (ISO). The timer counts down to this. */
  deadline: string;
  startedAt: string;
  test: TestDetail;
  answers: Record<number, AnswerState>;
}

/** Starts a new attempt, or resumes the unfinished one (e.g. after the browser was closed). */
export async function startAttempt(testId: number): Promise<AttemptSession> {
  if (USE_MOCK) {
    const t = findTest(testId);
    if (!t) throw new Error("Test not found");
    // Find-or-create happens before any await so two quick calls can't create two attempts.
    let attempt = readState().attempts.find((a) => a.testId === testId && !a.submittedAt);
    if (!attempt) {
      const attemptNo = readState().attempts.filter((a) => a.testId === testId).length + 1;
      const created: MockAttempt = {
        id: `a-${testId}-${attemptNo}-${Date.now().toString(36)}`,
        testId,
        attemptNo,
        startedAt: new Date().toISOString(),
        answers: {},
      };
      writeState((s) => ({ ...s, attempts: [...s.attempts, created] }));
      attempt = created;
    }
    const deadline = new Date(new Date(attempt.startedAt).getTime() + t.durationMinutes * 60_000).toISOString();
    await mockDelay(300);
    return {
      attemptId: attempt.id,
      startedAt: attempt.startedAt,
      deadline,
      answers: attempt.answers,
      test: { ...toTestSummary(t), questions: t.questionIds.map(toQuestion) },
    };
  }
  return request<AttemptSession>(`/tests/${testId}/attempts`, { method: "POST" });
}

/** Saved on every Save & Next / Mark for Review so a closed browser can resume. */
export async function saveAnswer(attemptId: string, questionId: number, answer: AnswerState): Promise<void> {
  if (USE_MOCK) {
    writeState((s) => ({
      ...s,
      attempts: s.attempts.map((a) => (a.id === attemptId ? { ...a, answers: { ...a.answers, [questionId]: answer } } : a)),
    }));
    return;
  }
  await request(`/attempts/${attemptId}/answers/${questionId}`, { method: "PUT", body: JSON.stringify(answer) });
}

/** Ends the attempt. The server stops the clock, scores it and records the time taken. */
export async function submitAttempt(attemptId: string, answers: Record<number, AnswerState>): Promise<{ attemptId: string }> {
  if (USE_MOCK) {
    await mockDelay(500);
    const now = new Date();
    writeState((s) => ({
      ...s,
      attempts: s.attempts.map((a) => {
        if (a.id !== attemptId) return a;
        const t = findTest(a.testId)!;
        const elapsed = Math.round((now.getTime() - new Date(a.startedAt).getTime()) / 1000);
        return {
          ...a,
          answers,
          submittedAt: now.toISOString(),
          timeTakenSeconds: Math.min(elapsed, t.durationMinutes * 60),
        };
      }),
    }));
    return { attemptId };
  }
  return request(`/attempts/${attemptId}/submit`, { method: "POST", body: JSON.stringify({ answers }) });
}
