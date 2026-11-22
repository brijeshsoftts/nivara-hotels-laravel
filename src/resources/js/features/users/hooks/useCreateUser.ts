import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import { type CreateUser, CreateUserSchema } from "../schema/createUser.schema";

export function useCreateUserFacade(onSuccess?: () => void) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    reset,
    formState: { errors },
  } = useForm<CreateUser>({
    resolver: zodResolver(CreateUserSchema),
  });

  const submit = (data: CreateUser) => {
    setIsPending(true);
    router.post("/dashboard/users", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("User created successfully.");
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
    reset,
    handleSubmit,
    errors,
  };
}
