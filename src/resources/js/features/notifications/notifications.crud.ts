import { router, usePage } from "@inertiajs/react";
import type { NotificationItem } from "./notifications.types";

export function useUnreadNotifications() {
  const page = usePage<any>();
  const list = page.props?.unreadNotifications ?? [];
  return {
    data: list as NotificationItem[],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}

export function useMarkNotificationAsRead() {
  const mutateAsync = async (id: string) => {
    router.patch(`/dashboard/notifications/${id}/read`, {}, {
      preserveScroll: true,
    });
  };

  return {
    mutateAsync,
    isPending: false,
  };
}

export function useMarkAllNotificationsAsRead() {
  const mutateAsync = async () => {
    router.patch("/dashboard/notifications/read-all", {}, {
      preserveScroll: true,
    });
  };

  return {
    mutateAsync,
    isPending: false,
  };
}
