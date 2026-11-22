import { createContext, type ReactNode } from "react";
import { usePage } from "@inertiajs/react";

import { useProfile } from "@/features/profile/profile.crud";
import type { AuthCTX } from "@/types/shared.types";

const initialCTX: AuthCTX = {
  isAuthenticated: false,
  isLoading: false,
  user: null,
};

export const AuthContext = createContext<AuthCTX>(initialCTX);

export function AuthProvider({ children }: { children: ReactNode }) {
  let pageUser: any = null;
  try {
    const page = usePage();
    pageUser = (page.props as any)?.auth?.user;
  } catch {
    // If rendered outside Inertia context
  }

  const { user: queryUser, isLoading: queryLoading } = useProfile();

  const user = pageUser || queryUser || null;
  const isLoading = pageUser ? false : queryLoading;

  return (
    <AuthContext.Provider
      value={{
        isAuthenticated: !!user,
        isLoading: isLoading,
        user: user ?? null,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}
