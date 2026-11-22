import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useIssues(_params?: ListParams) {
  const page = usePage<any>();
  const issues = page.props?.issues ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: issues.data ?? [],
    pagination: issues.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
