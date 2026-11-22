import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useCustomerBookings(
  _customerId: string,
  _params?: ListParams,
  _enabled = true
) {
  const page = usePage<any>();
  const bookings = page.props?.bookings ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    data: bookings,
    isLoading: false,
    isFetching: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}
