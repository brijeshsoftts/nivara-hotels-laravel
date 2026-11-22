import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type CreateRoom,
  CreateRoomSchema,
} from "../schemas/createRoom.schema";

export function useCreateRoomFacade() {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    handleSubmit,
    register,
    control,
    reset,
    formState: { errors },
  } = useForm<CreateRoom>({
    resolver: zodResolver(CreateRoomSchema) as any,
  });

  const submit = (data: CreateRoom) => {
    setIsPending(true);
    router.post("/dashboard/rooms", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Room created successfully.");
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
    reset,
  };
}
