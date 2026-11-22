import { usePage } from "@inertiajs/react";

export function useInvoiceDetails(id: string) {
  const page = usePage<any>();
  const invoices = page.props?.invoices?.data ?? [];
  const found = invoices.find((inv: any) => String(inv.id) === String(id)) ?? null;

  return {
    data: found,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
