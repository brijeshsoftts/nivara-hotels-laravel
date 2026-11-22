import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useInvoices<T = any>(_params?: any) {
  const page = usePage<any>();
  const invoices = page.props?.invoices ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    data: invoices,
    items: (invoices.data ?? []) as T[],
    pagination: invoices.meta,
    isLoading: false,
    isFetching: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}
