"use client";

import Link from "next/link";
import { ChevronRight, Medal, Target, Trophy } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { formatDate } from "@/lib/utils";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { PageHeader } from "@/components/ui/PageHeader";
import { EmptyState, ErrorState, PageSkeleton } from "@/components/ui/States";

/** Every submitted attempt, newest first. */
export default function ResultsPage() {
  const { data: results, loading, error, reload } = useApi(() => api.results.getResults(), []);

  return (
    <>
      <PageHeader title="Results" subtitle="Your scores, ranks and solutions for every test you've taken" />
      {loading ? (
        <PageSkeleton />
      ) : error ? (
        <ErrorState message={error} onRetry={reload} />
      ) : !results?.length ? (
        <EmptyState icon={Trophy} title="No results yet" body="Take a practice or mock test and your result will appear here." action={<Button href="/mock-tests">Go to Mock Tests</Button>} />
      ) : (
        <>
          <div className="mb-6 grid gap-4 sm:grid-cols-3">
            <Summary icon={Trophy} label="Tests taken" value={String(results.length)} />
            <Summary icon={Target} label="Average accuracy" value={`${Math.round(results.reduce((n, r) => n + r.accuracy, 0) / results.length)}%`} />
            <Summary icon={Medal} label="Best rank" value={`#${Math.min(...results.map((r) => r.rank))}`} />
          </div>

          <div className="overflow-hidden rounded-2xl border border-line bg-white">
            <table className="hidden w-full text-left text-sm md:table">
              <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                  <th className="px-5 py-3">Test</th>
                  <th className="px-3 py-3">Date</th>
                  <th className="px-3 py-3">Score</th>
                  <th className="px-3 py-3">Accuracy</th>
                  <th className="px-3 py-3">Rank</th>
                  <th className="px-5 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {results.map((r) => (
                  <tr key={r.id} className="hover:bg-slate-50">
                    <td className="px-5 py-4">
                      <p className="font-semibold text-navy-900">{r.testTitle}</p>
                      <p className="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                        <Badge tone={r.testKind === "mock" ? "navy" : "green"}>{r.testKind === "mock" ? "Mock" : "Practice"}</Badge>
                        Attempt {r.attemptNo} · {r.courseTitle}
                      </p>
                    </td>
                    <td className="px-3 py-4 text-slate-600">{formatDate(r.submittedAt)}</td>
                    <td className="px-3 py-4 font-bold text-navy-900">
                      {r.score}
                      <span className="font-medium text-slate-400">/{r.totalMarks}</span>
                    </td>
                    <td className="px-3 py-4 text-slate-600">{r.accuracy}%</td>
                    <td className="px-3 py-4 text-slate-600">
                      #{r.rank} <span className="text-slate-400">/ {r.totalCandidates}</span>
                    </td>
                    <td className="px-5 py-4 text-right">
                      <Button size="sm" variant="outline" href={`/results/${r.id}`}>
                        View
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            {/* Mobile list */}
            <ul className="divide-y divide-line md:hidden">
              {results.map((r) => (
                <li key={r.id}>
                  <Link href={`/results/${r.id}`} className="flex items-center gap-3 p-4">
                    <div className="min-w-0 flex-1">
                      <p className="font-semibold text-navy-900">{r.testTitle}</p>
                      <p className="mt-1 text-xs text-slate-500">
                        {formatDate(r.submittedAt)} · Attempt {r.attemptNo}
                      </p>
                      <p className="mt-2 flex gap-3 text-xs font-semibold text-slate-600">
                        <span className="text-navy-900">
                          {r.score}/{r.totalMarks}
                        </span>
                        <span>{r.accuracy}% accuracy</span>
                        <span>Rank #{r.rank}</span>
                      </p>
                    </div>
                    <ChevronRight className="size-5 text-slate-400" />
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </>
      )}
    </>
  );
}

function Summary({ icon: Icon, label, value }: { icon: React.ElementType; label: string; value: string }) {
  return (
    <div className="flex items-center gap-4 rounded-2xl border border-line bg-white p-5">
      <span className="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-600">
        <Icon className="size-5" />
      </span>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{label}</p>
        <p className="font-display text-2xl font-extrabold text-navy-900">{value}</p>
      </div>
    </div>
  );
}
