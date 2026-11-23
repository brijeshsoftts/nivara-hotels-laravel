import { usePage } from "@inertiajs/react";

export function usePaymentsStats() {
    const page = usePage<any>();
    const stats = page.props?.stats;

    return {
        data: Array.isArray(stats)
            ? stats.map((stat) => ({
                  ...stat,
                  label: stat.title ?? stat.label ?? "",
              }))
            : [],
        isLoading: false,
        isError: false,
        refetch: () => {},
    };
}
