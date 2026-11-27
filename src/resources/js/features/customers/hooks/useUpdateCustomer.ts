import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import type { CustomerDetails } from "../customers.types";
import {
  type UpdateCustomer,
  UpdateCustomerSchema,
} from "../schema/updateCustomer.schema";

export function useUpdateCustomerFacade(
  id: string,
  customer?: CustomerDetails | null,
  onSuccess?: () => void
) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    reset,
    formState: { errors },
  } = useForm<UpdateCustomer>({
    resolver: zodResolver(UpdateCustomerSchema),
    values: customer
      ? {
          name: customer.name,
          phone: customer.phone,
          email: customer.email,
          address: customer.address ?? "",
          idProofNumber: customer.idProofNumber ?? "",
        }
      : undefined,
  });

  const submit = (data: UpdateCustomer) => {
    setIsPending(true);
    router.post(
      `/dashboard/customers/${id}`,
      { ...data, _method: "put" } as any,
      {
        forceFormData: true,
        onSuccess: () => {
          setIsSuccess(true);
          toast.success("Customer updated successfully");
          onSuccess?.();
        },
        onError: (err) => {
          notifyError(err);
        },
        onFinish: () => setIsPending(false),
      }
    );
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

export function useUpdateCustomerStatus(id: string, isActive: boolean) {
  const [isPending, setIsPending] = useState(false);

  const handleToggleStatus = () => {
    setIsPending(true);
    router.patch(`/dashboard/customers/${id}/status`, { isActive: !isActive }, {
      onSuccess: () => {
        toast.success("Customer status updated successfully.");
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
}
