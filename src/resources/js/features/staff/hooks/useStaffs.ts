import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useStaffs(_params?: ListParams) {
  const page = usePage<any>();
  const staff = page.props?.staff ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    data: staff,
    items: staff.data ?? [],
    pagination: staff.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
