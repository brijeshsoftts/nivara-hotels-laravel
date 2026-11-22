import { usePage } from "@inertiajs/react";

export function useNextBooking() {
  const page = usePage<any>();
  return {
    data: page.props?.nextBooking ?? null,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
