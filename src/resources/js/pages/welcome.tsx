import LandingPage from "@/pages/public/Landing";
import { PublicLayout } from "@/layouts/PublicLayout";

export default function Welcome() {
  return (
    <PublicLayout>
      <LandingPage />
    </PublicLayout>
  );
}
