"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { ChevronLeft, ChevronRight, Flag, Info, LayoutGrid, LoaderCircle, X } from "lucide-react";
import * as api from "@/lib/api";
import type { AttemptSession } from "@/lib/api/tests";
import type { AnswerState } from "@/types";
import { countStatuses, emptyAnswer } from "@/lib/test-engine";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/Button";
import { Modal } from "@/components/ui/Modal";
import { Instructions } from "./Instructions";
import { PaletteLegend } from "./PaletteLegend";
import { QuestionPalette } from "./QuestionPalette";
import { QuestionView } from "./QuestionView";
import { SubmitDialog } from "./SubmitDialog";
import { TestTimer } from "./TestTimer";

/**
 * The full-screen test interface.
 * - Selecting an option is a draft; it is saved only with "Save & Next" or "Mark for Review & Next".
 * - Every save goes to the server, so the attempt can be resumed.
 * - When the timer hits zero the test is submitted automatically.
 */
export function TestRunner({ session }: { session: AttemptSession }) {
  const router = useRouter();
  const { test, attemptId } = session;
  const ids = test.questions.map((q) => q.id);

  const [answers, setAnswers] = useState<Record<number, AnswerState>>(session.answers);
  const [index, setIndex] = useState(0);
  const [draft, setDraft] = useState<number | null>(session.answers[ids[0]]?.selectedOptionId ?? null);
  const [paletteOpen, setPaletteOpen] = useState(true);
  const [mobilePalette, setMobilePalette] = useState(false);
  const [instructionsOpen, setInstructionsOpen] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [timeUp, setTimeUp] = useState(false);

  const answersRef = useRef(answers);
  answersRef.current = answers;
  const submittedRef = useRef(false);

  const question = test.questions[index];
  const counts = countStatuses(answers, ids);

  /** Update one question's state locally and on the server. */
  const commit = useCallback(
    (questionId: number, patch: Partial<AnswerState>) => {
      const next = { ...emptyAnswer(), ...answersRef.current[questionId], visited: true, ...patch };
      answersRef.current = { ...answersRef.current, [questionId]: next };
      setAnswers(answersRef.current);
      // A failed save is not fatal: the full set of answers is sent again when the test is submitted.
      api.tests.saveAnswer(attemptId, questionId, next).catch(() => undefined);
    },
    [attemptId],
  );

  const goTo = useCallback(
    (i: number) => {
      const qid = ids[i];
      if (!answersRef.current[qid]?.visited) commit(qid, {});
      setIndex(i);
      setDraft(answersRef.current[qid]?.selectedOptionId ?? null);
      setMobilePalette(false);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [commit],
  );

  // First question counts as visited as soon as the test opens.
  useEffect(() => {
    goTo(0);
  }, [goTo]);

  const submit = useCallback(async () => {
    if (submittedRef.current) return;
    submittedRef.current = true;
    setSubmitting(true);
    // Retry a few times on a bad connection; the server also auto-submits when the time is up.
    for (let attempt = 1; attempt <= 5; attempt++) {
      try {
        await api.tests.submitAttempt(attemptId, answersRef.current);
        router.replace(`/results/${attemptId}`);
        return;
      } catch {
        await new Promise((r) => setTimeout(r, 2000 * attempt));
      }
    }
    // Still failing: let the student try again by hand.
    submittedRef.current = false;
    setSubmitting(false);
    setTimeUp(false);
    setConfirmOpen(false);
    window.alert("We couldn't submit your test. Please check your internet connection and press Submit Test again.");
  }, [attemptId, router]);

  // Warn before closing the tab mid-test (answers are saved, but the timer keeps running).
  useEffect(() => {
    const warn = (e: BeforeUnloadEvent) => {
      if (!submittedRef.current) e.preventDefault();
    };
    window.addEventListener("beforeunload", warn);
    return () => window.removeEventListener("beforeunload", warn);
  }, []);

  const isLast = index === ids.length - 1;
  const saveAndNext = () => {
    commit(question.id, { selectedOptionId: draft, markedForReview: false });
    if (!isLast) goTo(index + 1);
  };
  const markForReviewAndNext = () => {
    commit(question.id, { selectedOptionId: draft, markedForReview: true });
    if (!isLast) goTo(index + 1);
  };
  const clearResponse = () => {
    setDraft(null);
    commit(question.id, { selectedOptionId: null });
  };

  const palette = (
    <>
      <PaletteLegend counts={counts} />
      <div className="mt-5">
        <QuestionPalette questionIds={ids} answers={answers} currentIndex={index} onJump={goTo} />
      </div>
    </>
  );

  return (
    <div className="flex h-dvh flex-col bg-white">
      {/* Header */}
      <header className="flex items-center gap-3 border-b border-line px-4 py-3 sm:px-6">
        <div className="min-w-0">
          <h1 className="flex items-center gap-2 truncate text-base font-bold text-navy-900 sm:text-lg">
            <span className="truncate">{test.title}</span>
            <button onClick={() => setInstructionsOpen(true)} className="shrink-0 rounded-full text-brand-600 hover:bg-brand-50" aria-label="View instructions">
              <Info className="size-5" />
            </button>
          </h1>
          <TestTimer
            deadline={session.deadline}
            onExpire={() => {
              setTimeUp(true);
              void submit();
            }}
          />
        </div>
        <div className="ml-auto flex shrink-0 gap-2">
          <Button variant="outline" className="max-sm:hidden" onClick={() => setInstructionsOpen(true)}>
            View Instructions
          </Button>
          <Button onClick={() => setConfirmOpen(true)}>Submit Test</Button>
        </div>
      </header>

      <div className="relative flex min-h-0 flex-1">
        {/* Question area */}
        <section className="flex min-w-0 flex-1 flex-col">
          <div className="flex-1 overflow-y-auto px-4 py-5 sm:px-8">
            <div className="mx-auto max-w-3xl">
              <QuestionView
                question={question}
                number={index + 1}
                marksCorrect={test.marksCorrect}
                marksWrong={test.marksWrong}
                selected={draft}
                onSelect={setDraft}
              />
            </div>
          </div>

          <footer className="grid grid-cols-2 items-center gap-2 border-t border-line px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 sm:flex sm:flex-wrap sm:px-8">
            <Button variant="ghost" size="sm" className="justify-self-start lg:hidden" onClick={() => setMobilePalette(true)}>
              <LayoutGrid className="size-4" /> Questions
            </Button>
            <Button variant="ghost" size="sm" onClick={() => goTo(index - 1)} disabled={index === 0} className="max-sm:hidden">
              <ChevronLeft className="size-4" /> Previous
            </Button>
            <div className="contents sm:ml-auto sm:flex sm:flex-wrap sm:justify-end sm:gap-2">
              <Button variant="ghost" className="justify-self-end max-sm:px-2" onClick={clearResponse} disabled={draft === null && !answers[question.id]?.selectedOptionId}>
                Clear Response
              </Button>
              <Button variant="outline" onClick={markForReviewAndNext}>
                <Flag className="size-4 text-status-review" />
                <span className="sm:hidden">Review &amp; Next</span>
                <span className="hidden sm:inline">Mark for Review &amp; Next</span>
              </Button>
              <Button onClick={saveAndNext}>{isLast ? "Save" : "Save & Next"}</Button>
            </div>
          </footer>
        </section>

        {/* Desktop palette (collapsible) */}
        <button
          onClick={() => setPaletteOpen((o) => !o)}
          className="absolute top-1/2 z-10 hidden h-10 w-5 -translate-y-1/2 place-items-center rounded-l-md bg-navy-900 text-white lg:grid"
          style={{ right: paletteOpen ? "20rem" : 0 }}
          aria-label={paletteOpen ? "Hide question palette" : "Show question palette"}
        >
          {paletteOpen ? <ChevronRight className="size-4" /> : <ChevronLeft className="size-4" />}
        </button>
        {paletteOpen && <aside className="hidden w-80 shrink-0 overflow-y-auto border-l border-line p-5 lg:block">{palette}</aside>}

        {/* Mobile palette drawer */}
        {mobilePalette && (
          <div className="fixed inset-0 z-40 bg-navy-950/50 lg:hidden" onClick={() => setMobilePalette(false)}>
            <div className="absolute inset-y-0 right-0 w-[min(20rem,90vw)] overflow-y-auto bg-white p-5" onClick={(e) => e.stopPropagation()}>
              <div className="mb-4 flex items-center justify-between">
                <p className="font-bold text-navy-900">Question palette</p>
                <button onClick={() => setMobilePalette(false)} className="rounded-lg p-1 hover:bg-slate-100" aria-label="Close palette">
                  <X className="size-5" />
                </button>
              </div>
              {palette}
            </div>
          </div>
        )}
      </div>

      <Modal open={instructionsOpen} onClose={() => setInstructionsOpen(false)} title="Instructions" size="lg">
        <Instructions test={test} />
      </Modal>

      <SubmitDialog open={confirmOpen} onClose={() => setConfirmOpen(false)} onConfirm={submit} counts={counts} submitting={submitting} />

      {(timeUp || (submitting && !confirmOpen)) && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-navy-950/60 p-4">
          <div className={cn("flex flex-col items-center rounded-2xl bg-white px-8 py-7 text-center shadow-2xl")}>
            <LoaderCircle className="size-8 animate-spin text-brand-600" />
            <p className="mt-3 text-lg font-bold text-navy-900">{timeUp ? "Time's up!" : "Submitting…"}</p>
            <p className="mt-1 text-sm text-slate-500">Submitting your answers and preparing your result.</p>
          </div>
        </div>
      )}
    </div>
  );
}
