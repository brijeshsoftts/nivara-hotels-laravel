import { PublicLayout } from "@/layouts/PublicLayout";
import Landing from "./Landing";

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

