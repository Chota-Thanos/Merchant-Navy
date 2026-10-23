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
    <div className="rounded-xl bg-white p-3 shadow-sm ring-1 ring-line sm:p-4">
      <div className="flex items-center justify-between gap-2">
        <span className="flex items-center gap-1.5 text-[13px] font-semibold text-navy-900 sm:gap-2 sm:text-sm">
          <Icon className={`size-4.5 ${iconClass}`} /> {label}
        </span>
        <span className="text-[13px] font-bold text-navy-900 sm:text-sm">{value}</span>
      </div>
      <ProgressBar value={percent} className="mt-3" barClassName={barClass} />
      {subLabel && (
        <div className="mt-2 flex justify-between gap-1 text-[11px] sm:mt-2.5 sm:text-xs">
          <span className="text-slate-500">{subLabel}</span>
          <span className={`font-semibold ${subValueClass ?? "text-slate-600"}`}>{subValue}</span>
        </div>
      )}
    </div>
  );
}
