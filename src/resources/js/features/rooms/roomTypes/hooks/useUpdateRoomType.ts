import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import type { UpdateRoomTypePayload } from "../schemas/updateRoomType.schema";

export function useUpdateRoomType(id: string) {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: UpdateRoomTypePayload, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.put(`/dashboard/room-types/${id}`, data as any, {
      onSuccess: () => {
        toast.success("Room type updated successfully.");
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
