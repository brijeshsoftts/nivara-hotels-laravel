import { usePage } from "@inertiajs/react";

export function useCustomerStat() {
  const page = usePage<any>();
  return {
    data: page.props?.stats ?? [],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
