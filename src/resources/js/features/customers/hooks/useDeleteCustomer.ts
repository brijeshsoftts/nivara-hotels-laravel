import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useDeleteCustomer(id: string) {
  const [isDeleting, setIsDeleting] = useState(false);

  const handleDelete = () => {
    setIsDeleting(true);
    router.delete(`/dashboard/customers/${id}`, {
      onSuccess: () => {
        toast.success("Customer deleted successfully");
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
