"use client";

import { useRef, useState } from "react";
import { CalendarDays, CircleCheck, FileUp, X } from "lucide-react";
import * as api from "@/lib/api";
import type { ContentItem } from "@/types";
import { formatDate } from "@/lib/utils";
import { MathText } from "@/components/math/MathText";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";

type Assignment = Extract<ContentItem, { type: "assignment" }>;

export function AssignmentView({ item }: { item: Assignment }) {
  const [file, setFile] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [submittedAt, setSubmittedAt] = useState(item.submittedAt);
  const input = useRef<HTMLInputElement>(null);

  const [error, setError] = useState("");

  async function submit() {
    if (!file) return;
    setSubmitting(true);
    setError("");
    try {
      setSubmittedAt(await api.assignments.submit(item.id, file));
    } catch (e) {
      setError(e instanceof Error ? e.message : "Upload failed. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="space-y-4">
      <Card className="p-6">
        {item.dueAt && (
          <p className="mb-3 flex items-center gap-1.5 text-sm font-semibold text-violet-700">
            <CalendarDays className="size-4" /> Due {formatDate(item.dueAt, true)}
          </p>
        )}
        <MathText html={item.instructions} className="text-[15px] leading-relaxed text-slate-700" />
      </Card>

      <Card className="p-6">
        <h3 className="font-bold text-navy-900">Your submission</h3>
        {submittedAt ? (
          <p className="mt-3 flex items-center gap-2 rounded-xl bg-green-50 p-4 text-sm font-medium text-green-700">
            <CircleCheck className="size-5" /> Submitted on {formatDate(submittedAt, true)}. Your teacher will review it.
          </p>
        ) : (
          <>
            <input ref={input} type="file" accept=".pdf,image/*" hidden onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
            {file ? (
              <div className="mt-3 flex items-center gap-3 rounded-xl border border-line p-3">
                <FileUp className="size-5 text-brand-600" />
                <span className="flex-1 truncate text-sm font-medium">{file.name}</span>
                <button onClick={() => setFile(null)} className="rounded p-1 text-slate-400 hover:bg-slate-100" aria-label="Remove file">
                  <X className="size-4" />
                </button>
              </div>
            ) : (
              <button
                onClick={() => input.current?.click()}
                className="mt-3 flex w-full flex-col items-center rounded-xl border-2 border-dashed border-line px-4 py-8 text-sm text-slate-500 hover:border-brand-200 hover:bg-brand-50/40"
              >
                <FileUp className="mb-2 size-7 text-brand-600" />
                <span className="font-semibold text-navy-900">Upload your answer sheet</span>
                <span className="mt-0.5 text-xs">PDF or image, up to 20 MB</span>
              </button>
            )}
            {error && <p className="mt-3 text-sm font-medium text-red-600">{error}</p>}
            <Button className="mt-4" disabled={!file} loading={submitting} onClick={submit}>
              Submit assignment
            </Button>
          </>
        )}
      </Card>
    </div>
  );
}
