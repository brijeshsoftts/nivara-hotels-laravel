import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useDeleteRoom() {
  const [isDeleting, setIsDeleting] = useState(false);

  const handleDelete = (id: string, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsDeleting(true);
    router.delete(`/dashboard/rooms/${id}`, {
      onSuccess: () => {
        toast.success("Room deleted successfully.");
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
      },
      onFinish: () => setIsDeleting(false),
    });
  };

  return {
    isDeleting,
    handleDelete,
  };
}
