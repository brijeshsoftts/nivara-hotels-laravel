import { NoAccess } from "@/components/common/NoAccess";
import { CustomerIssues } from "@/features/issues/components/CustomerIssues";
import { ManagementIssue } from "@/features/issues/components/ManagementIssue";
import { useAuth } from "@/hooks/useAuth";

export default function IssuesPage(props: any) {
  const { user } = useAuth();

  switch (user?.role) {
    case "ADMIN":
    case "MANAGER":
      return <ManagementIssue {...props} />;
    case "CUSTOMER":
      return <CustomerIssues {...props} />;
    default:
      return <NoAccess />;
  }
}
