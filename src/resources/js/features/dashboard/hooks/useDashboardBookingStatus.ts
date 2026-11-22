import { usePage } from "@inertiajs/react";

export const useDashbaordBookingStatus = (_range?: any) => {
  const page = usePage<any>();
  return {
    data: page.props?.bookingStatus ?? [],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
};
