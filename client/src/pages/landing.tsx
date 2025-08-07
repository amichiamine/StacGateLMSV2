import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { GraduationCap, Users, BookOpen, Award, ChevronRight } from "lucide-react";

export default function Landing() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
      {/* Header */}
      <header className="container mx-auto px-4 py-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <GraduationCap className="w-8 h-8 text-blue-600" />
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">StacGateLMS</h1>
          </div>
          <Button 
            onClick={() => window.location.href = '/login'}
            className="bg-blue-600 hover:bg-blue-700 text-white"
          >
            Se connecter
          </Button>
        </div>
      </header>

      {/* Hero Section */}
      <main className="container mx-auto px-4 py-12">
        <div className="text-center mb-16">
          <h2 className="text-5xl font-bold text-gray-900 dark:text-white mb-6">
            Plateforme d'apprentissage
            <span className="text-blue-600 block">nouvelle génération</span>
          </h2>
          <p className="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
            Découvrez StacGateLMS, la solution complète pour la formation en ligne.
            Créez, gérez et suivez vos formations avec simplicité et efficacité.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button 
              size="lg"
              onClick={() => window.location.href = '/login'}
              className="bg-blue-600 hover:bg-blue-700 text-white"
            >
              Commencer maintenant
              <ChevronRight className="ml-2 w-4 h-4" />
            </Button>
            <Button size="lg" variant="outline">
              Découvrir les fonctionnalités
            </Button>
          </div>
        </div>

        {/* Features Grid */}
        <div className="grid md:grid-cols-3 gap-8 mb-16">
          <Card className="border-0 shadow-lg">
            <CardHeader>
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                <Users className="w-6 h-6 text-blue-600" />
              </div>
              <CardTitle>Multi-établissements</CardTitle>
              <CardDescription>
                Gérez plusieurs écoles ou académies avec des écosystèmes séparés et des personnalisations uniques.
              </CardDescription>
            </CardHeader>
          </Card>

          <Card className="border-0 shadow-lg">
            <CardHeader>
              <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                <BookOpen className="w-6 h-6 text-green-600" />
              </div>
              <CardTitle>Formation hybride</CardTitle>
              <CardDescription>
                Proposez des formations synchrones avec visioconférence et asynchrones avec contenus interactifs.
              </CardDescription>
            </CardHeader>
          </Card>

          <Card className="border-0 shadow-lg">
            <CardHeader>
              <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                <Award className="w-6 h-6 text-purple-600" />
              </div>
              <CardTitle>Personnalisation totale</CardTitle>
              <CardDescription>
                Interface WYSIWYG pour personnaliser tous les éléments : textes, couleurs, logos, menus.
              </CardDescription>
            </CardHeader>
          </Card>
        </div>

        {/* Stats */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 mb-16">
          <div className="grid md:grid-cols-4 gap-8 text-center">
            <div>
              <div className="text-3xl font-bold text-blue-600 mb-2">500+</div>
              <div className="text-gray-600 dark:text-gray-300">Établissements</div>
            </div>
            <div>
              <div className="text-3xl font-bold text-green-600 mb-2">10k+</div>
              <div className="text-gray-600 dark:text-gray-300">Apprenants actifs</div>
            </div>
            <div>
              <div className="text-3xl font-bold text-purple-600 mb-2">95%</div>
              <div className="text-gray-600 dark:text-gray-300">Taux de satisfaction</div>
            </div>
            <div>
              <div className="text-3xl font-bold text-orange-600 mb-2">24/7</div>
              <div className="text-gray-600 dark:text-gray-300">Support disponible</div>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="text-center">
          <Card className="border-0 shadow-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white">
            <CardContent className="p-12">
              <h3 className="text-3xl font-bold mb-4">
                Prêt à transformer votre approche de la formation ?
              </h3>
              <p className="text-xl mb-8 opacity-90">
                Rejoignez des milliers d'établissements qui font confiance à StacGateLMS.
              </p>
              <Button 
                size="lg" 
                variant="secondary"
                onClick={() => window.location.href = '/login'}
                className="bg-white text-blue-600 hover:bg-gray-100"
              >
                Démarrer gratuitement
                <ChevronRight className="ml-2 w-4 h-4" />
              </Button>
            </CardContent>
          </Card>
        </div>
      </main>

      {/* Footer */}
      <footer className="container mx-auto px-4 py-8 mt-16 border-t border-gray-200 dark:border-gray-700">
        <div className="text-center text-gray-600 dark:text-gray-400">
          <p>&copy; 2024 StacGateLMS. Tous droits réservés.</p>
          <p className="mt-2">Plateforme d'apprentissage nouvelle génération</p>
        </div>
      </footer>
    </div>
  );
}