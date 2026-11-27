import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
  type UpdateStaff,
  UpdateStaffSchema,
} from "../schemas/updateStaff.schema";
import type { StaffDetails } from "../staff.types";

export function useUpdateStaffFacade(
  id: string,
  staff?: StaffDetails | null,
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
  } = useForm<UpdateStaff>({
    resolver: zodResolver(UpdateStaffSchema),
    values: staff
      ? {
          name: staff.user.name,
          email: staff.user.email,
          phone: staff.user.phone,
          category:
            staff.category === "RECEPTIONIST" ||
            staff.category === "HOUSEKEEPER"
              ? staff.category
              : undefined,
          fatherName: staff.fatherName,
          motherName: staff.motherName,
          idProofNumber: staff.idProofNumber,
          qualification: staff.qualification,
          experience: staff.experience,
          emergencyContact: staff.emergencyContact,
          address: staff.address,
        }
      : undefined,
  });

  const submit = (data: UpdateStaff) => {
    setIsPending(true);
    router.post(
      `/dashboard/staff/${id}`,
      { ...data, _method: "put" } as any,
      {
        forceFormData: true,
        onSuccess: () => {
          setIsSuccess(true);
          toast.success("Staff member updated successfully.");
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

export function useUpdateStaffStatus(id: string, isActive: boolean) {
  const [isPending, setIsPending] = useState(false);

  const handleToggleStatus = () => {
    setIsPending(true);
    router.patch(`/dashboard/staff/${id}/status`, { isActive: !isActive }, {
      onSuccess: () => {
        toast.success("Staff status updated successfully.");
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
