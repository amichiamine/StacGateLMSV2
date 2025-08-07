import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Building, Users, Shield, Plus } from "lucide-react";
import { useAuth } from "@/hooks/useAuth";
import { Link } from "wouter";
import PortalCustomization from "@/components/PortalCustomization";

interface Establishment {
  id: string;
  name: string;
  slug: string;
  description: string;
  isActive: boolean;
  stats?: {
    users: number;
    courses: number;
    themes: number;
  };
}

interface User {
  id: string;
  email: string;
  username: string;
  firstName: string;
  lastName: string;
  role: string;
  establishmentId: string;
  establishmentName?: string;
  isActive: boolean;
}

export default function SuperAdminPage() {
  const { user, isAuthenticated } = useAuth();
  const [showCreateEstablishment, setShowCreateEstablishment] = useState(false);
  const [showCreateAdmin, setShowCreateAdmin] = useState(false);

  // Vérifier les permissions
  const { data: permissions } = useQuery<any>({
    queryKey: ['/api/auth/permissions'],
    enabled: isAuthenticated,
  });

  const { data: establishments = [] } = useQuery<Establishment[]>({
    queryKey: ['/api/super-admin/establishments'],
    enabled: permissions?.canManageAllEstablishments === true,
  });

  const { data: allUsers = [] } = useQuery<User[]>({
    queryKey: ['/api/super-admin/users'],
    enabled: permissions?.canManageAllEstablishments === true,
  });

  // Rediriger si pas les bonnes permissions
  if (!isAuthenticated || permissions?.canManageAllEstablishments !== true) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
        <Card className="w-full max-w-md">
          <CardHeader>
            <CardTitle className="text-center text-red-600">Accès Refusé</CardTitle>
          </CardHeader>
          <CardContent className="text-center">
            <p className="mb-4">Vous n'avez pas les permissions pour accéder à cette page.</p>
            <Link to="/dashboard">
              <Button>Retour au Dashboard</Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    );
  }

  const getRoleBadgeColor = (role: string) => {
    switch (role) {
      case 'super_admin': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100';
      case 'admin': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100';
      case 'manager': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100';
      case 'formateur': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100';
      case 'apprenant': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100';
    }
  };

  const getRoleLabel = (role: string) => {
    switch (role) {
      case 'super_admin': return 'Super Admin';
      case 'admin': return 'Administrateur';
      case 'manager': return 'Manager';
      case 'formateur': return 'Formateur';
      case 'apprenant': return 'Apprenant';
      default: return role;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 dark:from-gray-900 dark:to-gray-800">
      {/* Header */}
      <header className="bg-white dark:bg-gray-800 shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center space-x-3">
              <Shield className="h-8 w-8 text-red-600" />
              <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                  Administration Plateforme
                </h1>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Bienvenue, {user?.firstName || ''} {user?.lastName || ''} (Super Admin)
                </p>
              </div>
            </div>
            <div className="flex items-center space-x-3">
              <Link to="/admin">
                <Button variant="outline" className="bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200">
                  <Shield className="h-4 w-4 mr-2" />
                  Super Administration
                </Button>
              </Link>
              <Link to="/dashboard">
                <Button variant="outline">Retour au Dashboard</Button>
              </Link>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Stats Overview */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card>
            <CardContent className="flex items-center p-6">
              <Building className="h-8 w-8 text-blue-600 mr-4" />
              <div>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{establishments.length}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">Établissements</p>
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="flex items-center p-6">
              <Users className="h-8 w-8 text-green-600 mr-4" />
              <div>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{allUsers.length}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">Utilisateurs Total</p>
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="flex items-center p-6">
              <Shield className="h-8 w-8 text-purple-600 mr-4" />
              <div>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  {allUsers.filter(u => u.role === 'admin').length}
                </p>
                <p className="text-sm text-gray-600 dark:text-gray-400">Administrateurs</p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Main Tabs */}
        <Tabs defaultValue="establishments" className="space-y-6">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="establishments">Établissements</TabsTrigger>
            <TabsTrigger value="users">Utilisateurs</TabsTrigger>
            <TabsTrigger value="portal">Portail</TabsTrigger>
          </TabsList>

          {/* Establishments Tab */}
          <TabsContent value="establishments" className="space-y-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="flex items-center space-x-2">
                  <Building className="h-5 w-5" />
                  <span>Gestion des Établissements</span>
                </CardTitle>
                <Button onClick={() => setShowCreateEstablishment(true)}>
                  <Plus className="h-4 w-4 mr-2" />
                  Créer Établissement
                </Button>
              </CardHeader>
              <CardContent>
                <div className="grid gap-4">
                  {establishments.map((establishment) => (
                    <div key={establishment.id} className="flex flex-col lg:flex-row lg:items-center justify-between p-4 border rounded-lg space-y-3 lg:space-y-0">
                      <div className="flex-1">
                        <h3 className="font-semibold text-gray-900 dark:text-white text-lg">
                          {establishment.name}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Slug: {establishment.slug}
                        </p>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                          {establishment.description}
                        </p>
                        {establishment.stats && (
                          <div className="flex flex-wrap gap-2 mt-2">
                            <Badge variant="outline" className="text-xs">
                              {establishment.stats.users} utilisateurs
                            </Badge>
                            <Badge variant="outline" className="text-xs">
                              {establishment.stats.courses} cours
                            </Badge>
                            <Badge variant="outline" className="text-xs">
                              {establishment.stats.themes} thèmes
                            </Badge>
                          </div>
                        )}
                      </div>
                      <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 lg:items-center">
                        <Badge variant={establishment.isActive ? "default" : "secondary"} className="w-fit">
                          {establishment.isActive ? "Actif" : "Inactif"}
                        </Badge>
                        <Button variant="outline" size="sm" className="w-full sm:w-auto">
                          Gérer
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Users Tab */}
          <TabsContent value="users" className="space-y-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="flex items-center space-x-2">
                  <Users className="h-5 w-5" />
                  <span>Gestion des Utilisateurs</span>
                </CardTitle>
                <Button onClick={() => setShowCreateAdmin(true)}>
                  <Plus className="h-4 w-4 mr-2" />
                  Créer Administrateur
                </Button>
              </CardHeader>
              <CardContent>
                <div className="grid gap-4">
                  {allUsers.map((user) => (
                    <div key={user.id} className="flex flex-col lg:flex-row lg:items-center justify-between p-4 border rounded-lg space-y-3 lg:space-y-0">
                      <div className="flex-1">
                        <h3 className="font-semibold text-gray-900 dark:text-white text-base">
                          {user.firstName} {user.lastName}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 break-all">
                          {user.email}
                        </p>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          @{user.username}
                        </p>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                          Établissement: {user.establishmentName || establishments.find(e => e.id === user.establishmentId)?.name || "N/A"}
                        </p>
                      </div>
                      <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 lg:items-center">
                        <Badge className={getRoleBadgeColor(user.role) + " w-fit"}>
                          {getRoleLabel(user.role)}
                        </Badge>
                        <Badge variant={user.isActive ? "default" : "secondary"} className="w-fit">
                          {user.isActive ? "Actif" : "Inactif"}
                        </Badge>
                        <Button variant="outline" size="sm" className="w-full sm:w-auto">
                          Modifier
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Portal Customization Tab */}
          <TabsContent value="portal" className="space-y-6">
            <PortalCustomization />
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}