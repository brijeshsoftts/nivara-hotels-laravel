import { usePage } from "@inertiajs/react";

import type { ListParams } from "@/types/api.types";
import type { UserRole } from "@/types/enum.types";

export type UserListParams = ListParams & {
  role?: UserRole;
};

export function useUsers(params: UserListParams = {}) {
  const page = usePage<any>();
  const users = page.props?.users ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: users.data ?? [],
    pagination: users.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
