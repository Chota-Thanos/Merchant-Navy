"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { USE_MOCK, tokenStore } from "./api/client";

/**
 * Sends logged-out visitors to /login (real API only; mock mode has no login wall).
 * Returns true once we know the visitor is allowed to see the page.
 */
export function useRequireAuth(): boolean {
  const router = useRouter();
  const [ready, setReady] = useState(USE_MOCK);

  useEffect(() => {
    if (USE_MOCK) return;
    if (tokenStore.get()) setReady(true);
    else router.replace("/login");
  }, [router]);

  return ready;
}
