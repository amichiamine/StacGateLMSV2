import { useParams } from "wouter";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAuth } from "@/hooks/useAuth";
import { GraduationCap, BookOpen, Users, Award, ArrowLeft } from "lucide-react";

export default function EstablishmentPage() {
  const { slug } = useParams<{ slug: string }>();
  const { isAuthenticated } = useAuth();

  // Récupérer les informations de l'établissement
  const { data: establishment, isLoading } = useQuery({
    queryKey: ['/api/establishments/slug', slug],
    enabled: !!slug,
  });

  // Récupérer le contenu personnalisé de la page d'accueil de cet établissement
  const { data: customContent } = useQuery({
    queryKey: ['/api/establishment-content', slug, 'home'],
    enabled: !!slug,
  });

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Chargement de l'établissement...</p>
        </div>
      </div>
    );
  }

  if (!establishment) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">Établissement non trouvé</h1>
          <p className="text-gray-600 dark:text-gray-400 mb-8">L'établissement demandé n'existe pas ou n'est plus actif.</p>
          <Button onClick={() => window.location.href = '/'} className="flex items-center gap-2">
            <ArrowLeft className="w-4 h-4" />
            Retour au portail
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
      {/* Header personnalisé par établissement */}
      <header className="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm shadow-sm border-b rounded-b-3xl">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              {establishment.logo ? (
                <img 
                  src={establishment.logo} 
                  alt={`Logo ${establishment.name}`}
                  className="w-10 h-10 rounded-lg object-cover"
                />
              ) : (
                <GraduationCap className="w-10 h-10 text-blue-600" />
              )}
              <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{establishment.name}</h1>
                <p className="text-sm text-gray-600 dark:text-gray-400">Établissement d'enseignement</p>
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => window.location.href = '/'}
                className="flex items-center gap-2 hover:bg-blue-50 hover:border-blue-300"
              >
                <ArrowLeft className="h-4 w-4" />
                Portail
              </Button>
              {isAuthenticated ? (
                <>
                  <Button 
                    variant="outline" 
                    size="sm"
                    onClick={() => window.location.href = '/dashboard'}
                    className="flex items-center gap-2 hover:bg-blue-50 hover:border-blue-300"
                  >
                    <BookOpen className="h-4 w-4" />
                    Dashboard
                  </Button>
                  <Button 
                    variant="destructive" 
                    size="sm"
                    onClick={() => window.location.href = '/api/auth/logout'}
                    className="bg-red-600 hover:bg-red-700"
                  >
                    Déconnexion
                  </Button>
                </>
              ) : (
                <Button 
                  onClick={() => window.location.href = '/login'}
                  className="bg-blue-600 hover:bg-blue-700 text-white"
                >
                  Se connecter
                </Button>
              )}
            </div>
          </div>
        </div>
      </header>

      {/* Contenu principal personnalisé */}
      <main className="container mx-auto px-4 py-12">
        {/* Hero Section personnalisé */}
        <div className="text-center mb-16">
          <h2 className="text-5xl font-bold text-gray-900 dark:text-white mb-6">
            {customContent?.heroTitle || `Bienvenue chez ${establishment.name}`}
          </h2>
          <p className="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
            {customContent?.heroDescription || establishment.description || "Découvrez notre offre de formation et rejoignez notre communauté d'apprenants."}
          </p>
          {!isAuthenticated && (
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Button 
                size="lg" 
                onClick={() => window.location.href = '/login'}
                className="bg-blue-600 hover:bg-blue-700 text-white shadow-lg hover:shadow-xl transition-shadow px-8 py-3"
              >
                Commencer maintenant
              </Button>
              <Button 
                variant="outline" 
                size="lg"
                className="border-blue-300 hover:bg-blue-50 px-8 py-3"
              >
                En savoir plus
              </Button>
            </div>
          )}
        </div>

        {/* Features spécifiques à l'établissement */}
        <div className="grid md:grid-cols-3 gap-8 mb-16">
          <Card className="border-0 shadow-lg hover:shadow-xl transition-shadow bg-white/80 backdrop-blur-sm">
            <CardHeader>
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                <BookOpen className="w-6 h-6 text-blue-600" />
              </div>
              <CardTitle>Formations de qualité</CardTitle>
              <CardDescription>
                Accédez à un catalogue de formations conçues par nos experts et adaptées à vos besoins.
              </CardDescription>
            </CardHeader>
          </Card>

          <Card className="border-0 shadow-lg hover:shadow-xl transition-shadow bg-white/80 backdrop-blur-sm">
            <CardHeader>
              <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                <Users className="w-6 h-6 text-green-600" />
              </div>
              <CardTitle>Communauté active</CardTitle>
              <CardDescription>
                Rejoignez une communauté d'apprenants motivés et échangez avec vos pairs.
              </CardDescription>
            </CardHeader>
          </Card>

          <Card className="border-0 shadow-lg hover:shadow-xl transition-shadow bg-white/80 backdrop-blur-sm">
            <CardHeader>
              <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                <Award className="w-6 h-6 text-purple-600" />
              </div>
              <CardTitle>Certifications reconnues</CardTitle>
              <CardDescription>
                Obtenez des certifications valorisantes pour votre parcours professionnel.
              </CardDescription>
            </CardHeader>
          </Card>
        </div>

        {/* Call to Action */}
        {!isAuthenticated && (
          <div className="text-center bg-white/80 backdrop-blur-sm rounded-3xl p-12 shadow-lg">
            <h3 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
              Prêt à commencer votre parcours ?
            </h3>
            <p className="text-lg text-gray-600 dark:text-gray-300 mb-8 max-w-2xl mx-auto">
              Rejoignez {establishment.name} et accédez à un univers de formation innovant.
            </p>
            <Button 
              size="lg" 
              onClick={() => window.location.href = '/login'}
              className="bg-blue-600 hover:bg-blue-700 text-white shadow-lg hover:shadow-xl transition-shadow px-12 py-4 text-lg"
            >
              S'inscrire maintenant
            </Button>
          </div>
        )}
      </main>
    </div>
  );
}