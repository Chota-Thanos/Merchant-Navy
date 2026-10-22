"use client";

import { useParams } from "next/navigation";
import { Layers, Target, TrendingUp } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { cn } from "@/lib/utils";
import { SubjectCard } from "@/components/course/SubjectCard";
import { TestCard } from "@/components/test/TestCard";
import { PageHeader } from "@/components/ui/PageHeader";
import { ProgressBar } from "@/components/ui/ProgressBar";
import { ErrorState, PageSkeleton } from "@/components/ui/States";

/** Course curriculum: subjects and mock tests in the order the admin arranged them. */
export default function CoursePage() {
  const { courseId } = useParams<{ courseId: string }>();
  const { data: course, loading, error, reload } = useApi(() => api.courses.getCourse(Number(courseId)), [courseId]);

  if (loading) return <PageSkeleton />;
  if (error || !course) return <ErrorState message={error ?? "Course not found"} onRetry={reload} />;

  let subjectNo = 0;

  return (
    <>
      <PageHeader title={course.title} crumbs={[{ label: "My Courses", href: "/my-courses" }, { label: course.title }]} />

      <section className={cn("relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br p-6 text-white sm:p-8", course.accent)}>
        <div aria-hidden className="absolute -right-10 -top-10 size-56 rounded-full bg-white/10 blur-2xl" />
        <p className="relative max-w-2xl text-sm text-white/80 sm:text-base">{course.description}</p>
        <div className="relative mt-6 grid gap-4 sm:grid-cols-3">
          <Stat icon={Layers} label="Subjects" value={`${course.subjectsCount} · ${course.chaptersCount} chapters`} />
          <Stat icon={Target} label="Tests" value={`${course.testsCount} practice & mock`} />
          <div className="rounded-xl bg-white/10 p-4 ring-1 ring-white/15">
            <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-white/70">
              <TrendingUp className="size-4" /> Progress
            </p>
            <p className="mt-1 text-lg font-bold">{course.progressPercent}% complete</p>
            <ProgressBar value={course.progressPercent} className="mt-2 bg-white/20" barClassName="bg-white" />
          </div>
        </div>
      </section>

      <h2 className="mb-4 text-lg font-bold text-navy-900">Course content</h2>
      <ol className="space-y-3">
        {course.curriculum.map((item) =>
          item.kind === "subject" ? (
            <li key={`s-${item.subject.id}`}>
              <SubjectCard subject={item.subject} courseId={course.id} index={++subjectNo} />
            </li>
          ) : (
            <li key={`t-${item.test.id}`}>
              <TestCard test={item.test} className="border-navy-900/15 bg-gradient-to-r from-slate-50 to-white" />
            </li>
          ),
        )}
      </ol>
    </>
  );
}

function Stat({ icon: Icon, label, value }: { icon: React.ElementType; label: string; value: string }) {
  return (
    <div className="rounded-xl bg-white/10 p-4 ring-1 ring-white/15">
      <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-white/70">
        <Icon className="size-4" /> {label}
      </p>
      <p className="mt-1 text-lg font-bold">{value}</p>
    </div>
  );
}
