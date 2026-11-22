import { usePage } from "@inertiajs/react";

export function useStaffDetails(id: string) {
  const page = usePage<any>();
  const staffList = page.props?.staff?.data ?? [];
  const found = staffList.find((s: any) => String(s.id) === String(id)) ?? null;

  return {
    data: found,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
