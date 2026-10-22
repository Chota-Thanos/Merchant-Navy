import Link from "next/link";
import { CircleCheck, Circle } from "lucide-react";
import type { ContentItem } from "@/types";
import { cn } from "@/lib/utils";
import { ContentIcon, contentMeta, contentTypes } from "./contentTypes";

/** One line in a chapter's content list (Udemy style). */
export function ContentRow({ item, active, compact }: { item: ContentItem; active?: boolean; compact?: boolean }) {
  const isLive = item.type === "live_class" && item.status === "live";
  return (
    <Link
      href={`/learn/${item.id}`}
      className={cn(
        "flex items-center gap-3 transition",
        compact ? "rounded-lg px-3 py-2.5" : "px-5 py-3.5",
        active ? "bg-brand-50" : "hover:bg-slate-50",
      )}
    >
      <ContentIcon type={item.type} className={compact ? "size-8" : undefined} />
      <span className="min-w-0 flex-1">
        <span className={cn("block font-semibold", compact ? "text-[13px]" : "text-sm", active ? "text-brand-700" : "text-navy-900")}>{item.title}</span>
        <span className="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
          {!compact && <span>{contentTypes[item.type].label}</span>}
          {!compact && <span className="text-slate-300">•</span>}
          {isLive ? (
            <span className="flex items-center gap-1 font-semibold text-red-600">
              <span className="size-1.5 animate-pulse rounded-full bg-red-600" /> Live now
            </span>
          ) : (
            <span>{contentMeta(item)}</span>
          )}
        </span>
      </span>
      {item.completed ? (
        <CircleCheck className="size-5 shrink-0 text-green-600" aria-label="Completed" />
      ) : (
        <Circle className="size-5 shrink-0 text-slate-300" aria-label="Not completed" />
      )}
    </Link>
  );
}
