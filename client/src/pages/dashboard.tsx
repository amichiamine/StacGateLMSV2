import { useAuth } from "@/hooks/useAuth";
import { useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { 
  BookOpen, 
  Users, 
  Calendar, 
  Award,
  TrendingUp,
  Clock,
  MessageSquare,
  Settings,
  Shield,
  FileText,
  Trophy,
  Archive,
  RefreshCw
} from "lucide-react";
import { Link } from "wouter";
import { useToast } from "@/hooks/use-toast";
import Navigation from "@/components/navigation";

export default function Dashboard() {
  const { user, isLoading, isAuthenticated } = useAuth();
  const { toast } = useToast();

  // Récupérer les vraies données pour les statistiques avec types corrects
  const { data: courses = [] } = useQuery<any[]>({
    queryKey: ['/api/courses'],
    enabled: isAuthenticated
  });

  const { data: users = [] } = useQuery<any[]>({
    queryKey: ['/api/users'],
    enabled: isAuthenticated && (user?.role === 'admin' || user?.role === 'super_admin' || user?.role === 'manager')
  });

  // Redirect to login if not authenticated
  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      toast({
        title: "Accès refusé",
        description: "Vous devez être connecté pour accéder au tableau de bord.",
        variant: "destructive",
      });
      setTimeout(() => {
        window.location.href = "/login";
      }, 1500);
      return;
    }
  }, [isAuthenticated, isLoading, toast]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (!isAuthenticated || !user) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Card className="w-full max-w-md">
          <CardContent className="p-6">
            <p className="text-center text-gray-600">Redirection vers la connexion...</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
      {/* Navigation Glassmorphism */}
      <Navigation />
      
      {/* Dashboard Content avec padding pour navigation fixe */}
      <div className="pt-20">
        {/* Header Dashboard avec glassmorphism */}
        <header className="glassmorphism mx-4 mt-4 rounded-2xl p-6 shadow-2xl">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div className="flex items-center space-x-3 sm:space-x-4">
              <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center">
                <BookOpen className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
              </div>
              <div>
                <h1 className="text-xl sm:text-2xl lg:text-3xl font-bold text-blue-900">
                  Tableau de bord
                </h1>
                <p className="text-xs sm:text-sm text-blue-700 hidden sm:block">
                  Bienvenue, {user?.firstName} {user?.lastName} • {Array.isArray(courses) ? courses.length : 0} cours disponibles
                </p>
              </div>
            </div>
            <div className="flex items-center space-x-2 sm:space-x-4 flex-wrap">
              <Button 
                variant="outline" 
                size="sm"
                onClick={async () => {
                  // Force logout to refresh user data
                  try {
                    await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' });
                    window.location.href = '/login';
                  } catch (error) {
                    window.location.href = '/login';
                  }
                }}
                className="flex items-center gap-2 hover:bg-red-50/70 hover:border-red-300 text-blue-900 backdrop-blur-sm"
              >
                <Settings className="h-4 w-4" />
                <span className="hidden sm:inline">Refresh Session</span>
              </Button>
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => window.location.href = '/'}
                className="flex items-center gap-2 hover:bg-blue-50/70 hover:border-blue-300 text-blue-900 backdrop-blur-sm"
              >
                <BookOpen className="h-4 w-4" />
                <span className="hidden sm:inline">Accueil</span>
              </Button>
              {(user?.role === 'super_admin' || user?.role === 'admin' || user?.role === 'manager') && (
                <Button 
                  variant="outline" 
                  size="sm"
                  onClick={() => window.location.href = user?.role === 'super_admin' ? '/super-admin' : '/admin'}
                  className="flex items-center gap-2 hover:bg-blue-50/70 hover:border-blue-300 text-blue-900 backdrop-blur-sm"
                >
                  <Settings className="h-4 w-4" />
                  <span className="hidden lg:inline">{user?.role === 'super_admin' ? 'Super Administration' : 'Administration'}</span>
                  <span className="lg:hidden">Admin</span>
                </Button>
              )}
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => window.location.href = '/courses'}
                className="flex items-center gap-2 hover:bg-green-50/70 hover:border-green-300 text-blue-900 backdrop-blur-sm"
              >
                <BookOpen className="h-4 w-4" />
                <span className="hidden sm:inline">Cours</span>
              </Button>
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => window.location.href = '/assessments'}
                className="flex items-center gap-2 hover:bg-purple-50 hover:border-purple-300"
              >
                <Trophy className="h-4 w-4" />
                Évaluations
              </Button>
              <div className="flex items-center space-x-3">
                <Avatar>
                  <AvatarImage src={user?.profileImageUrl || undefined} />
                  <AvatarFallback>
                    {user?.firstName?.charAt(0) || user?.email?.charAt(0) || 'U'}
                  </AvatarFallback>
                </Avatar>
                <div className="text-right hidden sm:block">
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {user?.firstName && user?.lastName 
                      ? `${user.firstName} ${user.lastName}` 
                      : user?.email}
                  </p>
                  <Badge variant="secondary" className="text-xs">
                    {user?.role || 'Apprenant'}
                  </Badge>
                </div>
              </div>
              <Button 
                variant="outline" 
                size="sm"
                onClick={async () => {
                  try {
                    await fetch('/api/auth/logout', { method: 'POST' });
                    window.location.href = '/';
                  } catch (error) {
                    console.error('Logout error:', error);
                    window.location.href = '/';
                  }
                }}
              >
                Déconnexion
              </Button>
            </div>
          </div>
        </header>
      </div>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        {/* Welcome Section */}
        <div className="mb-6 sm:mb-8">
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Bienvenue, {user?.firstName || user?.email?.split('@')[0] || 'Utilisateur'} !
          </h2>
          <p className="text-gray-600 dark:text-gray-300">
            Voici un aperçu de votre activité d'apprentissage dans votre établissement.
          </p>
        </div>

        {/* Stats Grid avec vraies données */}
        <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
          <Card className="border-0 shadow-lg hover:shadow-xl transition-shadow bg-white/80 backdrop-blur-sm cursor-pointer"
                onClick={() => window.location.href = '/courses'}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Cours disponibles</CardTitle>
              <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <BookOpen className="h-4 w-4 text-blue-600" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-blue-600">{Array.isArray(courses) ? courses.length : 0}</div>
              <p className="text-xs text-muted-foreground">
                Formations actives
              </p>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-lg hover:shadow-xl transition-shadow bg-white/80 backdrop-blur-sm">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Heures totales</CardTitle>
              <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <Clock className="h-4 w-4 text-green-600" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-600">
                {Array.isArray(courses) ? courses.reduce((total: number, course: any) => total + (course.duration || 0), 0) : 0}h
              </div>
              <p className="text-xs text-muted-foreground">
                Contenu disponible
              </p>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-lg hover:shadow-xl transition-shadow bg-white/80 backdrop-blur-sm">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Catégories</CardTitle>
              <div className="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <Award className="h-4 w-4 text-purple-600" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-purple-600">
                {Array.isArray(courses) ? new Set(courses.map((course: any) => course.category)).size : 0}
              </div>
              <p className="text-xs text-muted-foreground">
                Domaines d'expertise
              </p>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-lg hover:shadow-xl transition-shadow bg-white/80 backdrop-blur-sm">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Note moyenne</CardTitle>
              <div className="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                <TrendingUp className="h-4 w-4 text-orange-600" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-orange-600">
                {Array.isArray(courses) && courses.length > 0 
                  ? (courses.reduce((total: number, course: any) => total + parseFloat(course.rating || '0'), 0) / courses.length).toFixed(1)
                  : '0.0'}
              </div>
              <p className="text-xs text-muted-foreground">
                Qualité des cours
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Recent Activity avec vraies données */}
        <div className="grid lg:grid-cols-2 gap-6 sm:gap-8">
          <Card className="border-0 shadow-lg bg-white/80 backdrop-blur-sm">
            <CardHeader>
              <CardTitle className="flex items-center">
                <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                  <BookOpen className="w-4 h-4 text-blue-600" />
                </div>
                Cours en cours
              </CardTitle>
              <CardDescription>
                Vos formations actuelles
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {(Array.isArray(courses) ? courses.slice(0, 3) : []).map((course: any, index: number) => (
                <div key={course.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                     onClick={() => window.location.href = '/courses'}>
                  <div className="flex-1">
                    <h4 className="font-medium">{course.title}</h4>
                    <p className="text-sm text-gray-600">{course.category}</p>
                    <div className="flex items-center mt-1 space-x-2">
                      <Badge variant="outline" className="text-xs">
                        {course.level === 'debutant' ? 'Débutant' : 
                         course.level === 'intermediaire' ? 'Intermédiaire' : 
                         course.level === 'avance' ? 'Avancé' : course.level}
                      </Badge>
                      <span className="text-xs text-gray-500">{course.duration}h</span>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-sm font-medium text-gray-600">
                      {course.isFree ? 'Gratuit' : `${course.price}€`}
                    </div>
                    <div className="flex items-center space-x-1 mt-1">
                      <span className="text-xs text-yellow-600">★</span>
                      <span className="text-xs text-gray-500">{course.rating}</span>
                    </div>
                  </div>
                </div>
              ))}
              {(!Array.isArray(courses) || courses.length === 0) && (
                <div className="text-center py-8 text-gray-500">
                  <BookOpen className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                  <p>Aucun cours disponible</p>
                </div>
              )}
              {Array.isArray(courses) && courses.length > 3 && (
                <Button 
                  variant="outline" 
                  className="w-full mt-4"
                  onClick={() => window.location.href = '/courses'}
                >
                  Voir tous les cours ({courses.length})
                </Button>
              )}
            </CardContent>
          </Card>

          <Card className="border-0 shadow-lg bg-white/80 backdrop-blur-sm">
            <CardHeader>
              <CardTitle className="flex items-center">
                <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                  <Calendar className="w-4 h-4 text-green-600" />
                </div>
                Prochaines sessions
              </CardTitle>
              <CardDescription>
                Vos rendez-vous de formation
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center space-x-4 p-4 border rounded-lg">
                <div className="bg-blue-100 p-2 rounded-lg">
                  <Calendar className="h-4 w-4 text-blue-600" />
                </div>
                <div className="flex-1">
                  <h4 className="font-medium">Session JavaScript Live</h4>
                  <p className="text-sm text-gray-600">Aujourd'hui, 14h00</p>
                </div>
                <Badge>Live</Badge>
              </div>

              <div className="flex items-center space-x-4 p-4 border rounded-lg">
                <div className="bg-green-100 p-2 rounded-lg">
                  <Users className="h-4 w-4 text-green-600" />
                </div>
                <div className="flex-1">
                  <h4 className="font-medium">Atelier UX collaboratif</h4>
                  <p className="text-sm text-gray-600">Demain, 10h00</p>
                </div>
                <Badge variant="secondary">Atelier</Badge>
              </div>

              <div className="flex items-center space-x-4 p-4 border rounded-lg">
                <div className="bg-purple-100 p-2 rounded-lg">
                  <Award className="h-4 w-4 text-purple-600" />
                </div>
                <div className="flex-1">
                  <h4 className="font-medium">Examen final Agile</h4>
                  <p className="text-sm text-gray-600">Vendredi, 15h00</p>
                </div>
                <Badge variant="outline">Examen</Badge>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card className="mt-8 border-0 shadow-lg bg-white/80 backdrop-blur-sm">
          <CardHeader>
            <CardTitle>Actions rapides</CardTitle>
            <CardDescription>
              Accédez rapidement aux fonctionnalités principales
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex flex-wrap gap-4">
              <Button 
                variant="outline" 
                className="h-20 flex-col flex-1 min-w-[120px]"
                onClick={() => {
                  console.log('Navigating to courses...');
                  window.location.href = '/courses';
                }}
                data-testid="button-courses"
              >
                <BookOpen className="h-6 w-6 mb-2" />
                Parcourir les cours
              </Button>
              <Button 
                variant="outline" 
                className="h-20 flex-col flex-1 min-w-[120px]"
                onClick={() => {
                  console.log('Navigating to assessments...');
                  window.location.href = '/assessments';
                }}
                data-testid="button-assessments"
              >
                <FileText className="h-6 w-6 mb-2" />
                Évaluations
              </Button>
              <Button 
                variant="outline" 
                className="h-20 flex-col flex-1 min-w-[120px]"
                onClick={() => {
                  console.log('Navigating to manual...');
                  window.location.href = '/manual';
                }}
                data-testid="button-manual"
              >
                <FileText className="h-6 w-6 mb-2" />
                Manuel d'utilisation
              </Button>
              
              <Button 
                variant="outline" 
                className="h-20 flex-col flex-1 min-w-[120px]"
                onClick={() => {
                  console.log('Navigating to archive...');
                  window.location.href = '/archive';
                }}
                data-testid="button-archive"
              >
                <Archive className="h-6 w-6 mb-2" />
                Archivage
              </Button>
              
              {/* Boutons d'administration selon les rôles */}
              {(() => {
                console.log('DEBUG - User role:', user?.role, 'Expected: super_admin', 'Match:', user?.role === 'super_admin');
                return null;
              })()}
              {user?.role === 'super_admin' && (
                <Button 
                  variant="outline" 
                  className="h-20 flex-col flex-1 min-w-[120px] border-red-200 hover:bg-red-50"
                  onClick={() => {
                    console.log('Navigating to super-admin...');
                    window.location.href = '/super-admin';
                  }}
                  data-testid="button-super-admin"
                >
                  <Shield className="h-6 w-6 mb-2 text-red-600" />
                  Super Administration
                </Button>
              )}
              
              {(user?.role === 'admin' || user?.role === 'manager') && (
                <>
                  <Button 
                    variant="outline" 
                    className="h-20 flex-col flex-1 min-w-[120px] border-blue-200 hover:bg-blue-50"
                    onClick={() => {
                      console.log('Navigating to admin...');
                      window.location.href = '/admin';
                    }}
                    data-testid="button-admin"
                  >
                    <Settings className="h-6 w-6 mb-2 text-blue-600" />
                    Administration
                  </Button>
                  
                  <Button 
                    variant="outline" 
                    className="h-20 flex-col flex-1 min-w-[120px] border-purple-200 hover:bg-purple-50"
                    onClick={() => {
                      console.log('Navigating to system-updates...');
                      window.location.href = '/system-updates';
                    }}
                    data-testid="button-system-updates"
                  >
                    <RefreshCw className="h-6 w-6 mb-2 text-purple-600" />
                    Mises à jour
                  </Button>
                </>
              )}
              
              {/* Bouton paramètres pour tous les autres cas */}
              {!['admin', 'super_admin', 'manager'].includes(user?.role || '') && (
                <Button 
                  variant="outline" 
                  className="h-20 flex-col flex-1 min-w-[120px]"
                  onClick={() => {
                    console.log('Settings clicked');
                    toast({
                      title: "Paramètres",
                      description: "Fonctionnalité en cours de développement.",
                    });
                  }}
                  data-testid="button-settings"
                >
                  <Settings className="h-6 w-6 mb-2" />
                  Paramètres
                </Button>
              )}
            </div>
          </CardContent>
        </Card>
      </main>
    </div>
  );
}