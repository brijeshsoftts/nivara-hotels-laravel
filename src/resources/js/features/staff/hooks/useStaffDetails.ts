import { useEffect, useState } from "react";

import type { StaffDetails } from "../staff.types";

export function useStaffDetails(id: string) {
    const [data, setData] = useState<StaffDetails | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isError, setIsError] = useState(false);
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        const controller = new AbortController();
        setData(null);
        setIsLoading(true);
        setIsError(false);

        void (async () => {
            try {
                const response = await fetch(
                    `/staff/${encodeURIComponent(id)}`,
                    {
                        headers: { Accept: "application/json" },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error("Unable to load staff details.");
                }

                const result = (await response.json()) as {
                    data: StaffDetails;
                };
                setData(result.data);
            } catch {
                if (!controller.signal.aborted) {
                    setData(null);
                    setIsError(true);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setIsLoading(false);
                }
            }
        })();

        return () => controller.abort();
    }, [id, attempt]);

    return {
        data,
        isLoading,
        isError,
        refetch: () => setAttempt((current) => current + 1),
    };
}
