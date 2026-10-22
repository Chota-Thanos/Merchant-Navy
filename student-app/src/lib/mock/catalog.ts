/**
 * MOCK courses, subjects, chapters, content and tests (replaced by the API later).
 * Shapes follow what the admin builds in the panel:
 *   Course → curriculum (subjects + mock tests in any order) → chapters → content items.
 */
import type { ContentItem, TestKind } from "@/types";

export interface MockTest {
  id: number;
  title: string;
  kind: TestKind;
  courseId: number;
  durationMinutes: number;
  marksCorrect: number;
  marksWrong: number;
  questionIds: number[];
}

type PracticeTestItem = Extract<ContentItem, { type: "practice_test" }>;
export type MockContent =
  | Exclude<ContentItem, { type: "practice_test" }>
  | (Omit<PracticeTestItem, "test"> & { testId: number });

export interface MockChapter {
  id: number;
  title: string;
  contents: MockContent[];
}

export interface MockSubject {
  id: number;
  courseId: number;
  title: string;
  description: string;
  chapters: MockChapter[];
}

export interface MockCourse {
  id: number;
  title: string;
  description: string;
  accent: string;
  assignedAt: string;
  curriculum: ({ subjectId: number } | { testId: number })[];
}

const hoursFromNow = (h: number) => new Date(Date.now() + h * 3600_000).toISOString();
const range = (from: number, to: number) => Array.from({ length: to - from + 1 }, (_, i) => from + i);

// ---------------- Tests ----------------

export const mockTests: MockTest[] = [
  { id: 101, title: "Units, Dimensions & Mechanics — Practice", kind: "practice", courseId: 1, durationMinutes: 12, marksCorrect: 4, marksWrong: 1, questionIds: range(1, 8) },
  { id: 102, title: "Mole Concept — Practice", kind: "practice", courseId: 1, durationMinutes: 10, marksCorrect: 4, marksWrong: 1, questionIds: range(9, 13) },
  { id: 103, title: "Calculus & Algebra — Practice", kind: "practice", courseId: 1, durationMinutes: 15, marksCorrect: 4, marksWrong: 1, questionIds: range(14, 20) },
  { id: 104, title: "Vocabulary — Practice", kind: "practice", courseId: 1, durationMinutes: 5, marksCorrect: 4, marksWrong: 1, questionIds: [21, 22, 23] },
  { id: 201, title: "IMU-CET Mock Test 1 — Physics & Chemistry", kind: "mock", courseId: 1, durationMinutes: 20, marksCorrect: 4, marksWrong: 1, questionIds: range(1, 13) },
  { id: 202, title: "IMU-CET Full Mock Test 2", kind: "mock", courseId: 1, durationMinutes: 40, marksCorrect: 4, marksWrong: 1, questionIds: range(1, 25) },
  { id: 301, title: "Sponsorship Mock Test 1", kind: "mock", courseId: 2, durationMinutes: 15, marksCorrect: 3, marksWrong: 1, questionIds: [24, 25, 6, 21, 22, 23, 5, 9, 15, 20] },
];

// ---------------- Courses ----------------

export const mockCourses: MockCourse[] = [
  {
    id: 1,
    title: "IMU-CET 2027 Complete Course",
    description: "Physics, Chemistry, Mathematics and English for the IMU Common Entrance Test, with chapter tests and full-length mocks.",
    accent: "from-navy-900 via-navy-800 to-brand-600",
    assignedAt: "2026-09-02T10:00:00Z",
    curriculum: [{ subjectId: 11 }, { subjectId: 12 }, { testId: 201 }, { subjectId: 13 }, { subjectId: 14 }, { testId: 202 }],
  },
  {
    id: 2,
    title: "Company Sponsorship Exam Prep",
    description: "Aptitude, English and basic ship knowledge for shipping-company sponsorship tests and interviews.",
    accent: "from-brand-700 via-brand-600 to-sky-500",
    assignedAt: "2026-10-10T10:00:00Z",
    curriculum: [{ subjectId: 21 }, { subjectId: 22 }, { testId: 301 }],
  },
];

// ---------------- Subjects → Chapters → Content ----------------

