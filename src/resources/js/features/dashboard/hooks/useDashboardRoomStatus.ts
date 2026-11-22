import { usePage } from "@inertiajs/react";

export const useDashboardRoomStatus = () => {
  const page = usePage<any>();
  return {
    data: page.props?.roomStatus ?? [],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
};
