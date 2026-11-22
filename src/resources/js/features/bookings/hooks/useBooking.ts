import { usePage } from "@inertiajs/react";

export function useBooking(id?: string, _enabled = true) {
  const page = usePage<any>();
  const bookings = page.props?.bookings?.data ?? [];
  const found = id ? bookings.find((b: any) => String(b.id) === String(id)) ?? page.props?.booking ?? null : null;

  return { booking: found, data: found, isLoading: false, isFetching: false, isError: false, refetch: () => {} };
}

export const useBookingDetails = useBooking;
