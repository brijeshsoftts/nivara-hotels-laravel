import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";
import { notifyError } from "@/lib/notification";
import type { PaymentItem } from "./payments.types";

export function usePayments<T = PaymentItem>(_params?: any) {
  const page = usePage<any>();
  const payments = page.props?.payments ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: (payments.data ?? []) as T[],
    data: (payments.data ?? []) as T[],
    pagination: payments.meta,
    isLoading: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}

export function useCollectCash() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: any, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.post("/dashboard/payments/cash", data, {
      onSuccess: () => {
        toast.success("Cash payment collected.");
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    mutate,
    mutateAsync: mutate,
    isPending,
    isError: false,
    error: null,
    reset: () => {},
  };
}

export function useRecordQrPayment() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: any, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.post("/dashboard/payments/qr", data, {
      onSuccess: () => {
        toast.success("QR payment recorded. Verify it against the bank/app.");
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    mutate,
    mutateAsync: mutate,
    isPending,
    isError: false,
    error: null,
    reset: () => {},
  };
}

export const paymentsCrud = {
  useList: usePayments,
  useCollectCash,
  useRecordQrPayment,
};
