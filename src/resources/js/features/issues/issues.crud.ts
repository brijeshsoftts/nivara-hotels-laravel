import { zodResolver } from "@hookform/resolvers/zod";
import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { type DefaultValues, useForm, type UseFormReturn } from "react-hook-form";
import { toast } from "sonner";
import type { z } from "zod";

import { notifyError } from "@/lib/notification";
import {
  adminUpdateIssueSchema,
  customerCreateIssueSchema,
} from "./schemas/issue.schema";
import type { IssueDetails } from "./issues.types";

export function useIssues(_params?: any) {
  const page = usePage<any>();
  const issues = page.props?.issues ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };
  return {
    items: (issues.data ?? []) as IssueDetails[],
    pagination: issues.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}

export function useIssue(id: string | undefined) {
  const page = usePage<any>();
  const issues = page.props?.issues?.data ?? [];
  const found = issues.find((i: any) => String(i.id) === String(id)) ?? null;
  return {
    data: found as IssueDetails | null,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}

export function useIssueStats() {
  const page = usePage<any>();
  return {
    data: page.props?.stats ?? [],
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}

export function useDeleteIssue() {
  const [isDeleting, setIsDeleting] = useState(false);
  const handleDelete = (id: string) => {
    setIsDeleting(true);
    router.delete(`/dashboard/issues/${id}`, {
      onSuccess: () => toast.success("Issue deleted successfully."),
      onError: notifyError,
      onFinish: () => setIsDeleting(false),
    });
  };
  return { isDeleting, handleDelete };
}

type CustomerCreateIssueInput = z.input<typeof customerCreateIssueSchema>;
type CustomerCreateIssueOutput = z.output<typeof customerCreateIssueSchema>;

export function useCreateIssue(options?: {
  defaultValues?: DefaultValues<CustomerCreateIssueInput>;
  onSuccess?: () => void;
}) {
  const [isPending, setIsPending] = useState(false);

  const form = useForm<CustomerCreateIssueInput>({
    resolver: zodResolver(customerCreateIssueSchema as any),
    defaultValues: options?.defaultValues,
  });

  const submit = (data: CustomerCreateIssueInput) => {
    setIsPending(true);
    router.post("/dashboard/issues", data as any, {
      onSuccess: () => {
        toast.success("Issue reported successfully.");
        form.reset();
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    form: form as unknown as UseFormReturn<CustomerCreateIssueInput, unknown, CustomerCreateIssueOutput>,
    submit,
    mutation: {
      isPending,
      mutate: submit,
    },
  };
}

export const useCreateIssueCrud = useCreateIssue;

export function useUpdateIssue(
  id: string,
  options?: {
    defaultValues?: any;
    onSuccess?: () => void;
  }
) {
  const [isPending, setIsPending] = useState(false);

  const form = useForm({
    resolver: zodResolver(adminUpdateIssueSchema as any),
    defaultValues: options?.defaultValues,
  });

  const submit = (data: any) => {
    setIsPending(true);
    router.put(`/dashboard/issues/${id}`, data as any, {
      onSuccess: () => {
        toast.success("Issue updated successfully.");
        options?.onSuccess?.();
      },
      onError: (err) => {
        notifyError(err);
      },
      onFinish: () => setIsPending(false),
    });
  };

  return {
    form,
    submit,
    mutation: {
      isPending,
      mutate: submit,
    },
  };
}

export const issuesCrud = {
  useList: useIssues,
  useDetail: useIssue,
  useCreate: useCreateIssue,
  useUpdate: useUpdateIssue,
  useDelete: useDeleteIssue,
  useStats: useIssueStats,
};
