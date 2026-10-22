# My Merchant Navy LMS — Project Plan

> Living document. Update it when a decision changes.

## 1. Decisions (from requirements Q&A)

| Area | Decision |
|---|---|
| Purchases | Paid on the existing site. **Admin manually assigns** courses to a registered student. No automatic sync. |
| Course validity | No expiry. Access lasts until an admin removes it. |
| Login | Mobile number + OTP. SMS via **Twilio** first, behind a driver interface so MSG91 / Fast2SMS / 2Factor can be added by config. |
| Devices | Max **2 active devices** per student. A 3rd login logs out the oldest device. |
| Profile | Name, nickname, email (no verification, with a note asking for a real email), target exam, year of attempt. |
| Notifications | In-app bell + email. Triggers: course assigned, live class starting soon, result ready. Channels are pluggable (SMS/WhatsApp/push later). |
| Content types | Protected PDF, practice test, recorded video, live class (Agora, later — optional fields), text note, assignment. |
| PDFs | Read-only. **The original PDF never reaches the browser**; pages are rendered server-side to images, watermarked with student name + phone, served by short-lived signed URLs. |
| Unlocking | Everything open. Students only ever see courses assigned to them. |
| Tests | 4-option single-correct MCQ only. No sections. One timer for the whole test. Always open. Unlimited attempts. English only. |
| Maths | LaTeX rendered with KaTeX in questions, options, explanations and result/solution pages. Images supported. |
| Ranking | Rank + percentile per test (based on each student's **first** attempt, so reattempts can't inflate rank). |
| Question entry | Manual editor + Excel import. AI PDF parsing is a later phase (hook designed now). |
| Anti-cheating | None. |
| Admins | Multiple admins, one role (all permissions). |
| Admin panel | **Laravel Filament**, inside the backend. |
| Scale | Up to ~1,000 students, up to ~1,000 concurrent test-takers. |
| Mobile app | Later. The API is versioned (`/api/v1`) and token-based so apps can reuse it. |

**Still open:** where recorded videos are hosted (YouTube unlisted, Bunny Stream, Vimeo, VdoCipher). The video model stores a `provider` and is designed so any of these can be plugged in.

## 2. Domains

| URL | What |
|---|---|
| `mymerchantnavy.com` | Existing website (unchanged) |
| `dashboard.mymerchantnavy.com` | Student LMS (Next.js) |
| `admin.mymerchantnavy.com` | Admin panel (Laravel Filament) |
| `api.mymerchantnavy.com` | Laravel REST API used by the student app (and later mobile apps) |

## 3. Repository layout

```
/backend          Laravel 12 — API + Filament admin + jobs + notifications
  app/
    Enums/              ContentType, TestKind, AttemptStatus …
    Filament/           Admin panel (Resources = one per entity)
    Http/Controllers/Api/V1/   Student API controllers, one per area
    Http/Resources/     JSON shapes returned to the frontend
    Models/
    Notifications/      CourseAssigned, LiveClassStartingSoon, ResultReady
    Services/
      Otp/              OtpSender interface + TwilioSender, Msg91Sender …
      Tests/            AttemptService, ScoringService, RankingService
      Pdf/              PdfRasterizer, WatermarkService
      QuestionImport/   ExcelImporter, (later) AiPdfParser
  database/migrations, seeders
  routes/api.php        All student endpoints under /api/v1
/student-app      Next.js (App Router, TypeScript, Tailwind)
  src/
    app/(auth)/         login, otp, profile setup
    app/(student)/      my-courses, results, mock-tests, profile, notifications
    app/test/[id]/      instructions, attempt (full screen, no sidebar)
    components/ui/      Buttons, cards, badges — generic building blocks
    components/<area>/  Course, test, result components
    lib/api/            One file per API area; switch mock → real here
    lib/mock/           Mock data used until the backend is connected
    types/              Shared TypeScript types (mirror API resources)
/docs             This plan, API reference, deployment notes
```

