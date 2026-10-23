"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { ArrowLeft, ArrowRight, CircleCheck, Clock, FileQuestion, Trophy } from "lucide-react";
import * as api from "@/lib/api";
import type { ContentItem } from "@/types";
import { useApi } from "@/lib/use-api";
import { ContentRow } from "@/components/course/ContentRow";
import { contentTypes } from "@/components/course/contentTypes";
import { AssignmentView } from "@/components/content/AssignmentView";
import { ReachedEnd } from "@/components/content/ReachedEnd";
import { LiveClassView } from "@/components/content/LiveClassView";
import { ProtectedPdfViewer } from "@/components/content/ProtectedPdfViewer";
import { VideoPlayer } from "@/components/content/VideoPlayer";
import { MathText } from "@/components/math/MathText";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { PageHeader } from "@/components/ui/PageHeader";
import { ErrorState, PageSkeleton } from "@/components/ui/States";

/** Content player: the item on the left, the subject's chapter list on the right (Udemy style). */
export default function LearnPage() {
  const { contentId } = useParams<{ contentId: string }>();
  const id = Number(contentId);
  const { data, loading, error, reload, setData } = useApi(() => api.courses.getContent(id), [id]);

  if (loading) return <PageSkeleton />;
  if (error || !data) return <ErrorState message={error ?? "Content not found"} onRetry={reload} />;

  const { content, subject } = data;
  const all = subject.chapters.flatMap((ch) => ch.contents);
  const index = all.findIndex((c) => c.id === content.id);
  const prev = all[index - 1];
  const next = all[index + 1];
  const chapter = subject.chapters.find((ch) => ch.id === content.chapterId)!;
  let chapterNo = 0;

  // Reading, watching or listening count as done when the student reaches the end or moves on. A test or an assignment
  // counts only when it is actually taken or handed in (the server records that), so those are never auto-completed here.
  const autoCompletes = !content.completed && ["video", "note", "pdf", "live_class"].includes(content.type);
  const onDone = autoCompletes ? () => void markComplete() : undefined;

  async function markComplete() {
    await api.courses.markContentComplete(content.id);
    setData((d) =>
      d && {
        content: { ...d.content, completed: true },
        subject: {
          ...d.subject,
          chapters: d.subject.chapters.map((ch) => ({
            ...ch,
            contents: ch.contents.map((c) => (c.id === content.id ? { ...c, completed: true } : c)),
          })),
        },
      },
    );
  }

  return (
    <>
      <PageHeader
        title={content.title}
        crumbs={[
          { label: subject.courseTitle, href: `/my-courses/${subject.courseId}` },
          { label: subject.title, href: `/my-courses/${subject.courseId}/subjects/${subject.id}` },
          { label: chapter.title },
        ]}
        action={<Badge tone="blue">{contentTypes[content.type].label}</Badge>}
      />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div className="min-w-0 space-y-4">
          <ContentBody item={content} onDone={onDone} />

          <div className="flex flex-wrap items-center gap-2">
            {content.completed ? (
              <span className="flex items-center gap-1.5 text-sm font-semibold text-green-700">
                <CircleCheck className="size-5" /> Completed
              </span>
            ) : content.type !== "practice_test" ? (
              <Button variant="outline" onClick={markComplete}>
                <CircleCheck className="size-4" /> Mark as complete
              </Button>
            ) : null}
            <div className="ml-auto flex gap-2">
              {prev && (
                <Button variant="ghost" href={`/learn/${prev.id}`}>
                  <ArrowLeft className="size-4" /> Previous
                </Button>
              )}
              {next && (
                <Button href={`/learn/${next.id}`} onClick={onDone}>
                  Next <ArrowRight className="size-4" />
                </Button>
              )}
            </div>
          </div>
        </div>

        <aside className="lg:sticky lg:top-20 lg:self-start">
          <Card className="overflow-hidden">
            <div className="border-b border-line px-4 py-3">
              <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{subject.title}</p>
              <p className="font-bold text-navy-900">Chapter content</p>
            </div>
            <div className="max-h-[70vh] overflow-y-auto p-2">
              {subject.chapters.map((ch) =>
                ch.test ? (
                  <Link
                    key={ch.id}
                    href={`/tests/${ch.test.id}`}
                    className="mb-2 flex items-center gap-2 rounded-lg bg-navy-900/5 px-3 py-2.5 text-sm font-bold text-navy-900 hover:bg-navy-900/10"
                  >
                    <Trophy className="size-4 text-brand-600" /> {ch.test.title}
                    <span className="ml-auto text-xs font-medium text-slate-500">Mock test</span>
                  </Link>
                ) : (
                <div key={ch.id} className="mb-2">
                  <p className="px-3 pb-1 pt-2 text-xs font-bold text-slate-500">
                    {++chapterNo}. {ch.title}
                  </p>
                  {ch.contents.map((c) => (
                    <ContentRow key={c.id} item={c} active={c.id === content.id} compact />
                  ))}
                </div>
                ),
              )}
            </div>
          </Card>
        </aside>
      </div>
    </>
  );
}

/** Picks the viewer for each content type. Add a case here for a new type. */
function ContentBody({ item, onDone }: { item: ContentItem; onDone?: () => void }) {
  switch (item.type) {
    case "video":
      return <VideoPlayer title={item.title} embedUrl={item.embedUrl} durationSeconds={item.durationSeconds} />;
    case "live_class":
      return <LiveClassView item={item} />;
    case "pdf":
      return <ProtectedPdfViewer contentId={item.id} pageCount={item.pageCount} onReachEnd={onDone} />;
    case "note":
      return (
        <Card className="p-6 sm:p-8">
          <MathText html={item.body} className="text-[15px] leading-relaxed text-slate-700" />
          {onDone && <ReachedEnd onReach={onDone} />}
        </Card>
      );
    case "assignment":
      return <AssignmentView item={item} />;
    case "practice_test":
      return (
        <Card className="flex flex-col items-center p-8 text-center sm:p-12">
          <span className="grid size-16 place-items-center rounded-2xl bg-green-50 text-green-600">
            <FileQuestion className="size-8" />
          </span>
          <h2 className="mt-4 text-xl font-bold text-navy-900">{item.test.title}</h2>
          <p className="mt-2 flex flex-wrap justify-center gap-x-4 text-sm text-slate-500">
            <span>{item.test.questionsCount} questions</span>
            <span>{item.test.totalMarks} marks</span>
            <span className="flex items-center gap-1">
              <Clock className="size-4" /> {item.test.durationMinutes} min
            </span>
            <span>
              +{item.test.marksCorrect} / −{item.test.marksWrong}
            </span>
          </p>
          <div className="mt-6 flex gap-2">
            {item.test.lastAttemptId && (
              <Button variant="outline" href={`/results/${item.test.lastAttemptId}`}>
                Last result
              </Button>
            )}
            <Button href={`/tests/${item.test.id}`}>{item.test.attemptsCount ? "Reattempt" : "Start test"}</Button>
          </div>
          {item.test.attemptsCount > 0 && <p className="mt-3 text-xs text-slate-500">Attempted {item.test.attemptsCount} time(s) · Best {item.test.bestScore}/{item.test.totalMarks}</p>}
        </Card>
      );
  }
}
