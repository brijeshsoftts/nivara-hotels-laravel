import { router, usePage } from "@inertiajs/react";
import { useEffect } from "react";

import type { AuditLog, AuditLogsQuery } from "./auditLog.types";

type AuditLogsPageProps = {
    auditLogs?: {
        data: AuditLog[];
        meta: {
            page: number;
            limit: number;
            total: number;
            totalPages: number;
        };
    };
};

export function useAuditLogs(params: AuditLogsQuery) {
    const page = usePage<AuditLogsPageProps>();
    const auditLogs = page.props.auditLogs ?? {
        data: [],
        meta: { page: 1, limit: 20, total: 0, totalPages: 1 },
    };

    useEffect(() => {
        const currentParams = new URLSearchParams(window.location.search);
        const currentSearch = currentParams.get("search") ?? "";
        const currentEntityType = currentParams.get("entityType") ?? "";
        const currentPage = Number(currentParams.get("page") ?? 1);
        const currentLimit = Number(currentParams.get("limit") ?? 20);

        if (
            currentSearch === (params.search ?? "") &&
            currentEntityType === (params.entityType ?? "") &&
            currentPage === params.page &&
            currentLimit === params.limit
        ) {
            return;
        }

        router.get(
            window.location.pathname,
            {
                search: params.search || undefined,
                entityType: params.entityType || undefined,
                page: params.page,
                limit: params.limit,
            },
            {
                only: ["auditLogs"],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [params.search, params.entityType, params.page, params.limit]);

    return {
        items: (auditLogs.data ?? []) as AuditLog[],
        pagination: auditLogs.meta,
        isLoading: false,
        isError: false,
        refetch: () => router.reload({ only: ["auditLogs"] }),
    };
}
