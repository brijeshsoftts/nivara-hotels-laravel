import { useEffect, useState } from "react";
import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";
import type { RoomDetails } from "../rooms.types";

export function useRooms<T = any>(_params?: any) {
    const page = usePage<any>();
    const rooms = page.props?.rooms ?? {
        data: [],
        meta: { page: 1, limit: 10, total: 0, totalPages: 1 },
    };

    return {
        items: (rooms.data ?? []) as T[],
        data: (rooms.data ?? []) as T[],
        pagination: rooms.meta,
        isLoading: false,
        isFetching: false,
        isPending: false,
        isError: false,
        refetch: () => {},
    };
}

export function useRoomDetails(id: string, _enabled = true) {
    const [room, setRoom] = useState<RoomDetails | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [isError, setIsError] = useState(false);
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        if (!_enabled || !id) {
            setRoom(null);
            setIsLoading(false);
            setIsError(false);
            return;
        }

        const controller = new AbortController();
        setRoom(null);
        setIsLoading(true);
        setIsError(false);

        void (async () => {
            try {
                const response = await fetch(
                    `/rooms/${encodeURIComponent(id)}`,
                    {
                        headers: { Accept: "application/json" },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error("Unable to load room details.");
                }

                const result = (await response.json()) as { data: RoomDetails };
                setRoom(result.data);
            } catch {
                if (!controller.signal.aborted) {
                    setRoom(null);
                    setIsError(true);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setIsLoading(false);
                }
            }
        })();

        return () => controller.abort();
    }, [id, _enabled, attempt]);

    return {
        data: room,
        room,
        isLoading,
        isFetching: false,
        isPending: isLoading,
        isError,
        refetch: () => setAttempt((current) => current + 1),
    };
}
