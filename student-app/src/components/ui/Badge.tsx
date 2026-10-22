import { cn } from "@/lib/utils";

type Tone = "blue" | "green" | "red" | "amber" | "purple" | "slate" | "navy";

const tones: Record<Tone, string> = {
  blue: "bg-brand-50 text-brand-700 ring-brand-100",
  green: "bg-green-50 text-green-700 ring-green-100",
  red: "bg-red-50 text-red-700 ring-red-100",
  amber: "bg-amber-50 text-amber-700 ring-amber-100",
  purple: "bg-violet-50 text-violet-700 ring-violet-100",
  slate: "bg-slate-100 text-slate-600 ring-slate-200",
  navy: "bg-navy-900 text-white ring-navy-900",
};

export function Badge({ tone = "slate", className, children }: { tone?: Tone; className?: string; children: React.ReactNode }) {
  return (
    <span className={cn("inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset", tones[tone], className)}>
      {children}
    </span>
  );
}
