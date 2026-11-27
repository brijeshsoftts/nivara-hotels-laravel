import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import type { UpdateRoomTypePayload } from "../schemas/updateRoomType.schema";

export function useUpdateRoomType(id: string) {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: UpdateRoomTypePayload, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.post(
      `/dashboard/room-types/${id}`,
      { ...data, _method: "put" } as any,
      {
        forceFormData: true,
        onSuccess: () => {
          toast.success("Room type updated successfully.");
          options?.onSuccess?.();
        },
        onError: (err) => {
          notifyError(err);
          options?.onError?.(err);
        },
        onFinish: () => setIsPending(false),
      }
    );
  };

  return {
    mutate,
    isPending,
  };
}

export function useToggleRoomTypeStatus(id: string, isActive: boolean) {
  const [isPending, setIsPending] = useState(false);

  const handleToggleStatus = () => {
    setIsPending(true);
    router.patch(
      `/dashboard/room-types/${id}/status`,
      { isActive: !isActive },
      {
        onSuccess: () => {
          toast.success(
            `Room type ${isActive ? "deactivated" : "activated"} successfully.`
          );
        },
        onError: (err) => {
          notifyError(err);
        },
        onFinish: () => setIsPending(false),
      }
    );
  };

  return {
    isPending,
    handleToggleStatus,
  };
}
