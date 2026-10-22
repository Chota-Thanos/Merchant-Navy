import { ProgressBar } from "@/components/ui/ProgressBar";

/** A tile in "Your Progress" — label, value, bar and an optional sub-line (as in the reference screenshot). */
export function StatTile({
  icon: Icon,
  iconClass,
  label,
  value,
  percent,
  barClass,
  subLabel,
  subValue,
  subValueClass,
}: {
  icon: React.ElementType;
  iconClass: string;
  label: string;
  value: string;
  percent: number;
  barClass: string;
  subLabel?: string;
  subValue?: string | number;
  subValueClass?: string;
}) {
  return (
    <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-line">
      <div className="flex items-center justify-between gap-2">
        <span className="flex items-center gap-2 text-sm font-semibold text-navy-900">
          <Icon className={`size-4.5 ${iconClass}`} /> {label}
        </span>
        <span className="text-sm font-bold text-navy-900">{value}</span>
      </div>
      <ProgressBar value={percent} className="mt-3" barClassName={barClass} />
      {subLabel && (
        <div className="mt-2.5 flex justify-between text-xs">
          <span className="text-slate-500">{subLabel}</span>
          <span className={`font-semibold ${subValueClass ?? "text-slate-600"}`}>{subValue}</span>
        </div>
      )}
    </div>
  );
}