Rule of thumb: **to change something a student sees, look in `student-app/src/app/<page>`; to change admin, look in `backend/app/Filament/Resources/<Entity>`; to change business rules, look in `backend/app/Services`.**

## 4. Course structure & data model

```
Course
 └─ Curriculum (ordered list — mix of Subjects and Mock Tests)
     ├─ Subject
     │   └─ Chapter (ordered)
     │       └─ Chapter Content (ordered, any number, any type)
     │            PDF | Practice Test | Video | Live Class | Note | Assignment
     └─ Mock Test   ← can be placed between any two subjects
```

### Tables

| Table | Key columns |
|---|---|
| `users` (students) | phone (unique), name, nickname, email, target_exam, attempt_year, profile_completed_at |
| `admins` | name, email, password (Filament login) |
| `otp_codes` | phone, code_hash, expires_at, attempts |
| `personal_access_tokens` | Sanctum tokens; + `device_name`, `last_used_at` used for the 2-device limit |
| `courses` | title, slug, description, thumbnail, is_active |
| `enrollments` | user_id, course_id, assigned_by (admin), assigned_at |
| `curriculum_items` | course_id, item_type (`subject`/`test`), item_id, position |
| `subjects` | course_id, title, description, icon |
| `chapters` | subject_id, title, position |
| `chapter_contents` | chapter_id, type, title, position, content_type, content_id (polymorphic) |
| `pdf_documents` | file_path (private storage), page_count, processed_at |
| `videos` | provider, provider_ref/url, duration_seconds |
| `live_classes` | provider (agora/youtube), starts_at, ends_at, channel/join data (nullable), recording_video_id |
| `notes` | body (HTML + LaTeX) |
| `assignments` / `assignment_submissions` | instructions, attachment / user_id, file, submitted_at |
| `tests` | title, kind (`practice`/`mock`), duration_minutes, marks_correct, marks_wrong, instructions (HTML), is_published |
| `questions` | body (HTML + LaTeX), explanation, difficulty, subject_id/chapter_id tags (question bank) |
| `question_options` | question_id, label (A–D), body, is_correct |
| `test_questions` | test_id, question_id, position (a question can be reused in many tests) |
| `test_attempts` | user_id, test_id, attempt_no, started_at, submitted_at, time_taken_seconds, score, correct, incorrect, skipped, status |
| `attempt_answers` | attempt_id, question_id, selected_option_id, marked_for_review, visited, time_spent_seconds |
| `content_progress` | user_id, chapter_content_id, completed_at |
| `notifications` | Laravel's built-in database notifications |

## 5. Test engine rules

1. **Start** → server creates an attempt and records `started_at`. The deadline is `started_at + duration`; the server is the source of truth for time.
2. While answering, each Save & Next / Mark for Review is saved to the server (so a closed browser can resume with the timer still running).
3. Palette states: Not visited, Not answered, Answered, Marked for review, Answered & marked (counts for evaluation).
4. **Submit** (or deadline reached → auto-submit, also enforced by a scheduled job) → score = correct × marks_correct − incorrect × marks_wrong; `time_taken_seconds` stored.
5. **Result page**: score, correct / incorrect / skipped, accuracy, completion, time taken, rank, percentile, attempt switcher, Reattempt, View Solutions.
6. **Solutions page**: every question with the student's answer, correct answer and explanation (KaTeX rendered).
7. With 1,000 concurrent students, answer saves are small writes; rank/percentile is computed on submit and cached.

## 6. Student app screens (Next.js)