const sampleNote = `
<p>Every physical quantity can be written in terms of the seven base quantities. The <b>dimensional formula</b> shows how.</p>
<h3>Common dimensional formulae</h3>
<ul>
  <li>Velocity: $[LT^{-1}]$</li>
  <li>Force: $F = ma \\Rightarrow [MLT^{-2}]$</li>
  <li>Energy: $[ML^2T^{-2}]$</li>
  <li>Pressure: $P = \\dfrac{F}{A} \\Rightarrow [ML^{-1}T^{-2}]$</li>
</ul>
<h3>Principle of homogeneity</h3>
<p>Each term of a correct equation has the same dimensions. For $s = ut + \\tfrac{1}{2}at^2$:</p>
$$[L] = [LT^{-1}][T] + [LT^{-2}][T^2] = [L]$$
<p>Use this to check formulas quickly in the exam — it eliminates wrong options in seconds.</p>
`;

const buoyancyNote = `
<p><b>Archimedes' principle:</b> a body immersed in a fluid experiences an upward force equal to the weight of the fluid it displaces.</p>
$$F_B = \\rho_{\\text{fluid}} \\, V_{\\text{submerged}} \\, g$$
<p>A ship floats when $F_B = W$. Because sea water ($\\rho \\approx 1025\\,\\text{kg/m}^3$) is denser than fresh water ($1000\\,\\text{kg/m}^3$), a ship floats higher at sea — the reason for the <b>Plimsoll (load) line</b> marks.</p>
`;

