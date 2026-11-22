import { usePage } from "@inertiajs/react";
import type { InquiriesParams } from "../public.types";

export function useInquiries(_params?: InquiriesParams, _enabled = true) {
  const page = usePage<any>();
  const inquiries = page.props?.inquiries ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    inquiries: inquiries.data ?? [],
    pagination: inquiries.meta,
    isLoading: false,
    isFetching: false,
    isError: false,
    refetch: () => {},
  };
}
