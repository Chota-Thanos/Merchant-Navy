/**
 * Test rules shared by the attempt screen and the result page.
 * The backend applies the same rules (backend/app/Services/Tests/ScoringService.php);
 * its result is the one that counts.
 */
import type { AnswerState, PaletteStatus } from "@/types";

export const emptyAnswer = (): AnswerState => ({ selectedOptionId: null, markedForReview: false, visited: false });

/** Colour/state of a question number in the palette. */
export function paletteStatus(a: AnswerState | undefined): PaletteStatus {
  if (!a || !a.visited) return "not_visited";
  const answered = a.selectedOptionId !== null;
  if (a.markedForReview) return answered ? "answered_review" : "review";
  return answered ? "answered" : "not_answered";
}

export function countStatuses(answers: Record<number, AnswerState>, questionIds: number[]) {
  const counts: Record<PaletteStatus, number> = {
    not_visited: 0,
    not_answered: 0,
    answered: 0,
    review: 0,
    answered_review: 0,
  };
  for (const id of questionIds) counts[paletteStatus(answers[id])]++;
  return counts;
}

export interface ScoreInput {
  questionIds: number[];
  correctOptionByQuestion: Record<number, number>;
  selectedOptionByQuestion: Record<number, number | null>;
  marksCorrect: number;
  marksWrong: number;
}

/** "Answered & marked for review" counts for evaluation, as stated in the instructions. */
export function scoreAttempt(input: ScoreInput) {
  let correct = 0;
  let incorrect = 0;
  for (const id of input.questionIds) {
    const chosen = input.selectedOptionByQuestion[id];
    if (chosen == null) continue;
    if (chosen === input.correctOptionByQuestion[id]) correct++;
    else incorrect++;
  }
  const skipped = input.questionIds.length - correct - incorrect;
  const marksObtained = correct * input.marksCorrect;
  const marksLost = incorrect * input.marksWrong;
  const attempted = correct + incorrect;
  return {
    correct,
    incorrect,
    skipped,
    marksObtained,
    marksLost,
    score: marksObtained - marksLost,
    accuracy: attempted ? Math.round((correct / attempted) * 100) : 0,
  };
}
