"use client";

import { useParams, useRouter } from "next/navigation";
import { ArrowLeft, Clock, FileQuestion, Medal } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { Instructions } from "@/components/test/Instructions";
import { Button } from "@/components/ui/Button";
import { ErrorState, PageSkeleton } from "@/components/ui/States";

/** Step before the test: instructions + "I have read" + Start Test. */
export default function TestInstructionsPage() {
  const { testId } = useParams<{ testId: string }>();
  const router = useRouter();
  const { data: test, loading, error, reload } = useApi(() => api.tests.getTest(Number(testId)), [testId]);

  return (
    <div className="min-h-dvh bg-gradient-to-b from-brand-50 to-surface pb-28">
      <header className="border-b border-line bg-white">
        <div className="mx-auto flex h-14 max-w-4xl items-center px-4 sm:px-6">
          <button onClick={() => router.back()} className="flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-brand-600">
            <ArrowLeft className="size-4" /> Back
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        {loading ? (
          <PageSkeleton />
        ) : error || !test ? (
          <ErrorState message={error ?? "Test not found"} onRetry={reload} />
        ) : (
          <>
            <p className="text-xs font-bold uppercase tracking-wider text-brand-600">{test.kind === "mock" ? "Mock test" : "Practice test"}</p>
            <h1 className="mt-1 text-2xl font-extrabold text-navy-900 sm:text-3xl">{test.title}</h1>
            <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm font-medium text-slate-500">
              <span className="flex items-center gap-1.5">
                <FileQuestion className="size-4" /> {test.questionsCount} Questions
              </span>
              <span className="flex items-center gap-1.5">
                <Medal className="size-4" /> {test.totalMarks} Marks
              </span>
              <span className="flex items-center gap-1.5">
                <Clock className="size-4" /> {test.durationMinutes} min
              </span>
            </p>

            <div className="mt-6 rounded-2xl border border-line bg-white p-5 sm:p-8">
              <Instructions test={test} />
            </div>
          </>
        )}
      </main>

      {test && (
        <footer className="fixed inset-x-0 bottom-0 border-t border-line bg-white/95 backdrop-blur">
          <div className="mx-auto flex max-w-4xl justify-end px-4 py-3 sm:px-6">
            <Button size="lg" onClick={() => router.push(`/tests/${test.id}/attempt`)}>
              Start Test
            </Button>
          </div>
        </footer>
      )}
    </div>
  );
}
