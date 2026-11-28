import { usePage } from "@inertiajs/react";
import { usePaginatedResource } from "@/hooks/usePaginatedResource";
import type { ListParams } from "@/types/api.types";

export function useAmenities(params?: ListParams) {
  const page = usePage<any>();
  return usePaginatedResource("/amenities", page.props?.amenities, params);
}
