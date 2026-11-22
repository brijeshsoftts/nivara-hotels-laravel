import { Plus } from "lucide-react";

import { Header } from "@/components/common/Header";
import { HeaderActionButton } from "@/components/common/HeaderActionButton";
import { NoAccess } from "@/components/common/NoAccess";
import { CreateCustomerDialog } from "@/features/customers/components/CreateCustomerDialog";
import { CustomerStat } from "@/features/customers/components/CustomerStat";
import { CustomerTable } from "@/features/customers/components/CustomerTable";
import { useAuth } from "@/hooks/useAuth";

export default function CustomersPage({ customers, stats }: { customers?: any; stats?: any } = {}) {
  const { user } = useAuth();

  if (
    user?.role === "CUSTOMER" ||
    (user?.role === "STAFF" && user.category !== "RECEPTIONIST")
  ) {
    return <NoAccess />;
  }

  return (
    <div className="space-y-6">
      <Header
        title="Customers"
        description="Manage customers"
        rightContent={
          <CreateCustomerDialog>
            <HeaderActionButton id="btn-add-customer">
              <Plus />
              <span>New Customer</span>
            </HeaderActionButton>
          </CreateCustomerDialog>
        }
      />
      <CustomerStat />
      <CustomerTable />
    </div>
  );
}
