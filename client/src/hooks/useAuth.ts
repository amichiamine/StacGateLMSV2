import { useQuery } from "@tanstack/react-query";
import type { User } from "@shared/schema";

export interface AuthUser extends Omit<User, 'establishmentId' | 'role'> {
  establishmentId: string;
  role: string;
}

export function useAuth() {
  const { data: user, isLoading } = useQuery<AuthUser>({
    queryKey: ["/api/auth/user"],
    retry: false,
  });

  return {
    user,
    isLoading,
    isAuthenticated: !!user,
  };
}