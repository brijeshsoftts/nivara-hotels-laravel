import { usePage } from "@inertiajs/react";
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
  const page = usePage<any>();
  const rooms = page.props?.rooms?.data ?? [];
  const found = rooms.find((r: any) => String(r.id) === String(roomId)) ?? null;

  return {
    data: found as HousekeeperRoomDetails | null,
    isLoading: false,
    isError: false,
    refetch: () => {},
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
