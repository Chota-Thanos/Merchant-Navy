/** In-app notifications. Backend: app/Http/Controllers/Api/V1/NotificationController.php */
import type { AppNotification } from "@/types";
import { USE_MOCK, mockDelay, request } from "./client";
import { readState, seedNotifications, writeState } from "../mock/store";

export async function getNotifications(): Promise<AppNotification[]> {
  if (USE_MOCK) {
    await mockDelay(150);
    const { readNotificationIds } = readState();
    return seedNotifications.map((n) => ({ ...n, read: n.read || readNotificationIds.includes(n.id) }));
  }
  return request<AppNotification[]>("/notifications");
}

export async function markAllRead(): Promise<void> {
  if (USE_MOCK) {
    writeState((s) => ({ ...s, readNotificationIds: seedNotifications.map((n) => n.id) }));
    return;
  }
  await request("/notifications/read", { method: "POST" });
}
