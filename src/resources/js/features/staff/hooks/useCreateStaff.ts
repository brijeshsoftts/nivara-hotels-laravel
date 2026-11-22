import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type CreateStaff,
  CreateStaffSchema,
} from "../schemas/createStaff.schema";

export function useCreateStaffFacade(onSuccess?: () => void) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    reset,
    formState: { errors },
  } = useForm<CreateStaff>({
    resolver: zodResolver(CreateStaffSchema),
  });

  const submit = (data: CreateStaff) => {
    setIsPending(true);
    router.post("/dashboard/staff", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Staff member registered successfully.");
        reset();
        onSuccess?.();
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
    control,
    handleSubmit,
    errors,
    reset,
  };
}
