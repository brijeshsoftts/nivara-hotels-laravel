import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type UpdateTask,
  UpdateTaskSchema,
} from "../schemas/updateTask.schema";

export function useUpdateTaskFacade(id: string) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    formState: { errors },
    getValues,
    reset,
  } = useForm({
    resolver: zodResolver(UpdateTaskSchema),
  });

  const submit = (data: UpdateTask) => {
    setIsPending(true);
    router.put(`/dashboard/tasks/${id}`, data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Task updated successfully.");
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
    getValues,
    reset,
  };
}
