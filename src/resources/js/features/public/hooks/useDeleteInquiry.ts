import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useDeleteInquiry(id: string) {
  const [isDeleting, setIsDeleting] = useState(false);

  const handleDelete = () => {
    setIsDeleting(true);
    router.delete(`/dashboard/inquiries/${id}`, {
      onSuccess: () => {
        toast.success("Inquiry deleted successfully.");
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
