import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";

import { notifyError } from "@/lib/notification";
import {
    type CreateRoomType,
    CreateRoomTypeSchema,
} from "../schemas/createRoomType.schema";

export function useCreateRoomTypeFacade() {
    const [isPending, setIsPending] = useState(false);
    const [isSuccess, setIsSuccess] = useState(false);

    const {
        handleSubmit,
        register,
        formState: { errors },
        reset,
        control,
        setValue,
        getValues,
        setError,
        clearErrors,
    } = useForm<CreateRoomType>({
        resolver: zodResolver(CreateRoomTypeSchema) as any,
        defaultValues: {
            amenities: [],
            images: [],
            smokingAllowed: false,
            petsAllowed: false,
        },
    });

    const submit = (data: CreateRoomType, onDone?: () => void) => {
        setIsPending(true);
        router.post("/dashboard/room-types", data as any, {
            onSuccess: () => {
                setIsSuccess(true);
                toast.success("Room type created successfully.");
                reset();
                onDone?.();
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
        handleSubmit,
        errors,
        reset,
        control,
        setValue,
        getValues,
        setError,
        clearErrors,
    };
}
