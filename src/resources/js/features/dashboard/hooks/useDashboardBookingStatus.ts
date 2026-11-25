import { useEffect, useState } from "react";

type BookingStatusRange = "all" | "7d" | "14d" | "28d";

type BookingStatusItem = {
    status: string;
    count: number;
};

export function useDashboardBookingStatus(
    range: BookingStatusRange,
    initialData: BookingStatusItem[] = [],
) {
    const [data, setData] = useState(initialData);
    const [isLoading, setIsLoading] = useState(false);
    const [isError, setIsError] = useState(false);
    const [retryCount, setRetryCount] = useState(0);

    useEffect(() => {
        if (range === "all") {
            setData(initialData);
            setIsLoading(false);
            setIsError(false);
            return;
        }

        const controller = new AbortController();
        setIsLoading(true);
        setIsError(false);

        fetch(`/dashboard/booking-status?range=${range}`, {
            headers: { Accept: "application/json" },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error("Booking status data could not be loaded.");
                }

                return (await response.json()) as {
                    data?: BookingStatusItem[];
                };
            })
            .then((response) => {
                setData(Array.isArray(response.data) ? response.data : []);
            })
            .catch(() => {
                if (!controller.signal.aborted) {
                    setIsError(true);
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setIsLoading(false);
                }
            });

        return () => controller.abort();
    }, [initialData, range, retryCount]);

    return {
        data,
        isLoading,
        isError,
        refetch: () => setRetryCount((count) => count + 1),
    };
}
