import { usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import type { DashboardStat } from "@/components/common/Stat";
import type { BrowseRoom } from "./components/BrowseRoomCard";
import type { HousekeeperRoom, HousekeeperRoomDetails } from "./room.types";

export function useRooms<T = any>(_params?: any) {
  const page = usePage<any>();
  const rooms = page.props?.rooms ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  return {
    items: (rooms.data ?? []) as T[],
    data: (rooms.data ?? []) as T[],
    pagination: rooms.meta,
    isLoading: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}

export function useRoomsStats() {
  const page = usePage<any>();
  return {
    data: (page.props?.stats ?? []) as DashboardStat[],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}

export function useHousekeeperRoomDetails(roomId?: string) {
  const [data, setData] = useState<HousekeeperRoomDetails | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isError, setIsError] = useState(false);
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    if (!roomId) {
      setData(null);
      setIsLoading(false);
      setIsError(false);
      return;
    }

    const controller = new AbortController();
    setData(null);
    setIsLoading(true);
    setIsError(false);

    void (async () => {
      try {
        const response = await fetch(`/rooms/${encodeURIComponent(roomId)}`, {
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
          signal: controller.signal,
        });

        if (!response.ok) {
          throw new Error("Unable to load room details.");
        }

        const result = (await response.json()) as {
          data: HousekeeperRoomDetails;
        };
        setData(result.data ?? null);
      } catch {
        if (!controller.signal.aborted) {
          setData(null);
          setIsError(true);
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false);
        }
      }
    })();

    return () => controller.abort();
  }, [roomId, attempt]);

  return {
    data,
    isLoading,
    isError,
    refetch: () => setAttempt((current) => current + 1),
  };
}

export function useHousekeeperCleaningRooms() {
  const page = usePage<any>();
  const rooms: any[] = page.props?.rooms?.data ?? [];
  const dirty = rooms.filter((r) => r.housekeepingStatus === "DIRTY").slice(0, 5);

  return {
    data: dirty as HousekeeperRoom[],
    isLoading: false,
    refetch: () => {},
  };
}

export function useBrowseRooms(_params?: unknown) {
  const page = usePage<any>();
  const roomTypes = page.props?.roomTypes ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    data: roomTypes as { data: BrowseRoom[]; meta?: any },
    items: roomTypes.data ?? [],
    pagination: roomTypes.meta,
    isLoading: false,
    refetch: () => {},
  };
}

export const roomsCrud = {
  useList: useRooms,
  useStats: useRoomsStats,
};
