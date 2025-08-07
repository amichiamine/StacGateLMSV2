import { useAuth } from "@/hooks/useAuth";
import { useEffect } from "react";
import { useQuery } from "@tanstack/react-query";

export default function Home() {
  const { user, isAuthenticated, isLoading } = useAuth();

  // Récupérer la liste des établissements
  const { data: establishments } = useQuery({
    queryKey: ['/api/establishments'],
  });

  // Logique de redirection intelligente
  useEffect(() => {
    if (!isLoading) {
      if (isAuthenticated && user) {
        // Si l'utilisateur est connecté, le rediriger vers son dashboard
        window.location.href = '/dashboard';
      } else {
        // Si l'utilisateur n'est pas connecté, le rediriger vers le portail
        window.location.href = '/portal';
      }
    }
  }, [isAuthenticated, isLoading, user]);

  // Affichage de chargement pendant la redirection
  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
      <div className="text-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
        <p className="mt-4 text-gray-600 dark:text-gray-400">Redirection en cours...</p>
      </div>
    </div>
  );
}