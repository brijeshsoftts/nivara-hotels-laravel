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
  cleaningRooms?: any[];
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
              cleaningRooms={props.cleaningRooms}
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
      {renderDashboardContent()}
    </DashboardLayout>
  );
}
