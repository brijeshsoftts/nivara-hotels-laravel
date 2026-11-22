import { usePage, router } from "@inertiajs/react";
import type { AuthCTX, AuthUser } from "@/types/shared.types";

export function useAuth(): AuthCTX {
  try {
    const page = usePage<any>();
    const rawUser = page.props?.auth?.user;
    if (!rawUser) {
      return {
        isAuthenticated: false,
        isLoading: false,
        user: null,
      };
    }

    const user: AuthUser = {
      id: rawUser.id,
      fullName: rawUser.full_name || rawUser.name,
      email: rawUser.email,
      phone: rawUser.phone,
      role: rawUser.role,
      category: rawUser.category,
      isActive: rawUser.is_active,
    };

    return {
      isAuthenticated: true,
      isLoading: false,
      user: user as any,
    };
  } catch {
    return {
      isAuthenticated: false,
      isLoading: false,
      user: null,
    };
  }
}
