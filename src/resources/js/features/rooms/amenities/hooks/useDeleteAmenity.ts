import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";

export function useDeleteAmenity(id: string) {
  const [isPending, setIsPending] = useState(false);

  const handleDelete = () => {
    setIsPending(true);
    router.delete(`/dashboard/amenities/${id}`, {
      onSuccess: () => {
        toast.success("Amenity deleted successfully.");
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    handleDelete,
    isPending,
  };
}
