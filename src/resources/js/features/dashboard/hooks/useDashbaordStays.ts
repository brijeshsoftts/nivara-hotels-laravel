import { usePage } from "@inertiajs/react";

export const useDashbaordStays = () => {
  const page = usePage<any>();
  return {
    data: page.props?.stays ?? [],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
};
