import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import type { TaskStatus } from "@/types/enum.types";
import type {
  HousekeeperLookupItem,
  RoomLookupItem,
  TaskItem,
} from "./tasks.types";

export function useTasks() {
  const page = usePage<any>();
  const tasks = page.props?.tasks ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: tasks.data ?? [],
    pagination: tasks.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}

export function useTask(id: string) {
  const page = usePage<any>();
  const tasks = page.props?.tasks?.data ?? [];
  const found = tasks.find((t: any) => String(t.id) === String(id)) ?? null;

  return { data: found, isLoading: false, isError: false, refetch: () => {} };
}

export function useCreateTask(options?: { onSuccess?: (res?: any) => void; onError?: (err: any) => void }) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const form = useForm<any>();

  const mutate = (data: any, callOptions?: any) => {
    setIsPending(true);
    router.post("/dashboard/tasks", data, {
      onSuccess: (p) => {
        setIsSuccess(true);
        toast.success("Task created successfully.");
        options?.onSuccess?.(p);
        callOptions?.onSuccess?.(p);
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
        callOptions?.onError?.(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    form,
    mutation: { isPending, isSuccess, mutate, mutateAsync: mutate },
    submit: mutate,
    mutate,
    mutateAsync: mutate,
    isPending,
  };
}

export function useUpdateTask(taskId?: string, options?: { onSuccess?: (res?: any) => void; onError?: (err: any) => void }) {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const form = useForm<any>();

  const mutate = (dataOrId: any, maybeOptions?: any) => {
    setIsPending(true);
    const targetId = taskId || dataOrId?.id;
    const data = taskId ? dataOrId : dataOrId?.data ?? dataOrId;
    router.put(`/dashboard/tasks/${targetId}`, data, {
      onSuccess: (p) => {
        setIsSuccess(true);
        toast.success("Task updated successfully.");
        options?.onSuccess?.(p);
        maybeOptions?.onSuccess?.(p);
      },
      onError: (err) => {
        notifyError(err);
        options?.onError?.(err);
        maybeOptions?.onError?.(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    form,
    mutation: { isPending, isSuccess, mutate, mutateAsync: mutate },
    submit: mutate,
    mutate,
    mutateAsync: mutate,
    isPending,
  };
}

export function useDeleteTask(taskId?: string) {
  const [isDeleting, setIsDeleting] = useState(false);

  const handleDelete = (id?: string) => {
    setIsDeleting(true);
    const targetId = taskId || id;
    router.delete(`/dashboard/tasks/${targetId}`, {
      onSuccess: () => {
        toast.success("Task deleted successfully.");
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsDeleting(false),
    });
  };

  return { isDeleting, isPending: isDeleting, handleDelete, mutate: handleDelete };
}

export function useUpdateStatus(id: string) {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: { status: TaskStatus }) => {
    setIsPending(true);
    router.patch(`/dashboard/tasks/${id}/status`, data, {
      onSuccess: () => {
        toast.success("Task status updated");
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return { mutate, isPending };
}

export function useLookupHousekeepers(_params?: any) {
  const page = usePage<any>();
  const staff = page.props?.staff ?? [];
  return {
    data: staff as HousekeeperLookupItem[],
    isLoading: false,
    refetch: () => {},
  };
}

export function useLookupRooms(_params?: any) {
  const page = usePage<any>();
  const rooms = page.props?.rooms ?? [];
  return {
    data: rooms as RoomLookupItem[],
    isLoading: false,
    refetch: () => {},
  };
}

export function useHousekeeperTaskQueue() {
  const page = usePage<any>();
  const tasks = page.props?.tasks?.data ?? page.props?.tasks ?? [];
  return {
    data: (tasks as TaskItem[]).slice(0, 5),
    isLoading: false,
    refetch: () => {},
  };
}

export const tasksCrud = {
  useList: useTasks,
  useDetail: useTask,
  useDelete: useDeleteTask,
  useCreate: useCreateTask,
  useUpdate: useUpdateTask,
};
