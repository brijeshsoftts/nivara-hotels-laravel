import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type CreateTask,
  CreateTaskSchema,
} from "../schemas/createTask.schema";

export function useCreateTaskFacade() {
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
    resolver: zodResolver(CreateTaskSchema),
  });

  const submit = (data: CreateTask) => {
    setIsPending(true);
    router.post("/dashboard/tasks", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Task created successfully.");
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
    control,
    handleSubmit,
    errors,
    getValues,
    reset,
  };
}
