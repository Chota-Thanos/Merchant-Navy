"use client";

import { useParams } from "next/navigation";
import { ClipboardList } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { TestCard } from "@/components/test/TestCard";
import { PageHeader } from "@/components/ui/PageHeader";
import { EmptyState, ErrorState, PageSkeleton } from "@/components/ui/States";

/** Step 2: only the mock tests of the chosen course (practice tests stay inside their chapters). */
export default function CourseMockTestsPage() {
  const { courseId } = useParams<{ courseId: string }>();
  const { data, loading, error, reload } = useApi(async () => {
    const [course, tests] = await Promise.all([api.courses.getCourse(Number(courseId)), api.tests.getMockTests()]);
    return { course, tests: tests.filter((t) => t.courseId === Number(courseId)) };
  }, [courseId]);

  if (loading) return <PageSkeleton />;
  if (error || !data) return <ErrorState message={error ?? "Course not found"} onRetry={reload} />;

  return (
    <>
      <PageHeader
        title={data.course.title}
        subtitle="Mock tests — attempt as many times as you like"
        crumbs={[{ label: "Mock Tests", href: "/mock-tests" }, { label: data.course.title }]}
      />
      {data.tests.length === 0 ? (
        <EmptyState icon={ClipboardList} title="No mock tests in this course yet" />
      ) : (
        <div className="space-y-3">
          {data.tests.map((t) => (
            <TestCard key={t.id} test={t} />
          ))}
        </div>
      )}
    </>
  );
}
