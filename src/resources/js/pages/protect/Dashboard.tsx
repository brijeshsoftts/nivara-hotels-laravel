import { usePage } from "@inertiajs/react";
import type { SharedProps } from "@/types";
import { DashboardLayout } from "@/layouts/DashboardLayout";
import { ManagementDashboard } from "@/features/dashboard/components/ManagementDashboard";
import { ReceptionistDashboard } from "@/features/dashboard/components/ReceptionistDashboard";
import { HousekeeperDashboard } from "@/features/dashboard/components/HousekeeperDashboard";
import { CustomerDashboard } from "@/features/dashboard/components/CustomerDashboard";
import { NoAccess } from "@/components/common/NoAccess";

export interface DashboardProps {
  stats?: any[];
  roomStatus?: any;
  revenueTrend?: any[];
  bookingStatus?: any;
  stays?: any;
  tasks?: any[];
  nextBooking?: any;
}

export default function Dashboard(props: DashboardProps) {
  const { auth } = usePage<SharedProps>().props;
  const user = auth?.user;

  const renderDashboardContent = () => {
    switch (user?.role) {
      case "ADMIN":
      case "MANAGER":
        return (
          <ManagementDashboard
            stats={props.stats}
            stays={props.stays}
            revenueTrend={props.revenueTrend}
            bookingStatus={props.bookingStatus}
          />
        );
      case "STAFF":
        if (user.category === "RECEPTIONIST") {
          return (
            <ReceptionistDashboard
              stats={props.stats}
              stays={props.stays}
              roomStatus={props.roomStatus}
              bookingStatus={props.bookingStatus}
            />
          );
        }
        if (user.category === "HOUSEKEEPER") {
          return (
            <HousekeeperDashboard
              tasks={props.tasks}
              roomStatus={props.roomStatus}
            />
          );
        }
        return <NoAccess />;
      case "CUSTOMER":
        return <CustomerDashboard />;
      default:
        return (
          <ManagementDashboard
            stats={props.stats}
            stays={props.stays}
            revenueTrend={props.revenueTrend}
            bookingStatus={props.bookingStatus}
          />
        );
    }
  };

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="font-heading text-2xl font-bold tracking-tight text-foreground md:text-3xl">
            Welcome, {auth.user?.name}
          </h1>
          <p className="text-sm text-muted-foreground">{auth.user?.email}</p>
        </div>
        {renderDashboardContent()}
      </div>
    </DashboardLayout>
  );
}
