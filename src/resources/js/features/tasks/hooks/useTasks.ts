import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useTasks(_params?: ListParams) {
  const page = usePage<any>();
  const tasks = page.props?.tasks ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: tasks.data ?? [],
    pagination: tasks.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
