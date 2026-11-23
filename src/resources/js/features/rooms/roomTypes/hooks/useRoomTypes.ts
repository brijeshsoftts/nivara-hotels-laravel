import { usePage } from "@inertiajs/react";
import type { ListParams } from "@/types/api.types";

export function useRoomTypes(_params?: ListParams) {
    const page = usePage<any>();
    const roomTypes = page.props?.roomTypes ?? {
        data: [],
        meta: { page: 1, limit: 10, total: 0, totalPages: 1 },
    };

    return {
        items: roomTypes.data ?? [],
        pagination: roomTypes.meta,
        isLoading: false,
        isError: false,
        refetch: () => {},
    };
}
