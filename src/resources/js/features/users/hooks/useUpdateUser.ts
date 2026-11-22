import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import { type UpdateUser, UpdateUserSchema } from "../schema/updateUser.schema";

export function useUpdateUserFacade(userId: string, onSuccess?: () => void) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    reset,
    formState: { errors },
  } = useForm<UpdateUser>({
    resolver: zodResolver(UpdateUserSchema),
  });

  const submit = (data: UpdateUser) => {
    setIsPending(true);
    router.patch(`/dashboard/users/${userId}`, data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("User updated successfully.");
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
  };
}

export const useUpdateUserStatus = (userId: string, isActive: boolean) => {
  const [isPending, setIsPending] = useState(false);

  const handleToggleStatus = () => {
    setIsPending(true);
    router.patch(`/dashboard/users/${userId}/status`, { isActive: !isActive }, {
      onSuccess: () => {
        toast.success("User status updated successfully.");
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    isPending,
    handleToggleStatus,
  };
};
