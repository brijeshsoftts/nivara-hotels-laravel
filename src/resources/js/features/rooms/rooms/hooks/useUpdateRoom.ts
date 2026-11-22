import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type UpdateRoom,
  UpdateRoomSchema,
} from "../schemas/updateRoom.schema";

export function useReleaseOutOfOrderRoom(id: string) {
  const [isPending, setIsPending] = useState(false);

  const mutate = (_data?: any, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.patch(`/dashboard/rooms/${id}/release-out-of-order`, {}, {
      onSuccess: () => {
        toast.success("Room returned to service.");
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    mutate,
    isPending,
  };
}

export function useUpdateRoomMutation(id: string) {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: any, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.put(`/dashboard/rooms/${id}`, data as any, {
      onSuccess: () => {
        toast.success("Room updated successfully.");
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    mutate,
    isPending,
  };
}

export function useUpdateRoomFacade(id: string) {
  const { mutate, isPending, isSuccess } = useUpdateRoomMutation(id) as any;

  const {
    handleSubmit,
    register,
    formState: { errors },
    getValues,
    reset,
    control,
  } = useForm({
    resolver: zodResolver(UpdateRoomSchema),
  });

  const submit = (data: UpdateRoom) => {
    mutate(data);
  };

  return {
    submit,
    isPending,
    isSuccess: isSuccess || false,
    register,
    handleSubmit,
    errors,
    getValues,
    reset,
    control,
  };
}
