import BookingCheckoutFlow from "@/pages/public/BookingCheckoutFlow";
import { PublicLayout } from "@/layouts/PublicLayout";

export default function Checkout(props: any) {
  return (
    <PublicLayout>
      <BookingCheckoutFlow {...props} />
    </PublicLayout>
  );
}
