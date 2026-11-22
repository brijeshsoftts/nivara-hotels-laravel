import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import { type Login, LoginSchema } from "../schema/login.schema";

export function useLogin() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: Login) => {
    setIsPending(true);
    router.post("/login", data as any, {
      onSuccess: () => {
        toast.success("Logged in successfully.");
        window.location.href = "/dashboard";
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
    isSuccess: false,
    error: null,
    reset: () => {},
  };
}

export function useLoginFacade() {
  const mutation = useLogin();
  const { mutate, isPending } = mutation;

  const {
    handleSubmit,
    register,
    formState: { errors },
    getValues,
    reset,
  } = useForm<Login>({
    resolver: zodResolver(LoginSchema),
  });

  return {
    submit: (data: Login) => mutate(data),
    isPending,
    isSuccess: false,
    error: null,
    register,
    handleSubmit,
    errors,
    getValues,
    reset,
    resetMutation: () => {},
  };
}
