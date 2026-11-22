import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useUpdateBookingStatus() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (
    { id, status }: { id: string; status: string },
    options?: { onSuccess?: () => void; onError?: (err: any) => void }
  ) => {
    setIsPending(true);
    router.patch(`/dashboard/bookings/${id}/status`, { status }, {
      onSuccess: () => {
        toast.success("Booking status updated.");
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
    isPending,
  };
}
