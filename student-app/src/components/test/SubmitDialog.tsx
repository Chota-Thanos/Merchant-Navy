"use client";

import type { PaletteStatus } from "@/types";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/Button";
import { Modal } from "@/components/ui/Modal";
import { paletteStyles } from "./PaletteLegend";

/** Confirmation with a status summary before the test is submitted. */
export function SubmitDialog({
  open,
  onClose,
  onConfirm,
  counts,
  submitting,
}: {
  open: boolean;
  onClose: () => void;
  onConfirm: () => void;
  counts: Record<PaletteStatus, number>;
  submitting: boolean;
}) {
  const rows: PaletteStatus[] = ["answered", "answered_review", "not_answered", "review", "not_visited"];
  const unanswered = counts.not_answered + counts.review + counts.not_visited;
  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Submit test?"
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={submitting}>
            Back to test
          </Button>
          <Button onClick={onConfirm} loading={submitting}>
            Yes, submit
          </Button>
        </>
      }
    >
      <ul className="divide-y divide-line rounded-xl border border-line">
        {rows.map((s) => (
          <li key={s} className="flex items-center gap-3 px-4 py-2.5 text-sm">
            <span className={cn("size-4 rounded", paletteStyles[s].className)} />
            <span className="flex-1 text-slate-600">{paletteStyles[s].label}</span>
            <span className="font-bold text-navy-900">{counts[s]}</span>
          </li>
        ))}
      </ul>
      {unanswered > 0 && (
        <p className="mt-3 text-sm text-amber-700">
          {unanswered} question{unanswered > 1 ? "s are" : " is"} still unanswered. You won&apos;t be able to change answers after submitting.
        </p>
      )}
    </Modal>
  );
}
