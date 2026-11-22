import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useAmenities(_params?: ListParams) {
  const page = usePage<any>();
  const amenities = page.props?.amenities ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: amenities.data ?? [],
    pagination: amenities.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
