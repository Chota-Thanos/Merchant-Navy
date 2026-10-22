"use client";

import Link from "next/link";
import { Bell } from "lucide-react";
import * as api from "@/lib/api";
import { useApi } from "@/lib/use-api";
import { cn, timeAgo } from "@/lib/utils";
import { NotificationIcon } from "@/components/notifications/NotificationIcon";
import { Button } from "@/components/ui/Button";
import { PageHeader } from "@/components/ui/PageHeader";
import { EmptyState, ErrorState, PageSkeleton } from "@/components/ui/States";

export default function NotificationsPage() {
  const { data, loading, error, reload, setData } = useApi(() => api.notifications.getNotifications(), []);
  const unread = data?.filter((n) => !n.read).length ?? 0;

  async function markAll() {
    await api.notifications.markAllRead();
    setData((list) => list?.map((n) => ({ ...n, read: true })) ?? null);
  }

  return (
    <>
      <PageHeader
        title="Notifications"
        subtitle="Course updates, live class reminders and results"
        action={unread > 0 && <Button variant="outline" onClick={markAll}>Mark all as read</Button>}
      />
      {loading ? (
        <PageSkeleton />
      ) : error ? (
        <ErrorState message={error} onRetry={reload} />
      ) : !data?.length ? (
        <EmptyState icon={Bell} title="You're all caught up" body="We'll notify you when a course is assigned, a live class is about to start or a result is ready." />
      ) : (
        <ul className="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-white">
          {data.map((n) => (
            <li key={n.id}>
              <Link href={n.href ?? "#"} className={cn("flex gap-4 p-4 hover:bg-slate-50 sm:p-5", !n.read && "bg-brand-50/40")}>
                <NotificationIcon type={n.type} className="size-10" />
                <div className="min-w-0 flex-1">
                  <p className="flex items-center gap-2 font-semibold text-navy-900">
                    {n.title}
                    {!n.read && <span className="size-2 rounded-full bg-brand-600" aria-label="Unread" />}
                  </p>
                  <p className="mt-0.5 text-sm text-slate-600">{n.body}</p>
                  <p className="mt-1.5 text-xs text-slate-400">{timeAgo(n.createdAt)}</p>
                </div>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
