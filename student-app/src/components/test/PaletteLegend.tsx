import type { PaletteStatus } from "@/types";
import { cn } from "@/lib/utils";

/** Colours of each palette state. Shared by the palette, legend and instructions. */
export const paletteStyles: Record<PaletteStatus, { label: string; className: string; description: string }> = {
  not_visited: {
    label: "Not Visited",
    className: "bg-status-not-visited text-slate-700",
    description: "You have not visited the question yet.",
  },
  not_answered: {
    label: "Not Answered",
    className: "bg-status-not-answered text-white",
    description: "You have not answered the question.",
  },
  answered: {
    label: "Answered",
    className: "bg-status-answered text-white",
    description: "You have answered the question.",
  },
  review: {
    label: "Marked for Review",
    className: "bg-status-review text-white",
    description: "You have NOT answered the question, but have marked it for review.",
  },
  answered_review: {
    label: "Answered & Marked for Review",
    className: "bg-[linear-gradient(135deg,var(--color-status-answered)_50%,var(--color-status-review)_50%)] text-white",
    description: 'Questions "Answered and Marked for Review" will be considered for evaluation.',
  },
};

const order: PaletteStatus[] = ["answered", "not_answered", "review", "not_visited", "answered_review"];

/** Count chips shown above the palette (as in the reference screenshot). */
export function PaletteLegend({ counts }: { counts: Record<PaletteStatus, number> }) {
  return (
    <div className="grid grid-cols-2 gap-x-3 gap-y-2.5 rounded-xl bg-brand-50/60 p-3.5">
      {order.map((s) => (
        <div key={s} className={cn("flex items-center gap-2 text-xs text-slate-600", s === "answered_review" && "col-span-2")}>
          <span className={cn("grid h-6 min-w-6 place-items-center rounded-md px-1 text-[11px] font-bold", paletteStyles[s].className)}>{counts[s]}</span>
          {paletteStyles[s].label}
        </div>
      ))}
    </div>
  );
}

/** Symbol list used in the instructions. */
export function PaletteSymbolList() {
  return (
    <ul className="space-y-3">
      {(["not_visited", "not_answered", "answered", "review", "answered_review"] as PaletteStatus[]).map((s) => (
        <li key={s} className="flex items-center gap-4">
          <span className={cn("size-10 shrink-0 rounded-lg", paletteStyles[s].className)} />
          <span className="text-sm text-slate-600">{paletteStyles[s].description}</span>
        </li>
      ))}
    </ul>
  );
}
