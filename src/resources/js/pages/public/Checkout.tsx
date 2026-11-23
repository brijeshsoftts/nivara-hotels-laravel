import BookingCheckoutFlow from "@/features/public/components/BookingCheckoutFlow";
import { PublicLayout } from "@/layouts/PublicLayout";

export default function Checkout(props: any) {
    return (
        <PublicLayout>
            <BookingCheckoutFlow {...props} />
        </PublicLayout>
    );
}
