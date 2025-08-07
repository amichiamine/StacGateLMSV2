import { Link } from "wouter";
import { Button } from "@/components/ui/button";
import { Play, Info, Star, GraduationCap } from "lucide-react";

export default function HeroSection() {
  return (
    <section className="relative overflow-hidden">
      <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-secondary/5 to-accent/5"></div>
      <div className="absolute top-0 right-0 w-96 h-96 bg-gradient-to-bl from-primary/10 to-transparent rounded-full blur-3xl"></div>
      <div className="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-secondary/10 to-transparent rounded-full blur-3xl"></div>
      
      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <h1 className="text-5xl md:text-6xl font-bold text-neutral-900 leading-tight mb-6">
              Apprenez sans{" "}
              <span className="text-gradient-primary">
                limites
              </span>
            </h1>
            <p className="text-xl text-neutral-600 mb-8 leading-relaxed">
              Découvrez une nouvelle façon d'apprendre avec StacGateLMS. Des cours interactifs, 
              des parcours personnalisés et une communauté d'apprenants passionnés.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <Link href="/login">
                <Button 
                  size="lg" 
                  className="bg-primary text-white hover:bg-primary/90 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl"
                >
                  <Play className="mr-2 h-4 w-4" />
                  Commencer maintenant
                </Button>
              </Link>
              <Button 
                variant="outline" 
                size="lg"
                className="border-2 border-primary text-primary hover:bg-primary hover:text-white transition-all duration-300"
              >
                <Info className="mr-2 h-4 w-4" />
                En savoir plus
              </Button>
            </div>
          </div>
          <div className="relative">
            <div className="relative bg-white rounded-2xl shadow-2xl p-8 transform rotate-2">
              <div className="bg-gradient-primary rounded-xl p-6 mb-6">
                <GraduationCap className="text-white text-4xl mb-4 w-10 h-10" />
                <h3 className="text-white text-xl font-semibold">Cours Interactifs</h3>
              </div>
              <div className="space-y-4">
                <div className="flex items-center space-x-3">
                  <div className="w-4 h-4 bg-accent rounded-full"></div>
                  <span className="text-neutral-600">Vidéos HD</span>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="w-4 h-4 bg-secondary rounded-full"></div>
                  <span className="text-neutral-600">Exercices pratiques</span>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="w-4 h-4 bg-primary rounded-full"></div>
                  <span className="text-neutral-600">Certificats</span>
                </div>
              </div>
            </div>
            <div className="absolute -top-4 -right-4 bg-accent text-white rounded-full w-16 h-16 flex items-center justify-center shadow-lg">
              <Star className="text-xl w-6 h-6" />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
