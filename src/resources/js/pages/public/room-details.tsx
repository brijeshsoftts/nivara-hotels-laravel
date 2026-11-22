import RoomDetailsPage from "@/pages/public/RoomDetails";
import { PublicLayout } from "@/layouts/PublicLayout";

export default function RoomDetails(props: any) {
  return (
    <PublicLayout>
      <RoomDetailsPage {...props} />
    </PublicLayout>
  );
}
