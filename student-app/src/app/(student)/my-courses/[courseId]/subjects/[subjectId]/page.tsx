"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import { ChevronDown } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { cn } from "@/lib/utils";
import { ContentRow } from "@/components/course/ContentRow";
import { PageHeader } from "@/components/ui/PageHeader";
import { ErrorState, PageSkeleton } from "@/components/ui/States";

/** Subject page: chapters as an accordion, each with its list of content. */
export default function SubjectPage() {
  const { subjectId } = useParams<{ courseId: string; subjectId: string }>();
  const { data: subject, loading, error, reload } = useApi(() => api.courses.getSubject(Number(subjectId)), [subjectId]);
  const [closed, setClosed] = useState<Set<number>>(new Set());

  if (loading) return <PageSkeleton />;
  if (error || !subject) return <ErrorState message={error ?? "Subject not found"} onRetry={reload} />;

  const toggle = (id: number) =>
    setClosed((s) => {
      const next = new Set(s);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });

  return (
    <>
      <PageHeader
        title={subject.title}
        subtitle={subject.description}
        crumbs={[
          { label: "My Courses", href: "/my-courses" },
          { label: subject.courseTitle, href: `/my-courses/${subject.courseId}` },
          { label: subject.title },
        ]}
      />

      <div className="space-y-4">
        {subject.chapters.map((chapter, i) => {
          const done = chapter.contents.filter((c) => c.completed).length;
          const isOpen = !closed.has(chapter.id);
          return (
            <section key={chapter.id} className="overflow-hidden rounded-2xl border border-line bg-white">
              <button onClick={() => toggle(chapter.id)} className="flex w-full items-center gap-4 px-5 py-4 text-left hover:bg-slate-50" aria-expanded={isOpen}>
                <span className="text-xs font-bold uppercase tracking-wider text-brand-600">Chapter {i + 1}</span>
                <span className="flex-1 font-bold text-navy-900">{chapter.title}</span>
                <span className="hidden text-xs font-medium text-slate-500 sm:block">
                  {done}/{chapter.contents.length} completed
                </span>
                <ChevronDown className={cn("size-5 text-slate-400 transition", isOpen && "rotate-180")} />
              </button>
              {isOpen && (
                <div className="divide-y divide-line border-t border-line">
                  {chapter.contents.map((item) => (
                    <ContentRow key={item.id} item={item} />
                  ))}
                </div>
              )}
            </section>
          );
        })}
      </div>
    </>
  );
}
