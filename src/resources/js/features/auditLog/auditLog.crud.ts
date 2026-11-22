import { usePage } from "@inertiajs/react";
import type { AuditLog, AuditLogsQuery } from "./auditLog.types";

export function useAuditLogs(_params?: AuditLogsQuery) {
  const page = usePage<any>();
  const auditLogs = page.props?.auditLogs ?? { data: [], meta: { page: 1, limit: 10, total: 0, totalPages: 1 } };

  return {
    items: (auditLogs.data ?? []) as AuditLog[],
    pagination: auditLogs.meta,
    isLoading: false,
    isError: false,
    refetch: () => {},
  };
}
