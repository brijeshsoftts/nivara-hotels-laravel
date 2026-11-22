import { usePage } from "@inertiajs/react";

export function useSearchCustomers(search?: string) {
  const page = usePage<any>();
  const customers: any[] = page.props?.customers?.data ?? page.props?.customers ?? [];

  const filtered = search
    ? customers.filter(
        (c) =>
          c.name?.toLowerCase().includes(search.toLowerCase()) ||
          c.email?.toLowerCase().includes(search.toLowerCase()) ||
          c.phone?.includes(search)
      )
    : customers;

  return {
    items: filtered,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
