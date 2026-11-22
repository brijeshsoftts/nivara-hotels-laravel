import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type CreateAmenity,
  CreateAmenitySchema,
} from "../schemas/createAmenity.schema";

export function useCreateAmenityFacade() {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    formState: { errors },
    reset,
    control,
  } = useForm({
    resolver: zodResolver(CreateAmenitySchema),
  });

  const submit = (data: CreateAmenity) => {
    setIsPending(true);
    router.post("/dashboard/amenities", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Amenity created successfully.");
        reset();
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
