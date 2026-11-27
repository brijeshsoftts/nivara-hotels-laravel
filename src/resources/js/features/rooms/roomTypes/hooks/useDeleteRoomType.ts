import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useDeleteRoomType(id: string) {
  const [isPending, setIsPending] = useState(false);

  const handleDelete = (onSuccess?: () => void) => {
    setIsPending(true);
    router.delete(`/dashboard/room-types/${id}`, {
      onSuccess: () => {
        toast.success("Room type deleted successfully.");
        onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    isPending,
    handleDelete,
  };
}

