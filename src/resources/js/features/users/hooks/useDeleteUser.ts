import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useDeleteUser(id: string) {
  const [isDeleting, setIsDeleting] = useState(false);

  const handleDelete = () => {
    setIsDeleting(true);
    router.delete(`/dashboard/users/${id}`, {
      onSuccess: () => {
        toast.success("User deleted successfully");
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsDeleting(false),
    });
  };

  return {
    isDeleting,
    handleDelete,
  };
}
