import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import type { NotificationItem } from "./notifications.types";

export function useUnreadNotifications() {
    const page = usePage<any>();
    const list = page.props?.unreadNotifications ?? [];
    return {
        data: list as NotificationItem[],
        isLoading: false,
        isError: false,
        refetch: () => router.reload({ only: ["unreadNotifications"] }),
    };
}

export function useMarkNotificationAsRead() {
    const [isPending, setIsPending] = useState(false);
    const mutateAsync = async (id: string) => {
        router.patch(
            `/dashboard/notifications/${id}/read`,
            {},
            {
                preserveScroll: true,
                onStart: () => setIsPending(true),
                onFinish: () => setIsPending(false),
            },
        );
    };

    return {
        mutateAsync,
        isPending,
    };
}

export function useMarkAllNotificationsAsRead() {
    const [isPending, setIsPending] = useState(false);
    const mutateAsync = async () => {
        router.patch(
            "/dashboard/notifications/read-all",
            {},
            {
                preserveScroll: true,
                onStart: () => setIsPending(true),
                onFinish: () => setIsPending(false),
            },
        );
    };

    return {
        mutateAsync,
        isPending,
    };
}
