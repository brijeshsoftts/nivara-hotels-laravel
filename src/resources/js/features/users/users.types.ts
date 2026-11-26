import type { Category, UserRole } from "@/types/enum.types";

export type User = {
  id: string;
  name: string;
  email: string;
  phone: string;
  role: UserRole;
  isActive: boolean;
  avatar: {
    url: string;
    altText: string | null;
  } | null;
};

export type UserDetails = {
  id: string;
  name: string;
  email: string;
  phone: string;
  role: UserRole;
  isActive: boolean;
  createdAt: string;
  lastLoginAt: string | null;
  category?: Category;
  avatar: {
    id: string;
    url: string;
    altText: string | null;
  } | null;
};

export type UserProfile = UserDetails;
