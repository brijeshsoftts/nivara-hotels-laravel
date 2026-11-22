import { usePage } from "@inertiajs/react";

export function useCustomer(id: string) {
  const page = usePage<any>();
  const customers = page.props?.customers?.data ?? [];
  const customer = customers.find((c: any) => String(c.id) === String(id)) ?? null;

  return {
    data: customer,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
