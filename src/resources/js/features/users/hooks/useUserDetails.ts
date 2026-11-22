import { usePage } from "@inertiajs/react";

export function useUserDetails(userId?: string) {
  const page = usePage<any>();
  const users = page.props?.users?.data ?? [];
  const user = page.props?.user ?? users.find((u: any) => u.id === userId) ?? null;
  return {
    data: user,
    isLoading: false,
    isError: false,
    refetch: () => { },
  };
}
