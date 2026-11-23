import { useEffect, useState } from "react";

import type { InvoiceDetails } from "../invoices.types";

export function useInvoiceDetails(id: string) {
    const [data, setData] = useState<InvoiceDetails | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isError, setIsError] = useState(false);
    const [requestNumber, setRequestNumber] = useState(0);

    useEffect(() => {
        const controller = new AbortController();

        async function loadInvoice() {
            setIsLoading(true);
            setIsError(false);

            try {
                const response = await fetch(
                    `/invoices/${encodeURIComponent(id)}`,
                    {
                        headers: { Accept: "application/json" },
                        signal: controller.signal,
                    },
                );

                if (!response.ok)
                    throw new Error("Could not load invoice details");

                const result = await response.json();
                setData(result.data as InvoiceDetails);
            } catch (error) {
                if (!controller.signal.aborted) {
                    setData(null);
                    setIsError(true);
                }
            } finally {
                if (!controller.signal.aborted) setIsLoading(false);
            }
        }

        void loadInvoice();
        return () => controller.abort();
    }, [id, requestNumber]);

    return {
        data,
        isLoading,
        isError,
        refetch: () => setRequestNumber((number) => number + 1),
    };
}
