"use client";

import { CirclePlay } from "lucide-react";
import { formatDuration } from "@/lib/utils";

/**
 * Recorded lecture player. `embedUrl` comes from the video provider
 * (YouTube unlisted / Bunny / Vimeo / VdoCipher — chosen in the admin panel).
 */
export function VideoPlayer({ title, embedUrl, durationSeconds }: { title: string; embedUrl?: string; durationSeconds: number }) {
  return (
    <div className="relative aspect-video overflow-hidden rounded-2xl bg-navy-950" onContextMenu={(e) => e.preventDefault()}>
      {embedUrl ? (
        <iframe src={embedUrl} title={title} className="size-full" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowFullScreen />
      ) : (
        <div className="flex size-full flex-col items-center justify-center bg-gradient-to-br from-navy-950 via-navy-900 to-navy-700 text-white">
          <button className="grid size-20 place-items-center rounded-full bg-white/10 ring-1 ring-white/25 backdrop-blur transition hover:scale-105 hover:bg-white/20" aria-label="Play">
            <CirclePlay className="size-10" />
          </button>
          <p className="mt-4 px-6 text-center font-semibold">{title}</p>
          <p className="mt-1 text-sm text-slate-400">{formatDuration(durationSeconds)}</p>
        </div>
      )}
    </div>
  );
}
