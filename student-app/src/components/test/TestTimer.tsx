"use client";

import { useEffect, useRef, useState } from "react";
import { Clock } from "lucide-react";
import { cn, formatClock } from "@/lib/utils";

/**
 * Counts down to the server deadline. Calls `onExpire` once when time is up.
 * Using the deadline (not a local counter) keeps it correct after a refresh.
 */
export function TestTimer({ deadline, onExpire }: { deadline: string; onExpire: () => void }) {
  const end = new Date(deadline).getTime();
  const [left, setLeft] = useState(() => Math.round((end - Date.now()) / 1000));
  const expired = useRef(false);
  const onExpireRef = useRef(onExpire);
  onExpireRef.current = onExpire;

  useEffect(() => {
    const tick = () => {
      const s = Math.round((end - Date.now()) / 1000);
      setLeft(s);
      if (s <= 0 && !expired.current) {
        expired.current = true;
        onExpireRef.current();
      }
    };
    tick();
    const t = setInterval(tick, 1000);
    return () => clearInterval(t);
  }, [end]);

  const warning = left <= 5 * 60;
  return (
    <span className={cn("flex items-center gap-1.5 text-sm font-semibold tabular-nums", warning ? "text-red-600" : "text-slate-600")} role="timer" aria-live="off">
      <Clock className={cn("size-4", warning && "animate-pulse")} />
      {formatClock(left)}
    </span>
  );
}
