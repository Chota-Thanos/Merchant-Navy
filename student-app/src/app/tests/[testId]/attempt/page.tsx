"use client";

import { useParams } from "next/navigation";
import { LoaderCircle } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { TestRunner } from "@/components/test/TestRunner";
import { ErrorState } from "@/components/ui/States";

/** Starts (or resumes) an attempt, then hands over to the test interface. */
export default function AttemptPage() {
  const { testId } = useParams<{ testId: string }>();
  const { data: session, error, reload } = useApi(() => api.tests.startAttempt(Number(testId)), [testId]);

  if (error) {
    return (
      <div className="mx-auto max-w-lg p-6">
        <ErrorState message={error} onRetry={reload} />
      </div>
    );
  }
  if (!session) {
    return (
      <div className="grid h-dvh place-items-center bg-white">
        <div className="flex flex-col items-center text-slate-500">
          <LoaderCircle className="size-8 animate-spin text-brand-600" />
          <p className="mt-3 text-sm font-medium">Preparing your test…</p>
        </div>
      </div>
    );
  }
  return <TestRunner session={session} />;
}
