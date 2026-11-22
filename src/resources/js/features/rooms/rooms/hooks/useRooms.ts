import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useRooms<T = any>(_params?: any) {
  const page = usePage<any>();
  const rooms = page.props?.rooms ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

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
  const page = usePage<any>();
  const rooms = page.props?.rooms?.data ?? [];
  const room = rooms.find((r: any) => String(r.id) === String(id)) ?? null;

  return {
    data: room,
    room,
    isLoading: false,
    isFetching: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}
