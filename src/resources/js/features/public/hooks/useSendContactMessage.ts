import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useSendContactMessage() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: any, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.post("/contact", data, {
      onSuccess: () => {
        toast.success("Your message has been sent successfully.");
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
