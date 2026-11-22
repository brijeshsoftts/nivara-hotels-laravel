import { usePage } from "@inertiajs/react";
import type { RoomTypeDetails } from "../roomTypes.types";

export function useRoomType(slug: string) {
  const page = usePage<any>();
  const roomTypes = page.props?.roomTypes?.data ?? [];
  const found = roomTypes.find((rt: any) => rt.slug === slug || String(rt.id) === String(slug)) ?? null;

  return {
    roomType: found as RoomTypeDetails | null,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
