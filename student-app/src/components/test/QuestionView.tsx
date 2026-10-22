import type { Question } from "@/types";
import { cn } from "@/lib/utils";
import { MathText } from "@/components/math/MathText";

/** Question text + the four options. `selected` is the option currently chosen (not yet saved). */
export function QuestionView({
  question,
  number,
  marksCorrect,
  marksWrong,
  selected,
  onSelect,
}: {
  question: Question;
  number: number;
  marksCorrect: number;
  marksWrong: number;
  selected: number | null;
  onSelect: (optionId: number | null) => void;
}) {
  return (
    <div>
      <div className="flex items-center gap-3 border-b border-line pb-4">
        <span className="grid size-9 place-items-center rounded-lg bg-navy-900 text-sm font-bold text-white">{number}</span>
        <span className="h-6 w-px bg-line" />
        <span className="rounded-full border border-line px-3 py-1 text-xs font-medium text-slate-600">
          Marks: <span className="font-bold text-green-600">+{marksCorrect}</span> <span className="font-bold text-red-600">-{marksWrong}</span>
        </span>
        <span className="rounded-full border border-line px-3 py-1 text-xs font-medium text-slate-600">
          Type: <span className="font-semibold text-navy-900">Single</span>
        </span>
      </div>

      <MathText html={question.body} className="mt-5 text-[16px] leading-relaxed text-navy-900" />

      <div className="mt-6 space-y-3" role="radiogroup" aria-label={`Options for question ${number}`}>
        {question.options.map((opt) => {
          const isSelected = selected === opt.id;
          return (
            <button
              key={opt.id}
              role="radio"
              aria-checked={isSelected}
              // Clicking the chosen option again deselects it (as per instructions)
              onClick={() => onSelect(isSelected ? null : opt.id)}
              className={cn(
                "flex w-full items-center gap-4 rounded-xl border px-4 py-3.5 text-left transition",
                isSelected ? "border-brand-500 bg-brand-50 ring-1 ring-brand-500" : "border-line bg-white hover:border-slate-300 hover:bg-slate-50",
              )}
            >
              <span className={cn("w-4 text-sm font-semibold", isSelected ? "text-brand-700" : "text-slate-500")}>{opt.label}</span>
              <span className="h-6 w-px bg-line" />
              <MathText as="span" html={opt.body} className="flex-1 text-[15px] text-navy-900 [&_p]:m-0" />
              <span className={cn("grid size-5 shrink-0 place-items-center rounded-full border-2", isSelected ? "border-brand-600" : "border-slate-300")}>
                {isSelected && <span className="size-2.5 rounded-full bg-brand-600" />}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
