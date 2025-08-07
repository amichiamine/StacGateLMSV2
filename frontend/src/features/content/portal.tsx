import React, { useState, useRef, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { 
  Search, 
  Building, 
  Users, 
  BookOpen, 
  Star, 
  ArrowRight, 
  Menu, 
  X,
  GraduationCap,
  Award,
  Globe
} from "lucide-react";
import { Link } from "wouter";

interface Establishment {
  id: string;
  name: string;
  slug: string;
  description: string;
  logo?: string;
  domain?: string;
  isActive: boolean;
  stats?: {
    users: number;
    courses: number;
    themes: number;
  };
}

export default function PortalPage() {
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const searchInputRef = useRef<HTMLInputElement>(null);

  // Récupération des établissements avec gestion d'erreur robuste
  const { data: establishments = [], isLoading, error } = useQuery<Establishment[]>({
    queryKey: ['/api/establishments'],
    retry: 1,
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
  });

  // Log des erreurs si présentes
  React.useEffect(() => {
    if (error) {
      console.warn('Erreur récupération établissements:', error);
    }
  }, [error]);

  // Gestionnaire d'erreur global pour unhandledrejection
  useEffect(() => {
    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
      console.warn('Promise rejection interceptée:', event.reason);
      event.preventDefault(); // Empêche l'erreur de remonter dans la console
    };

    const handleError = (event: ErrorEvent) => {
      console.warn('Erreur interceptée:', event.error);
      event.preventDefault();
    };

    window.addEventListener('unhandledrejection', handleUnhandledRejection);
    window.addEventListener('error', handleError);
    
    return () => {
      window.removeEventListener('unhandledrejection', handleUnhandledRejection);
      window.removeEventListener('error', handleError);
    };
  }, []);

  // Fermer le menu mobile avec gestion d'erreur robuste
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      try {
        if (isMobileMenuOpen) {
          setIsMobileMenuOpen(false);
        }
      } catch (error) {
        console.warn('Erreur lors de la fermeture du menu:', error);
      }
    };

    const handleEscape = (event: KeyboardEvent) => {
      try {
        if (event.key === 'Escape' && isMobileMenuOpen) {
          setIsMobileMenuOpen(false);
        }
      } catch (error) {
        console.warn('Erreur gestion touche Escape:', error);
      }
    };

    if (isMobileMenuOpen) {
      document.addEventListener('click', handleClickOutside);
      document.addEventListener('keydown', handleEscape);
    }

    return () => {
      document.removeEventListener('click', handleClickOutside);
      document.removeEventListener('keydown', handleEscape);
    };
  }, [isMobileMenuOpen]);

  // Raccourci clavier pour la recherche avec gestion d'erreur
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      try {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
          e.preventDefault();
          searchInputRef.current?.focus();
        }
      } catch (error) {
        console.warn('Erreur raccourci clavier:', error);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, []);

  // Filtrage des établissements avec typage sécurisé
  const filteredEstablishments = Array.isArray(establishments) ? establishments.filter((establishment: Establishment) => {
    const matchesSearch = establishment.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         establishment.description.toLowerCase().includes(searchTerm.toLowerCase());
    
    if (selectedCategory === "all") return matchesSearch;
    
    const categoryMap: Record<string, string[]> = {
      "university": ["université", "university"],
      "school": ["école", "school", "lycée"],
      "professional": ["professionnel", "formation", "pro"]
    };
    
    const categoryKeywords = categoryMap[selectedCategory] || [];
    const matchesCategory = categoryKeywords.some(keyword => 
      establishment.name.toLowerCase().includes(keyword) ||
      establishment.description.toLowerCase().includes(keyword)
    );
    
    return matchesSearch && matchesCategory;
  }) : [];

  // Catégories
  const categories = [
    { id: "all", label: "Tous", icon: Globe },
    { id: "university", label: "Universités", icon: GraduationCap },
    { id: "school", label: "Écoles", icon: BookOpen },
    { id: "professional", label: "Formation Pro", icon: Award }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
      {/* Header responsive */}
      <header className="sticky top-0 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur-lg border-b border-gray-200 dark:border-gray-700">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16 sm:h-20 relative">
            {/* Logo */}
            <div className="flex items-center space-x-3">
              <div className="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                <GraduationCap className="w-4 h-4 sm:w-5 sm:h-5 text-white" />
              </div>
              <div>
                <h1 className="text-xl sm:text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                  StacGateLMS
                </h1>
                <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
                  Portail Multi-Établissements
                </p>
              </div>
            </div>

            {/* Navigation desktop FORCÉE VISIBLE */}
            <nav className="hidden md:flex items-center space-x-4 lg:space-x-8" style={{ display: 'flex' }}>
              <Link href="/" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-sm lg:text-base">
                Accueil
              </Link>
              <Link href="/about" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-sm lg:text-base">
                À propos
              </Link>
              <Link href="/contact" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-sm lg:text-base">
                Contact
              </Link>
              <div data-testid="button-login-desktop" style={{ display: 'block', visibility: 'visible' }}>
                <Link href="/login">
                  <Button 
                    variant="outline" 
                    size="sm" 
                    className="ml-4 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                    style={{ display: 'inline-flex', visibility: 'visible', opacity: '1' }}
                  >
                    <span className="hidden lg:inline">🔐 Connexion</span>
                    <span className="lg:hidden">Login</span>
                  </Button>
                </Link>
              </div>
            </nav>

            {/* Menu mobile toggle - visible sur petits écrans et tablettes */}
            <Button
              variant="ghost"
              size="sm"
              className="md:hidden z-50 relative"
              onClick={(e) => {
                try {
                  e.preventDefault();
                  e.stopPropagation();
                  setIsMobileMenuOpen(prev => !prev);
                } catch (error) {
                  console.warn('Erreur toggle menu:', error);
                }
              }}
              data-testid="button-mobile-menu"
              aria-label="Menu mobile"
              aria-expanded={isMobileMenuOpen}
            >
              {isMobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </Button>
          </div>

          {/* Menu mobile - responsive pour mobile et tablette */}
          {isMobileMenuOpen && (
            <div className="md:hidden py-4 border-t border-gray-200 dark:border-gray-700 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm">
              <nav className="flex flex-col space-y-3 px-2">
                <Link href="/" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors py-2 px-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                  🏠 Accueil
                </Link>
                <Link href="/about" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors py-2 px-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                  ℹ️ À propos
                </Link>
                <Link href="/contact" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors py-2 px-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                  📞 Contact
                </Link>
                <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                  <div data-testid="button-login-mobile" style={{ display: 'block', visibility: 'visible' }}>
                    <Link href="/login" className="block">
                      <Button 
                        variant="outline" 
                        size="sm" 
                        className="w-full justify-center bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600"
                        style={{ display: 'flex', visibility: 'visible', opacity: '1' }}
                      >
                        🔐 Connexion
                      </Button>
                    </Link>
                  </div>
                </div>
              </nav>
            </div>
          )}
        </div>
      </header>

      {/* Section Hero avec statistiques */}
      <section className="py-12 sm:py-16 lg:py-24">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto mb-12 sm:mb-16">
            <h2 className="text-3xl sm:text-4xl lg:text-6xl font-bold mb-4 sm:mb-6" style={{ color: '#1e3a8a' }}>
              Bienvenue sur
              <span className="block bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                StacGateLMS
              </span>
            </h2>
            <p className="text-lg sm:text-xl lg:text-2xl mb-8 sm:mb-12 leading-relaxed" style={{ color: '#475569' }}>
              Découvrez notre écosystème éducatif multi-établissements.
              Choisissez votre établissement pour accéder à une expérience d'apprentissage personnalisée.
            </p>

            {/* Barre de recherche responsive */}
            <div className="relative max-w-2xl mx-auto mb-8 sm:mb-12">
              <div className="relative">
                <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
                <Input
                  ref={searchInputRef}
                  type="text"
                  placeholder="Rechercher un établissement... (Ctrl+K)"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-12 pr-4 py-3 sm:py-4 text-base sm:text-lg h-12 sm:h-14 rounded-2xl border-2 border-gray-200 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 transition-colors shadow-lg"
                  data-testid="input-search"
                />
              </div>
            </div>

            {/* Statistiques globales responsive */}
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6 max-w-3xl mx-auto">
              <div className="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                <div className="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3">
                  <div className="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
                    <Building className="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" />
                  </div>
                  <div className="text-center sm:text-left">
                    <p className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white" style={{ color: '#1e3a8a' }}>
                      {Array.isArray(establishments) ? establishments.length : 0}
                    </p>
                    <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Établissements</p>
                  </div>
                </div>
              </div>
              
              <div className="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                <div className="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3">
                  <div className="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center">
                    <Users className="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" />
                  </div>
                  <div className="text-center sm:text-left">
                    <p className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white" style={{ color: '#1e3a8a' }}>
                      {Array.isArray(establishments) ? establishments.reduce((acc: number, est: Establishment) => acc + (est.stats?.users || 0), 0) : 0}
                    </p>
                    <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Étudiants</p>
                  </div>
                </div>
              </div>
              
              <div className="col-span-2 sm:col-span-1 bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                <div className="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3">
                  <div className="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center">
                    <BookOpen className="w-5 h-5 sm:w-6 sm:h-6 text-purple-600 dark:text-purple-400" />
                  </div>
                  <div className="text-center sm:text-left">
                    <p className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white" style={{ color: '#1e3a8a' }}>
                      {Array.isArray(establishments) ? establishments.reduce((acc: number, est: Establishment) => acc + (est.stats?.courses || 0), 0) : 0}
                    </p>
                    <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Cours</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Section des établissements */}
      <section className="py-8 sm:py-12 lg:py-16">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          {/* Filtres par catégorie responsive */}
          <div className="mb-8 sm:mb-12">
            <div className="flex flex-wrap justify-center gap-2 sm:gap-4">
              {categories.map((category) => {
                const IconComponent = category.icon;
                return (
                  <Button
                    key={category.id}
                    variant={selectedCategory === category.id ? "default" : "outline"}
                    size="sm"
                    onClick={() => setSelectedCategory(category.id)}
                    className="flex items-center space-x-2 text-sm sm:text-base px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl"
                    data-testid={`button-category-${category.id}`}
                  >
                    <IconComponent className="w-4 h-4" />
                    <span className="hidden sm:inline">{category.label}</span>
                    <span className="sm:hidden">{category.label.charAt(0)}</span>
                  </Button>
                );
              })}
            </div>
          </div>

          {/* Grille d'établissements responsive */}
          {isLoading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
              {[...Array(6)].map((_, i) => (
                <Card key={i} className="animate-pulse">
                  <CardHeader>
                    <div className="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-2">
                      <div className="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                      <div className="w-2/3 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : filteredEstablishments.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
              {filteredEstablishments.map((establishment: Establishment) => (
                <Card 
                  key={establishment.id} 
                  className="group hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-2 hover:border-blue-300 dark:hover:border-blue-600"
                  data-testid={`card-establishment-${establishment.id}`}
                >
                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between">
                      <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-sm sm:text-base">
                          {establishment.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                          <CardTitle className="text-base sm:text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            {establishment.name}
                          </CardTitle>
                          {establishment.isActive && (
                            <Badge variant="secondary" className="mt-1 text-xs">
                              <div className="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                              Actif
                            </Badge>
                          )}
                        </div>
                      </div>
                    </div>
                  </CardHeader>
                  
                  <CardContent className="pt-0">
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                      {establishment.description}
                    </p>
                    
                    {establishment.stats && (
                      <div className="grid grid-cols-2 gap-2 mb-4">
                        <div className="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                          <p className="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                            {establishment.stats.users}
                          </p>
                          <p className="text-xs text-gray-600 dark:text-gray-400">Étudiants</p>
                        </div>
                        <div className="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                          <p className="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                            {establishment.stats.courses}
                          </p>
                          <p className="text-xs text-gray-600 dark:text-gray-400">Cours</p>
                        </div>
                      </div>
                    )}
                    
                    <Link href={`/${establishment.slug}/dashboard`} className="block">
                      <Button 
                        className="w-full group-hover:bg-blue-600 transition-colors" 
                        size="sm"
                        data-testid={`button-access-${establishment.slug}`}
                      >
                        Accéder
                        <ArrowRight className="w-4 h-4 ml-2" />
                      </Button>
                    </Link>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <Search className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                Aucun établissement trouvé
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                Essayez de modifier vos critères de recherche ou filtres.
              </p>
            </div>
          )}
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 py-8 sm:py-12">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <div className="flex items-center justify-center space-x-3 mb-4">
              <div className="w-8 h-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                <GraduationCap className="w-4 h-4 text-white" />
              </div>
              <h3 className="text-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                StacGateLMS
              </h3>
            </div>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              Plateforme d'apprentissage multi-établissements
            </p>
            <p className="text-sm text-gray-500 dark:text-gray-500">
              © 2025 StacGateLMS. Tous droits réservés.
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}