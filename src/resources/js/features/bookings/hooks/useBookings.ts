import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useBookings<T = any>(_params?: any) {
  const page = usePage<any>();
  const bookings = page.props?.bookings ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: (bookings.data ?? []) as T[],
    data: (bookings.data ?? []) as T[],
    pagination: bookings.meta,
    isLoading: false,
    isFetching: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}
