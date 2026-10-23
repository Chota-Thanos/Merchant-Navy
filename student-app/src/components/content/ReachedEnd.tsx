"use client";

import { useEffect, useRef } from "react";

/**
 * An invisible marker placed at the end of a piece of content. When the student scrolls it into view
 * (they have read to the end), `onReach` is called once. Used to record progress without a button press.
 */
export function ReachedEnd({ onReach }: { onReach: () => void }) {
  const marker = useRef<HTMLDivElement>(null);
  const callback = useRef(onReach);
  callback.current = onReach;

  useEffect(() => {
    const el = marker.current;
    if (!el) return;
    const observer = new IntersectionObserver((entries) => {
      if (entries.some((e) => e.isIntersecting)) {
        observer.disconnect();
        callback.current();
      }
    });
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  return <div ref={marker} aria-hidden className="h-px" />;
}
