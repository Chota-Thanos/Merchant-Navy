"use client";

import { useEffect, useState } from "react";
import { BellRing, CalendarDays, Clock, Radio } from "lucide-react";
import type { ContentItem } from "@/types";
import { formatClock, formatDate } from "@/lib/utils";
import { Button } from "@/components/ui/Button";
import { VideoPlayer } from "./VideoPlayer";

type LiveClass = Extract<ContentItem, { type: "live_class" }>;

/**
 * Live class states: upcoming (countdown) → live (join) → ended (recording).
 * The live provider (Agora) is plugged in later; only the Join button changes.
 */
export function LiveClassView({ item }: { item: LiveClass }) {
  const [now, setNow] = useState(() => Date.now());
  useEffect(() => {
    const t = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(t);
  }, []);

  if (item.status === "ended") {
    return item.recording ? (
      <VideoPlayer title={`Recording: ${item.title}`} embedUrl={item.recording.embedUrl} durationSeconds={item.recording.durationSeconds} />
    ) : (
      <Panel>
        <Radio className="size-10 text-slate-400" />
        <p className="mt-3 text-lg font-bold">This class has ended</p>
        <p className="mt-1 text-sm text-slate-400">The recording will appear here soon.</p>
      </Panel>
    );
  }

  const startsIn = Math.round((new Date(item.startsAt).getTime() - now) / 1000);

  if (item.status === "live" || startsIn <= 0) {
    return (
      <Panel>
        <span className="flex items-center gap-2 rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wider">
          <span className="size-2 animate-pulse rounded-full bg-white" /> Live now
        </span>
        <p className="mt-4 px-6 text-center text-xl font-bold">{item.title}</p>
        <p className="mt-1 text-sm text-slate-400">Started {formatDate(item.startsAt, true)}</p>
        <Button size="lg" className="mt-6">
          <Radio className="size-5" /> Join live class
        </Button>
      </Panel>
    );
  }

  return (
    <Panel>
      <p className="text-xs font-bold uppercase tracking-[0.2em] text-brand-200">Starts in</p>
      <p className="mt-2 font-display text-3xl font-bold tabular-nums sm:text-5xl">{formatClock(startsIn)}</p>
      <p className="mt-4 px-6 text-center text-lg font-bold">{item.title}</p>
      <div className="mt-2 flex flex-wrap justify-center gap-x-4 gap-y-1 text-sm text-slate-400">
        <span className="flex items-center gap-1.5">
          <CalendarDays className="size-4" /> {formatDate(item.startsAt, true)}
        </span>
        <span className="flex items-center gap-1.5">
          <Clock className="size-4" /> {item.durationMinutes} min
        </span>
      </div>
      <p className="mt-6 flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs text-slate-300">
        <BellRing className="size-4 text-brand-200" /> We&apos;ll notify you 15 minutes before the class starts
      </p>
    </Panel>
  );
}

function Panel({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex aspect-video flex-col items-center justify-center rounded-2xl bg-gradient-to-br from-navy-950 via-navy-900 to-navy-700 text-white">
      {children}
    </div>
  );
}
