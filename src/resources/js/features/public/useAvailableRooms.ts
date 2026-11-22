import { usePage } from "@inertiajs/react";
import type { AvailableRoomsParams } from "./public.types";

export function useAvailableRooms(_params?: AvailableRoomsParams) {
  const page = usePage<any>();
  const rooms = page.props?.rooms ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    rooms: rooms.data ?? [],
    pagination: rooms.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}

export function useAvailableRoom(
  slug: string | null,
  _checkInDate?: string,
  _checkOutDate?: string
) {
  const page = usePage<any>();
  const room = page.props?.room ?? page.props?.roomType ?? null;

  return {
    room,
    isLoading: false,
    isFetching: false,
    isError: false,
    error: null,
    refetch: () => {},
  };
}
