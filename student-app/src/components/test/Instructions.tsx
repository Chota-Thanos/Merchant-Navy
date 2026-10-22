import type { TestSummary } from "@/types";
import { PaletteSymbolList } from "./PaletteLegend";

/**
 * Standard test instructions, filled in from the test's settings.
 * Shown before the test starts and in the "View Instructions" modal.
 */
export function Instructions({ test }: { test: TestSummary }) {
  return (
    <div className="space-y-6 text-[15px] leading-relaxed text-slate-700">
      <section>
        <h3 className="mb-2 font-bold text-navy-900">General instructions</h3>
        <ol className="list-decimal space-y-1 pl-5">
          <li>
            The test is of <b>{test.durationMinutes} minutes</b> duration.
          </li>
          <li>
            The test consists of <b>{test.questionsCount} questions</b>. The maximum marks are <b>{test.totalMarks}</b>.
          </li>
          <li>All questions are multiple choice questions with four options, of which only one is correct.</li>
          <li>
            Each correct answer gives <b className="text-green-700">+{test.marksCorrect} marks</b>
            {test.marksWrong > 0 ? (
              <>
                {" "}
                and <b className="text-red-700">{test.marksWrong} mark{test.marksWrong > 1 ? "s" : ""}</b> will be deducted for a wrong answer.
              </>
            ) : (
              <>. There is no negative marking.</>
            )}{" "}
            Unanswered questions get zero.
          </li>
          <li>You can go back and change any of your answers at any time during the test.</li>
        </ol>
      </section>

      <section>
        <h3 className="mb-2 font-bold text-navy-900">The timer</h3>
        <ul className="list-disc space-y-1 pl-5">
          <li>The clock is kept on the server. The countdown at the top of the screen shows the time remaining.</li>
          <li>When the timer reaches zero, the test is submitted automatically. You can also submit earlier with the Submit Test button.</li>
          <li>If your browser closes, open the test again — you will continue from where you left off while the timer keeps running.</li>
        </ul>
      </section>

      <section>
        <h3 className="mb-3 font-bold text-navy-900">Question palette</h3>
        <p className="mb-3">The palette on the right side of the screen shows the status of each question:</p>
        <PaletteSymbolList />
      </section>

      <section>
        <h3 className="mb-2 font-bold text-navy-900">Answering a question</h3>
        <ul className="list-disc space-y-1 pl-5">
          <li>Click an option to select it. Click another option to change it.</li>
          <li>
            To save your answer you <b>must</b> click <b>Save &amp; Next</b>.
          </li>
          <li>
            Click <b>Mark for Review &amp; Next</b> to save the answer (if any), mark the question for review and move on.
          </li>
          <li>
            Click <b>Clear Response</b> to remove your selected answer.
          </li>
          <li>Clicking a question number in the palette takes you to it directly, but does NOT save your answer to the current question.</li>
        </ul>
      </section>
    </div>
  );
}
