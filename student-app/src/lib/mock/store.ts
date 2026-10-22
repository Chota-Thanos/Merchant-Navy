/**
 * MOCK persistence in the browser's localStorage, so the UI works end to end
 * (log in, attempt a test, see the result) before the backend exists.
 * Delete this file once lib/api talks to Laravel.
 */
import type { AnswerState, AppNotification, Student } from "@/types";

export interface MockAttempt {
  id: string;
  testId: number;
  attemptNo: number;
  startedAt: string;
  submittedAt?: string;
  timeTakenSeconds?: number;
  answers: Record<number, AnswerState>;
}

interface MockState {
  student: Student | null;
  attempts: MockAttempt[];
  completedContentIds: number[];
  readNotificationIds: string[];
}

const KEY = "mmn.mock.v1";

const daysAgo = (d: number) => new Date(Date.now() - d * 86400_000).toISOString();

/** Two finished attempts so the Results pages are not empty on first visit. */
function seedAttempts(): MockAttempt[] {
  const pick = (map: Record<number, number | null>): Record<number, AnswerState> =>
    Object.fromEntries(
      Object.entries(map).map(([q, opt]) => [q, { selectedOptionId: opt, markedForReview: false, visited: true }]),
    );
  return [
    {
      id: "a-201-1",
      testId: 201,
      attemptNo: 1,
      startedAt: daysAgo(3),
      submittedAt: daysAgo(3),
      timeTakenSeconds: 17 * 60 + 42,
      // option id = questionId * 10 + (index + 1)
      answers: pick({ 1: 12, 2: 23, 3: 31, 4: 42, 5: 52, 6: 62, 7: 72, 8: 81, 9: 92, 10: 102, 11: 113, 12: null, 13: 132 }),
    },
    {
      id: "a-101-1",
      testId: 101,
      attemptNo: 1,
      startedAt: daysAgo(6),
      submittedAt: daysAgo(6),
      timeTakenSeconds: 9 * 60 + 5,
      answers: pick({ 1: 12, 2: 23, 3: 31, 4: null, 5: 52, 6: 61, 7: 72, 8: 83 }),
    },
  ];
}

const defaultState = (): MockState => ({
  student: null,
  attempts: seedAttempts(),
  completedContentIds: [],
  readNotificationIds: [],
});

export function readState(): MockState {
  if (typeof window === "undefined") return defaultState();
  try {
    const raw = window.localStorage.getItem(KEY);
    return raw ? { ...defaultState(), ...JSON.parse(raw) } : defaultState();
  } catch {
    return defaultState();
  }
}

export function writeState(update: (s: MockState) => MockState) {
  const next = update(readState());
  try {
    window.localStorage.setItem(KEY, JSON.stringify(next));
  } catch {
    /* storage unavailable — mock data simply won't persist */
  }
  return next;
}

export const seedNotifications: AppNotification[] = [
  {
    id: "n1",
    type: "live_class",
    title: "Live class starting now",
    body: "Limits & Derivatives Doubt Session has started. Join from Mathematics → Calculus Basics.",
    href: "/learn/1201",
    createdAt: new Date(Date.now() - 18 * 60_000).toISOString(),
    read: false,
  },
  {
    id: "n2",
    type: "course_assigned",
    title: "New course assigned",
    body: "You now have access to Company Sponsorship Exam Prep.",
    href: "/my-courses/2",
    createdAt: "2026-10-10T10:00:00Z",
    read: false,
  },
  {
    id: "n3",
    type: "result",
    title: "Your result is ready",
    body: "IMU-CET Mock Test 1 — Physics & Chemistry. See your score, rank and solutions.",
    href: "/results/a-201-1",
    createdAt: daysAgo(3),
    read: true,
  },
  {
    id: "n4",
    type: "course_assigned",
    title: "Welcome aboard!",
    body: "IMU-CET 2027 Complete Course has been assigned to you. Start with Physics → Units, Dimensions & Errors.",
    href: "/my-courses/1",
    createdAt: "2026-09-02T10:00:00Z",
    read: true,
  },
];
