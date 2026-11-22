import { createContext, type ReactNode } from "react";
import { usePage } from "@inertiajs/react";
import type { SharedProps } from "@/types";

export interface AuthContextType {
  isAuthenticated: boolean;
  isLoading: boolean;
  user: any;
}

export const AuthContext = createContext<AuthContextType>({
  isAuthenticated: false,
  isLoading: false,
  user: null,
});

export function AuthProvider({ children }: { children: ReactNode }) {
  let user: any = null;
  try {
    const { auth } = usePage<SharedProps>().props;
    user = auth?.user ?? null;
  } catch {
    // If rendered outside Inertia context
  }

  return (
    <AuthContext.Provider
      value={{
        isAuthenticated: !!user,
        isLoading: false,
        user,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}
