import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type ResetPassword,
  ResetPasswordSchema,
} from "../schema/reset-password.schema";

export function useForgotPassword() {
  const [isPending, setIsPending] = useState(false);
  const mutate = (data: { email: string }, options?: { onSuccess?: () => void }) => {
    setIsPending(true);
    router.post("/forgot-password", data, {
      onSuccess: () => {
        toast.success("If the account exists, a reset link has been sent.");
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    mutate,
    isPending,
  };
}

export function useResetPassword(token: string) {
  const [isPending, setIsPending] = useState(false);
  const form = useForm<ResetPassword>({
    resolver: zodResolver(ResetPasswordSchema),
  });

  const submit = (data: ResetPassword) => {
    setIsPending(true);
    router.post("/reset-password", { ...data, token }, {
      onSuccess: () => {
        toast.success("Password reset successfully.");
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
    error: null,
    handleSubmit: form.handleSubmit,
    register: form.register,
    errors: form.formState.errors,
    getValues: form.getValues,
    reset: form.reset,
    resetMutation: () => {},
  };
}
