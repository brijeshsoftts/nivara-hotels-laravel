import { usePage } from "@inertiajs/react";
import { usePaginatedResource } from "@/hooks/usePaginatedResource";
import type { ListParams } from "@/types/api.types";

export function useRoomTypes(params?: ListParams) {
    const page = usePage<any>();
    return usePaginatedResource("/room-types", page.props?.roomTypes, params);
}
