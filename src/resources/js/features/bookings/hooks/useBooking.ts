import { useEffect, useState } from "react";
import type { BookingDetails } from "../bookings.types";

export function useBooking<T = BookingDetails>(id?: string, enabled = true) {
    const [booking, setBooking] = useState<T | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [isError, setIsError] = useState(false);
    const [requestId, setRequestId] = useState(0);

    useEffect(() => {
        if (!id || !enabled) return;

        const controller = new AbortController();
        setIsLoading(true);
        setIsError(false);

        fetch(`/bookings/${encodeURIComponent(id)}`, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok)
                    throw new Error("Unable to load booking details.");
                const result = await response.json();
                setBooking(result.data ?? null);
            })
            .catch((error: unknown) => {
                if (
                    error instanceof DOMException &&
                    error.name === "AbortError"
                )
                    return;
                setBooking(null);
                setIsError(true);
            })
            .finally(() => {
                if (!controller.signal.aborted) setIsLoading(false);
            });

        return () => controller.abort();
    }, [id, enabled, requestId]);

    const isPending = enabled && (isLoading || (!booking && !isError));

    return {
        booking,
        data: booking,
        isLoading: isPending,
        isFetching: isPending,
        isError,
        refetch: () => setRequestId((current) => current + 1),
    };
}

export const useBookingDetails = useBooking;
