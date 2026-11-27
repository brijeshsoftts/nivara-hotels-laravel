import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type UpdateAmenity,
  UpdateAmenitySchema,
} from "../schemas/updateAmenity.schema";

export function useUpdateAmenityFacade(id: string) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    formState: { errors },
    reset,
    control,
  } = useForm({
    resolver: zodResolver(UpdateAmenitySchema),
  });

  const submit = (data: UpdateAmenity) => {
    setIsPending(true);
    router.put(`/dashboard/amenities/${id}`, data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Amenity updated successfully.");
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    submit,
    isPending,
    isSuccess,
    register,
    handleSubmit,
    errors,
    reset,
    control,
  };
}

export function useToggleAmenityStatus(id: string, isActive: boolean) {
  const [isPending, setIsPending] = useState(false);

  const handleToggleStatus = () => {
    setIsPending(true);
    router.patch(
      `/dashboard/amenities/${id}/status`,
      { isActive: !isActive },
      {
        onSuccess: () => {
          toast.success(
            `Amenity ${isActive ? "deactivated" : "activated"} successfully.`
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
