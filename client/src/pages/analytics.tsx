import { useAuth } from "@/hooks/useAuth";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { 
  TrendingUp, 
  Users, 
  BookOpen, 
  Award,
  BarChart3,
  Calendar,
  Search,
  Download,
  Activity
} from "lucide-react";
import { Link } from "wouter";

export default function Analytics() {
  const { user, isAuthenticated } = useAuth();

  // Get establishment analytics
  const { data: analytics, isLoading: analyticsLoading } = useQuery<any>({
    queryKey: ['/api/analytics/establishments', user?.establishmentId, 'analytics'],
    enabled: isAuthenticated && !!user?.establishmentId,
  });

  // Get dashboard stats
  const { data: dashboardStats, isLoading: statsLoading } = useQuery<any>({
    queryKey: ['/api/analytics/dashboard/stats'],
    enabled: isAuthenticated && !!user?.id,
  });

  // Get popular courses
  const { data: popularCourses = [], isLoading: coursesLoading } = useQuery<any[]>({
    queryKey: ['/api/analytics/establishments', user?.establishmentId, 'popular-courses'],
    enabled: isAuthenticated && !!user?.establishmentId,
  });

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Card className="w-full max-w-md">
          <CardContent className="p-6">
            <p className="text-center text-gray-600">Veuillez vous connecter pour accéder aux analytics.</p>
          </CardContent>
        </Card>
      </div>
    );
  }

  const isLoading = analyticsLoading || statsLoading || coursesLoading;

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="container mx-auto p-6">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Analytics & Insights</h1>
            <p className="text-gray-600 dark:text-gray-300 mt-2">Analysez les performances de votre établissement</p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" data-testid="button-export-analytics">
              <Download className="h-4 w-4 mr-2" />
              Exporter
            </Button>
            <Button data-testid="button-refresh-analytics">
              <Activity className="h-4 w-4 mr-2" />
              Actualiser
            </Button>
          </div>
        </div>

        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {[...Array(8)].map((_, i) => (
              <Card key={i} className="animate-pulse">
                <CardHeader className="pb-2">
                  <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                </CardHeader>
                <CardContent>
                  <div className="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/2 mb-2"></div>
                  <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                </CardContent>
              </Card>
            ))}
          </div>
        ) : (
          <>
            {/* Key Metrics Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <Card data-testid="card-total-users">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium">Total Utilisateurs</CardTitle>
                  <Users className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{analytics?.totalUsers || 0}</div>
                  <p className="text-xs text-muted-foreground">
                    +{analytics?.newUsersThisMonth || 0} ce mois
                  </p>
                </CardContent>
              </Card>

              <Card data-testid="card-active-courses">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium">Cours Actifs</CardTitle>
                  <BookOpen className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{analytics?.activeCourses || 0}</div>
                  <p className="text-xs text-muted-foreground">
                    {analytics?.courseCompletionRate || 0}% taux de complétion
                  </p>
                </CardContent>
              </Card>

              <Card data-testid="card-total-enrollments">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium">Inscriptions Totales</CardTitle>
                  <TrendingUp className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{analytics?.totalEnrollments || 0}</div>
                  <p className="text-xs text-muted-foreground">
                    +{analytics?.enrollmentsThisWeek || 0} cette semaine
                  </p>
                </CardContent>
              </Card>

              <Card data-testid="card-certificates-issued">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium">Certificats Délivrés</CardTitle>
                  <Award className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{analytics?.certificatesIssued || 0}</div>
                  <p className="text-xs text-muted-foreground">
                    Ce mois-ci
                  </p>
                </CardContent>
              </Card>
            </div>

            {/* Popular Courses Section */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
              <Card data-testid="card-popular-courses">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <BarChart3 className="h-5 w-5" />
                    Cours Populaires
                  </CardTitle>
                  <CardDescription>Les cours les plus suivis ce mois</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {popularCourses.slice(0, 5).map((course: any, index: number) => (
                      <div key={course.id || index} className="flex items-center justify-between" data-testid={`course-popular-${index}`}>
                        <div className="flex items-center space-x-3">
                          <div className="w-2 h-2 rounded-full bg-blue-500"></div>
                          <div>
                            <p className="text-sm font-medium">{course.title || 'Cours sans titre'}</p>
                            <p className="text-xs text-gray-500">{course.enrollmentCount || 0} inscrits</p>
                          </div>
                        </div>
                        <Badge variant="secondary" data-testid={`badge-completion-${index}`}>
                          {course.completionRate || 0}% completé
                        </Badge>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>

              {/* Recent Activity */}
              <Card data-testid="card-recent-activity">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Activity className="h-5 w-5" />
                    Activité Récente
                  </CardTitle>
                  <CardDescription>Actions récentes dans l'établissement</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {analytics?.recentActivity?.slice(0, 5).map((activity: any, index: number) => (
                      <div key={index} className="flex items-start space-x-3" data-testid={`activity-${index}`}>
                        <div className="w-2 h-2 rounded-full bg-green-500 mt-2"></div>
                        <div className="flex-1">
                          <p className="text-sm">{activity.description || 'Activité inconnue'}</p>
                          <p className="text-xs text-gray-500">
                            {activity.timestamp ? new Date(activity.timestamp).toLocaleDateString('fr-FR') : 'Date inconnue'}
                          </p>
                        </div>
                      </div>
                    )) || (
                      <p className="text-sm text-gray-500" data-testid="text-no-activity">Aucune activité récente</p>
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Action Buttons */}
            <div className="flex flex-wrap gap-4 mt-8">
              <Link href="/courses">
                <Button variant="outline" data-testid="button-view-courses">
                  <BookOpen className="h-4 w-4 mr-2" />
                  Voir tous les cours
                </Button>
              </Link>
              <Link href="/user-management">
                <Button variant="outline" data-testid="button-manage-users">
                  <Users className="h-4 w-4 mr-2" />
                  Gérer les utilisateurs
                </Button>
              </Link>
              <Link href="/archive-export">
                <Button variant="outline" data-testid="button-exports">
                  <Download className="h-4 w-4 mr-2" />
                  Exports & Archives
                </Button>
              </Link>
            </div>
          </>
        )}
      </div>
    </div>
  );
}