import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useCustomers(_params?: ListParams) {
  const page = usePage<any>();
  const customers = page.props?.customers ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: customers.data ?? [],
    pagination: customers.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
