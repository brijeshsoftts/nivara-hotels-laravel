import { PublicLayout } from "@/layouts/PublicLayout";
import Landing from "@/features/public/components/LandingPage";

export interface HomeProps {
    featuredRooms?: any;
    amenities?: any;
}

export default function Home({ featuredRooms, amenities }: HomeProps = {}) {
    return (
        <PublicLayout>
            <Landing featuredRooms={featuredRooms} amenities={amenities} />
        </PublicLayout>
    );
}
