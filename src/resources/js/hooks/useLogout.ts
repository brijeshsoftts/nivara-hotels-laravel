import { router } from "@inertiajs/react";
import { useState } from "react";

export function useLogout() {
  const [isLoading, setIsLoading] = useState(false);

  const handleLogout = () => {
    setIsLoading(true);
    router.post("/logout", {}, {
      onFinish: () => setIsLoading(false),
    });
  };

  return {
    handleLogout,
    isLoading,
  };
}

