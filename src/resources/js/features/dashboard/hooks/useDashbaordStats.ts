import { usePage } from "@inertiajs/react";

export const useDashbaordStats = () => {
  const page = usePage<any>();
  return {
    data: page.props?.stats ?? [],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
};
