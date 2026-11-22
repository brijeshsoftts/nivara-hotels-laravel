import LandingPage from "@/pages/public/Landing";
import { PublicLayout } from "@/layouts/PublicLayout";

export default function PublicHome() {
  return (
    <PublicLayout>
      <LandingPage />
    </PublicLayout>
  );
}
