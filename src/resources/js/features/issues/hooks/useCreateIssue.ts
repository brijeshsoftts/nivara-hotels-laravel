import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type CreateIssue,
  CreateIssueSchema,
} from "../schemas/createIssue.schema";

export function useCreateIssueFacade() {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    formState: { errors },
    getValues,
    reset,
  } = useForm<CreateIssue>({
    resolver: zodResolver(CreateIssueSchema),
  });

  const submit = (data: CreateIssue) => {
    setIsPending(true);
    router.post("/dashboard/issues", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Issue reported successfully.");
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
