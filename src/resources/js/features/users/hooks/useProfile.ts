import { router, usePage } from "@inertiajs/react";
import type { SharedProps } from "@/types";

export function useProfile() {
  const page = usePage<SharedProps & { profile?: { data: any }; details?: { data: any } }>();
  const user = page.props?.profile?.data ?? page.props?.auth?.user ?? null;
  const details = page.props?.details?.data ?? null;

  return {
    data: user ? { user, details } : null,
    user,
    details,
    isLoading: false,
    isPending: false,
    isError: false,
    refetch: () => router.reload(),
  };
}
