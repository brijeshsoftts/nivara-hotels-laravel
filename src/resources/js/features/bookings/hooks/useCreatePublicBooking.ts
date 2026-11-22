import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useCreatePublicBooking() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: any, options?: { onSuccess?: (res: any) => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.post("/checkout", data, {
      onSuccess: (page) => {
        toast.success("Booking submitted successfully.");
        options?.onSuccess?.(page);
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  const mutateAsync = (data: any, options?: { onSuccess?: (res: any) => void; onError?: (err: any) => void }): Promise<any> => {
    return new Promise((resolve, reject) => {
      setIsPending(true);
      router.post("/checkout", data, {
        onSuccess: (page) => {
          toast.success("Booking submitted successfully.");
          options?.onSuccess?.(page);
          resolve((page?.props as any)?.booking ?? page);
        },
        onError: (err) => {
          notifyError(err);
          options?.onError?.(err);
          reject(err);
        },
        onFinish: () => setIsPending(false),
      });
    });
  };

  return {
    mutate,
    mutateAsync,
    isPending,
  };
}
