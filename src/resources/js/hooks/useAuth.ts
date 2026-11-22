import { usePage } from "@inertiajs/react";
import type { SharedProps } from "@/types";

export { useLogout } from "./useLogout";

export function useAuth() {
  try {
    const { auth } = usePage<SharedProps>().props;
    const rawUser = auth?.user;
    if (!rawUser) {
      return {
        isAuthenticated: false,
        isLoading: false,
        user: null,
      };
    }

    return {
      isAuthenticated: true,
      isLoading: false,
      user: rawUser,
    };
  } catch {
    return {
      isAuthenticated: false,
      isLoading: false,
      user: null,
    };
  }
}
