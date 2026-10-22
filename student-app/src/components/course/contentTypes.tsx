import { CirclePlay, ClipboardCheck, FileText, Radio, StickyNote, Target } from "lucide-react";
import type { ContentItem, ContentType } from "@/types";
import { cn, formatDate, formatDuration } from "@/lib/utils";

/** Icon, label and colour for each chapter content type. Add new types here. */
export const contentTypes: Record<ContentType, { label: string; icon: React.ElementType; tone: string }> = {
  video: { label: "Recorded lecture", icon: CirclePlay, tone: "bg-brand-50 text-brand-600" },
  live_class: { label: "Live class", icon: Radio, tone: "bg-red-50 text-red-600" },
  pdf: { label: "PDF notes", icon: FileText, tone: "bg-amber-50 text-amber-600" },
  note: { label: "Notes", icon: StickyNote, tone: "bg-teal-50 text-teal-600" },
  assignment: { label: "Assignment", icon: ClipboardCheck, tone: "bg-violet-50 text-violet-600" },
  practice_test: { label: "Practice test", icon: Target, tone: "bg-green-50 text-green-600" },
};

export function ContentIcon({ type, className }: { type: ContentType; className?: string }) {
  const { icon: Icon, tone } = contentTypes[type];
  return (
    <span className={cn("grid size-9 shrink-0 place-items-center rounded-lg", tone, className)}>
      <Icon className="size-4.5" />
    </span>
  );
}

/** Short detail line under a content title, e.g. "38 min" or "24 pages". */
export function contentMeta(c: ContentItem): string {
  switch (c.type) {
    case "video":
      return formatDuration(c.durationSeconds);
    case "live_class":
      if (c.status === "ended") return c.recording ? `Recording · ${formatDuration(c.recording.durationSeconds)}` : "Recording coming soon";
      if (c.status === "live") return "Live now";
      return formatDate(c.startsAt, true);
    case "pdf":
      return `${c.pageCount} pages`;
    case "note":
      return `${c.readMinutes} min read`;
    case "assignment":
      return c.submittedAt ? "Submitted" : c.dueAt ? `Due ${formatDate(c.dueAt)}` : "No due date";
    case "practice_test":
      return `${c.test.questionsCount} questions · ${c.test.durationMinutes} min`;
  }
}
