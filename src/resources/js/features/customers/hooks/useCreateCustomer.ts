import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type CreateCustomer,
  CreateCustomerSchema,
} from "../schema/createCustomer.schema";

export function useCreateCustomerFacade(onSuccess?: () => void) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    reset,
    formState: { errors },
  } = useForm<CreateCustomer>({
    resolver: zodResolver(CreateCustomerSchema),
  });

  const submit = (data: CreateCustomer) => {
    setIsPending(true);
    router.post("/dashboard/customers", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Customer registered successfully.");
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
  };
}
