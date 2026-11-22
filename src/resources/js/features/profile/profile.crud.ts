import { zodResolver } from "@hookform/resolvers/zod";
import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import type { SharedProps } from "@/types";
import {
  type ChangePassword,
  ChangePasswordSchema,
} from "./schemas/changePassword.schema";
import { type UpdateProfile } from "./schemas/profileUpdate.schema";
import type { Profile, ProfileDetails } from "./profile.types";

export function useChangePassword() {
  const [isPending, setIsPending] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const form = useForm<ChangePassword>({
    resolver: zodResolver(ChangePasswordSchema),
  });

  const submit = (data: ChangePassword) => {
    setIsPending(true);
    router.patch("/dashboard/profile/change-password", data as any, {
      onSuccess: () => {
        setIsSuccess(true);
        toast.success("Password changed successfully.");
        form.reset();
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
    errors: form.formState.errors,
    handleSubmit: form.handleSubmit,
    register: form.register,
  };
}

export function useUpdateProfile() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (data: UpdateProfile, options?: { onSuccess?: () => void }) => {
    setIsPending(true);
    router.patch("/dashboard/profile", data as any, {
      onSuccess: () => {
        toast.success("Profile updated successfully.");
        options?.onSuccess?.();
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
  };
}

export function useUpdateAvatar() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (file: File, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    const formData = new FormData();
    formData.append("avatar", file);

    router.post("/dashboard/profile/avatar", formData as any, {
      onSuccess: () => {
        toast.success("Profile image updated successfully.");
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

export function useRemoveAvatar() {
  const [isPending, setIsPending] = useState(false);

  const mutate = (_?: void, options?: { onSuccess?: () => void; onError?: (err: any) => void }) => {
    setIsPending(true);
    router.delete("/dashboard/profile/avatar", {
      onSuccess: () => {
        toast.success("Profile image removed successfully.");
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

export function useProfile() {
  const page = usePage<any>();
  const sharedAuth = usePage<SharedProps>().props.auth;
  const user = page.props?.profile?.data ?? sharedAuth?.user ?? null;

  return {
    user: user as Profile | null,
    isLoading: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}

export function useProfileDetails() {
  const page = usePage<any>();
  const sharedAuth = usePage<SharedProps>().props.auth;
  const data = page.props?.details?.data ?? page.props?.details ?? sharedAuth?.user ?? null;
  return {
    data: data as ProfileDetails | null,
    isLoading: false,
    isPending: false,
    isError: false,
    refetch: () => {},
  };
}
