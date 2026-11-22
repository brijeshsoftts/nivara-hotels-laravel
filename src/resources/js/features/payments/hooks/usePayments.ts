import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function usePayments<T = any>(_params?: ListParams) {
  const page = usePage<any>();
  const payments = page.props?.payments ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    data: payments,
    items: (payments.data ?? []) as T[],
    pagination: payments.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