export const mockSubjects: MockSubject[] = [
  {
    id: 11,
    courseId: 1,
    title: "Physics",
    description: "Mechanics, fluids, heat, electricity and modern physics.",
    chapters: [
      {
        id: 111,
        title: "Units, Dimensions & Errors",
        contents: [
          { id: 1001, chapterId: 111, type: "live_class", title: "Live: Dimensional Analysis Problem Solving", startsAt: hoursFromNow(26), durationMinutes: 90, status: "upcoming", completed: false },
          { id: 1002, chapterId: 111, type: "video", title: "Introduction to SI Units", durationSeconds: 38 * 60 + 12, provider: "youtube", completed: true },
          { id: 1003, chapterId: 111, type: "pdf", title: "Chapter Notes — Units & Dimensions", pageCount: 24, completed: true },
          { id: 1004, chapterId: 111, type: "note", title: "Dimensional Analysis — Quick Summary", readMinutes: 4, body: sampleNote, completed: false },
          { id: 1005, chapterId: 111, type: "practice_test", title: "Practice Test: Units, Dimensions & Mechanics", testId: 101, completed: false },
          { id: 1006, chapterId: 111, type: "assignment", title: "Assignment: Dimensional Analysis Worksheet", dueAt: hoursFromNow(24 * 5), instructions: "<p>Solve all 15 questions in the worksheet PDF on paper. Scan or photograph your answers and upload them as a single PDF.</p><p>Show the dimensional formula for every quantity you use.</p>", completed: false },
        ],
      },
      {
        id: 112,
        title: "Kinematics",
        contents: [
          { id: 1011, chapterId: 112, type: "live_class", title: "Live: Projectile Motion Masterclass", startsAt: hoursFromNow(-72), durationMinutes: 75, status: "ended", recording: { durationSeconds: 74 * 60 }, completed: true },
          { id: 1012, chapterId: 112, type: "video", title: "Equations of Motion", durationSeconds: 52 * 60 + 40, provider: "youtube", completed: false },
          { id: 1013, chapterId: 112, type: "pdf", title: "Kinematics — Formula Sheet", pageCount: 6, completed: false },
        ],
      },
      {
        id: 113,
        title: "Fluid Mechanics & Buoyancy",
        contents: [
          { id: 1021, chapterId: 113, type: "video", title: "Why Ships Float — Archimedes' Principle", durationSeconds: 27 * 60 + 5, provider: "youtube", completed: false },
          { id: 1022, chapterId: 113, type: "note", title: "Buoyancy & the Plimsoll Line", readMinutes: 3, body: buoyancyNote, completed: false },
          { id: 1023, chapterId: 113, type: "pdf", title: "Fluid Mechanics — Solved Examples", pageCount: 14, completed: false },
        ],
      },
    ],
  },
  {
    id: 12,
    courseId: 1,
    title: "Chemistry",
    description: "Physical, inorganic and organic chemistry essentials.",
    chapters: [
      {
        id: 121,
        title: "Mole Concept",
        contents: [
          { id: 1101, chapterId: 121, type: "video", title: "Mole, Molar Mass & Avogadro's Number", durationSeconds: 44 * 60, provider: "youtube", completed: true },
          { id: 1102, chapterId: 121, type: "pdf", title: "Mole Concept — Notes", pageCount: 18, completed: false },
          { id: 1103, chapterId: 121, type: "practice_test", title: "Practice Test: Mole Concept", testId: 102, completed: false },
        ],
      },
      {
        id: 122,
        title: "Chemical Bonding",
        contents: [
          { id: 1111, chapterId: 122, type: "video", title: "Hybridisation Made Simple", durationSeconds: 36 * 60 + 20, provider: "youtube", completed: false },
          { id: 1112, chapterId: 122, type: "pdf", title: "Chemical Bonding — Notes", pageCount: 22, completed: false },
        ],
      },
    ],
  },
  {
    id: 13,
    courseId: 1,
    title: "Mathematics",
    description: "Algebra, calculus, trigonometry, coordinate geometry and probability.",
    chapters: [
      {
        id: 131,
        title: "Calculus Basics",
        contents: [
          { id: 1201, chapterId: 131, type: "live_class", title: "Live: Limits & Derivatives Doubt Session", startsAt: hoursFromNow(-0.3), durationMinutes: 60, status: "live", completed: false },
          { id: 1202, chapterId: 131, type: "video", title: "Limits — Standard Results", durationSeconds: 41 * 60 + 18, provider: "youtube", completed: false },
          { id: 1203, chapterId: 131, type: "pdf", title: "Integration Formula Sheet", pageCount: 8, completed: false },
          { id: 1204, chapterId: 131, type: "practice_test", title: "Practice Test: Calculus & Algebra", testId: 103, completed: false },
        ],
      },
      {
        id: 132,
        title: "Quadratic Equations",
        contents: [
          { id: 1211, chapterId: 132, type: "video", title: "Roots & Coefficients", durationSeconds: 33 * 60, provider: "youtube", completed: false },
          { id: 1212, chapterId: 132, type: "pdf", title: "Quadratic Equations — Practice Sheet", pageCount: 10, completed: false },
        ],
      },
    ],
  },
  {
    id: 14,
    courseId: 1,
    title: "English & Aptitude",
    description: "Vocabulary, grammar, comprehension and quantitative aptitude.",
    chapters: [
      {
        id: 141,
        title: "Vocabulary Builder",
        contents: [
          { id: 1301, chapterId: 141, type: "video", title: "Synonyms & Antonyms Strategy", durationSeconds: 22 * 60, provider: "youtube", completed: false },
          { id: 1302, chapterId: 141, type: "practice_test", title: "Practice Test: Vocabulary", testId: 104, completed: false },
        ],
      },
    ],
  },
  {
    id: 21,
    courseId: 2,
    title: "Aptitude & Reasoning",
    description: "Speed–distance, percentages, ratios and logical reasoning.",
    chapters: [
      {
        id: 211,
        title: "Speed, Time & Distance",
        contents: [
          { id: 2001, chapterId: 211, type: "video", title: "Relative Speed for Ships & Trains", durationSeconds: 31 * 60, provider: "youtube", completed: false },
          { id: 2002, chapterId: 211, type: "pdf", title: "Speed, Time & Distance — Notes", pageCount: 12, completed: false },
        ],
      },
    ],
  },
  {
    id: 22,
    courseId: 2,
    title: "English Communication",
    description: "Comprehension, grammar and interview English.",
    chapters: [
      {
        id: 221,
        title: "Reading Comprehension",
        contents: [
          { id: 2101, chapterId: 221, type: "video", title: "How to Attack a Passage", durationSeconds: 26 * 60, provider: "youtube", completed: false },
          { id: 2102, chapterId: 221, type: "pdf", title: "Comprehension Practice Set", pageCount: 9, completed: false },
        ],
      },
    ],
  },
];
