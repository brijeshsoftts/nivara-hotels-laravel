import { useEffect, useState } from "react";
import type { SearchCustomer } from "../customers.types";

export function useSearchCustomers(search?: string) {
    const [items, setItems] = useState<SearchCustomer[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isError, setIsError] = useState(false);
    const [requestId, setRequestId] = useState(0);

    useEffect(() => {
        const controller = new AbortController();
        const query = new URLSearchParams();
        const searchTerm = search?.trim();

        if (searchTerm) query.set("q", searchTerm);

        setIsLoading(true);
        setIsError(false);

        fetch(`/customers/search?${query}`, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error("Unable to search customers.");
                }

                const result = await response.json();
                setItems(Array.isArray(result.data) ? result.data : []);
            })
            .catch((error: unknown) => {
                if (
                    error instanceof DOMException &&
                    error.name === "AbortError"
                ) {
                    return;
                }

                setItems([]);
                setIsError(true);
            })
            .finally(() => {
                if (!controller.signal.aborted) setIsLoading(false);
            });

        return () => controller.abort();
    }, [search, requestId]);

    return {
        items,
        isLoading,
        isError,
        refetch: () => setRequestId((current) => current + 1),
    };
}