| Route | Screen |
|---|---|
| `/login` | Mobile number |
| `/login/verify` | OTP entry, resend timer |
| `/onboarding` | Profile setup (first login) |
| `/my-courses` | Assigned courses (empty state: "Your course will appear once assigned") |
| `/my-courses/[course]` | Curriculum: subjects + mock tests in order |
| `/my-courses/[course]/subjects/[subject]` | Chapters as an accordion; each opens a Udemy-style content list |
| `/learn/[content]` | Content player: PDF viewer / video / live / note / assignment |
| `/tests/[test]` | Instructions + Start |
| `/tests/[test]/attempt` | Full-screen test UI (timer, palette, Save & Next, Mark for Review, Submit). Starts a new attempt or resumes the unfinished one. |
| `/results` | All attempts |
| `/results/[attempt]` | Result summary on top, detailed solutions below (filter: all / correct / incorrect / skipped) |
| `/mock-tests` | All mock tests across assigned courses |
| `/notifications` | Notification list (+ bell in header) |
| `/profile` | Edit profile, active devices |

Sidebar: **My Courses, Mock Tests, Results, My Profile**.

## 7. Admin panel (Filament)

- **Dashboard** — students, enrollments, attempts today, recent results.
- **Students** — list/search by phone; view profile, courses, attempts; **Assign course** action (sends notification); remove access; log out devices.
- **Courses** — details → **Curriculum builder**: drag-and-drop list of Subjects and Mock Tests → inside a subject, drag-and-drop Chapters → inside a chapter, drag-and-drop Content with an "Add content" menu per type.
- **Tests** — details, marking, duration, instructions; question list with drag-and-drop order; **Add question** (editor with live KaTeX preview), **Pick from question bank**, **Import from Excel** (template download, validation report before saving).
- **Question bank** — filter by subject/chapter; reuse in many tests.
- **Live classes** — schedule; "starting soon" notifications are sent automatically.
- **Reports** — enrollments, student activity, results per test (export), question-wise analysis (% correct / wrong / skipped per question).
- **Admins** — add/remove admin users.
- **Settings** — SMS provider credentials, notification templates.

## 8. API (v1, all JSON, Sanctum bearer token)

```
POST /auth/otp/send            POST /auth/otp/verify        POST /auth/logout
GET  /me                       PUT  /me                     GET  /me/devices   DELETE /me/devices/{id}
GET  /courses                  GET  /courses/{id}           (curriculum)
GET  /subjects/{id}            (chapters + contents)
GET  /contents/{id}            POST /contents/{id}/complete
GET  /pdfs/{id}/pages/{n}      (signed, watermarked image)
GET  /mock-tests
GET  /tests/{id}               POST /tests/{id}/attempts    (start or resume)
GET  /attempts/{id}            PUT  /attempts/{id}/answers/{question}
POST /attempts/{id}/submit     GET  /attempts/{id}/result   GET /attempts/{id}/solutions
GET  /results
GET  /notifications            POST /notifications/read
```

## 9. Milestones

1. **Student UI with mock data** — every screen above, clickable end to end. ✅
2. **Backend foundation** — Laravel 12, PostgreSQL, migrations for all tables, Sanctum, OTP (Twilio driver), device limit. ✅ (login + profile API done and tested)
3. **Admin panel** — Filament resources, course builder (outline), test question builder, assign course. ✅ Question import (PDF automatic reader, PDF with AI, Excel) with a review list ✅
4. **Connect frontend to API** — courses, content, tests, attempts, results, rank/percentile, notifications. ✅ (student app runs on the real API with `NEXT_PUBLIC_USE_MOCK=false`)
5. **Content delivery** — PDF page rendering + watermark ✅ (needs `pdftoppm` on the server), assignments upload ✅, notifications in-app + email + scheduled live reminders ✅, video embeds (YouTube/Vimeo) ✅. Still to do: Bunny/VdoCipher embeds once a host is chosen.
6. **Reports** — admin analytics + exports.
7. **Deploy** — VPS, Nginx, queues (Redis), scheduled jobs, backups, SSL for the three subdomains.
8. **Later** — Agora live classes, AI PDF question parsing, mobile apps, extra OTP/notification channels.
