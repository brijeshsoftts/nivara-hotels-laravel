import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import { type Register, RegisterSchema } from "../schema/register.schema";

export function useRegisterFacade(onSuccess: (email: string) => void) {
  const [isPending, setIsPending] = useState(false);
  const form = useForm<Register>({
    resolver: zodResolver(RegisterSchema),
    defaultValues: { fullName: "", email: "", password: "" },
  });

  const submit = (data: Register) => {
    setIsPending(true);
    router.post("/register", data as any, {
      onSuccess: () => {
        toast.success("Account created successfully.");
        form.reset();
        onSuccess(data.email);
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
