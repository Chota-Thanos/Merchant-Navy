"use client";

import { BookOpen } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { CourseCard } from "@/components/course/CourseCard";
import { PageHeader } from "@/components/ui/PageHeader";
import { EmptyState, ErrorState, PageSkeleton } from "@/components/ui/States";

export default function MyCoursesPage() {
  const { data: courses, loading, error, reload } = useApi(() => api.courses.getMyCourses(), []);

  return (
    <>
      <PageHeader title="My Courses" subtitle="Courses assigned to your account" />
      {loading ? (
        <PageSkeleton />
      ) : error ? (
        <ErrorState message={error} onRetry={reload} />
      ) : !courses?.length ? (
        <EmptyState
          icon={BookOpen}
          title="No courses yet"
          body="Your purchased course will appear here as soon as our team assigns it to your account. You'll also get a notification."
        />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          {courses.map((c) => (
            <CourseCard key={c.id} course={c} />
          ))}
        </div>
      )}
    </>
  );
}
