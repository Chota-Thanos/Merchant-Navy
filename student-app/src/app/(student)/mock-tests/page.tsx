"use client";

import Link from "next/link";
import { ArrowRight, ClipboardList } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { cn } from "@/lib/utils";
import { PageHeader } from "@/components/ui/PageHeader";
import { EmptyState, ErrorState, PageSkeleton } from "@/components/ui/States";

/** Step 1: pick a course. Its mock tests (only mock tests) are listed on the next page. */
export default function MockTestCoursesPage() {
  const { data, loading, error, reload } = useApi(async () => {
    const [courses, tests] = await Promise.all([api.courses.getMyCourses(), api.tests.getMockTests()]);
    return courses
      .map((c) => ({ course: c, tests: tests.filter((t) => t.courseId === c.id) }))
      .filter((x) => x.tests.length > 0);
  }, []);

  return (
    <>
      <PageHeader title="Mock Tests" subtitle="Choose a course to see its full-length mock tests" />
      {loading ? (
        <PageSkeleton />
      ) : error ? (
        <ErrorState message={error} onRetry={reload} />
      ) : !data?.length ? (
        <EmptyState icon={ClipboardList} title="No mock tests yet" body="Mock tests added to your courses will show up here." />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          {data.map(({ course, tests }) => {
            const attempted = tests.filter((t) => t.attemptsCount > 0).length;
            return (
              <Link
                key={course.id}
                href={`/mock-tests/${course.id}`}
                className="group flex flex-col overflow-hidden rounded-2xl border border-line bg-white transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-600/5"
              >
                <div className={cn("flex h-24 items-end bg-gradient-to-br p-5", course.accent)}>
                  <span className="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white ring-1 ring-white/25">
                    {tests.length} mock test{tests.length > 1 ? "s" : ""}
                  </span>
                </div>
                <div className="p-5">
                  <h3 className="text-lg font-bold text-navy-900 group-hover:text-brand-700">{course.title}</h3>
                  <p className="mt-1 text-sm text-slate-500">
                    {attempted} of {tests.length} attempted
                  </p>
                  <span className="mt-4 flex items-center gap-1.5 text-sm font-semibold text-brand-600">
                    View mock tests <ArrowRight className="size-4 transition group-hover:translate-x-0.5" />
                  </span>
                </div>
              </Link>
            );
          })}
        </div>
      )}
    </>
  );
}
