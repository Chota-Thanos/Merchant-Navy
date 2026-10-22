import type { AnswerState } from "@/types";
import { paletteStatus } from "@/lib/test-engine";
import { cn } from "@/lib/utils";
import { paletteStyles } from "./PaletteLegend";

/** Numbered grid of all questions, coloured by status. Clicking jumps to that question. */
export function QuestionPalette({
  questionIds,
  answers,
  currentIndex,
  onJump,
}: {
  questionIds: number[];
  answers: Record<number, AnswerState>;
  currentIndex: number;
  onJump: (index: number) => void;
}) {
  return (
    <div className="grid grid-cols-6 gap-2">
      {questionIds.map((id, i) => {
        const status = paletteStatus(answers[id]);
        const current = i === currentIndex;
        return (
          <button
            key={id}
            onClick={() => onJump(i)}
            aria-label={`Question ${i + 1}: ${paletteStyles[status].label}`}
            aria-current={current}
            className={cn(
              "grid aspect-square place-items-center rounded-lg text-sm font-semibold transition hover:brightness-95",
              paletteStyles[status].className,
              status === "not_visited" && "bg-slate-100",
              current && "ring-2 ring-navy-900 ring-offset-2",
            )}
          >
            {i + 1}
          </button>
        );
      })}
    </div>
  );
}
