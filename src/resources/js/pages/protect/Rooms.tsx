import { NoAccess } from "@/components/common/NoAccess";
import { HouseKeeperRooms } from "@/features/rooms/components/HouseKeeperRooms";
import { ManagementRooms } from "@/features/rooms/components/ManagementRooms";
import { useAuth } from "@/hooks/useAuth";

export default function RoomsPage(props: any) {
  const { user } = useAuth();

  switch (user?.role) {
    case "ADMIN":
    case "MANAGER":
      return <ManagementRooms {...props} />;
    case "STAFF":
      if (user.category === "RECEPTIONIST") {
        return <ManagementRooms {...props} />;
      }
      if (user.category === "HOUSEKEEPER") {
        return <HouseKeeperRooms {...props} />;
      }
      return <NoAccess />;
    case "CUSTOMER":
    default:
      return <NoAccess />;
  }
}
