"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { Bell } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { timeAgo, cn } from "@/lib/utils";
import { NotificationIcon } from "@/components/notifications/NotificationIcon";

export function NotificationBell() {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const { data, setData } = useApi(() => api.notifications.getNotifications(), []);
  const unread = data?.filter((n) => !n.read).length ?? 0;

  useEffect(() => {
    const close = (e: MouseEvent) => ref.current && !ref.current.contains(e.target as Node) && setOpen(false);
    document.addEventListener("mousedown", close);
    return () => document.removeEventListener("mousedown", close);
  }, []);

  async function markAll() {
    await api.notifications.markAllRead();
    setData((list) => list?.map((n) => ({ ...n, read: true })) ?? null);
  }

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen((o) => !o)}
        className="relative grid size-10 place-items-center rounded-xl text-slate-600 hover:bg-slate-100"
        aria-label={`Notifications${unread ? ` (${unread} unread)` : ""}`}
      >
        <Bell className="size-5" />
        {unread > 0 && (
          <span className="absolute right-1.5 top-1.5 grid min-w-4 place-items-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-4 text-white ring-2 ring-white">
            {unread}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 top-12 z-50 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-white shadow-xl">
          <div className="flex items-center justify-between border-b border-line px-4 py-3">
            <p className="font-bold text-navy-900">Notifications</p>
            {unread > 0 && (
              <button onClick={markAll} className="text-xs font-semibold text-brand-600 hover:underline">
                Mark all as read
              </button>
            )}
          </div>
          <ul className="max-h-96 overflow-y-auto">
            {data?.slice(0, 5).map((n) => (
              <li key={n.id}>
                <Link
                  href={n.href ?? "/notifications"}
                  onClick={() => setOpen(false)}
                  className={cn("flex gap-3 px-4 py-3 hover:bg-slate-50", !n.read && "bg-brand-50/50")}
                >
                  <NotificationIcon type={n.type} />
                  <span className="min-w-0 flex-1">
                    <span className="block text-sm font-semibold text-navy-900">{n.title}</span>
                    <span className="line-clamp-2 text-xs text-slate-500">{n.body}</span>
                    <span className="mt-1 block text-[11px] text-slate-400">{timeAgo(n.createdAt)}</span>
                  </span>
                  {!n.read && <span className="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600" />}
                </Link>
              </li>
            ))}
          </ul>
          <Link href="/notifications" onClick={() => setOpen(false)} className="block border-t border-line py-2.5 text-center text-sm font-semibold text-brand-600 hover:bg-slate-50">
            View all
          </Link>
        </div>
      )}
    </div>
  );
}
