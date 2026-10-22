"use client";

import { useEffect, useState } from "react";
import { Anchor } from "lucide-react";
import { brand } from "@/lib/config";
import { cn } from "@/lib/utils";

/**
 * Shows public/brand/logo.png once it has loaded, otherwise an anchor icon + name.
 * `tone="light"` is for dark backgrounds (sidebar, login panel).
 */
export function Logo({ tone = "dark", className }: { tone?: "dark" | "light"; className?: string }) {
  const [hasImage, setHasImage] = useState(false);

  useEffect(() => {
    const img = new Image();
    img.onload = () => setHasImage(true);
    img.src = brand.logoSrc;
  }, []);

  if (hasImage) {
    // eslint-disable-next-line @next/next/no-img-element
    return <img src={brand.logoSrc} alt={brand.name} className={cn("h-10 w-auto", className)} />;
  }

  return (
    <span className={cn("flex items-center gap-2.5", className)}>
      <span className={cn("grid size-9 place-items-center rounded-xl", tone === "light" ? "bg-white/10 text-white ring-1 ring-white/20" : "bg-navy-900 text-white")}>
        <Anchor className="size-5" />
      </span>
      <span className={cn("font-display text-[15px] font-extrabold leading-tight", tone === "light" ? "text-white" : "text-navy-900")}>
        My Merchant
        <br />
        <span className={tone === "light" ? "text-brand-200" : "text-brand-600"}>Navy</span>
      </span>
    </span>
  );
}
