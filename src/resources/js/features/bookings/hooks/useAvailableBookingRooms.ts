import { usePage } from "@inertiajs/react";
import type { BookingAvailabilityParams } from "../bookings.types";

export function useAvailableBookingRooms(
  _params?: BookingAvailabilityParams
) {
  const page = usePage<any>();
  const rooms = page.props?.availableRooms ?? [];

  return {
    rooms,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
