import { usePage } from "@inertiajs/react";

export const useDashbaordRevenueTrend = (_range?: string) => {
  const page = usePage<any>();
  return {
    data: page.props?.revenueTrend ?? [],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
};
