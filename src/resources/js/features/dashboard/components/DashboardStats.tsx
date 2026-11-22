import { StatGrid } from "@/components/common/Stat";
import { usePage } from "@inertiajs/react";

export function DashboardStats({ stats: propStats }: { stats?: any[] } = {}) {
  const page = usePage<any>();
  const stats = propStats ?? page.props?.stats ?? [];

  return <StatGrid stats={stats} isLoading={false} isError={false} />;
}
