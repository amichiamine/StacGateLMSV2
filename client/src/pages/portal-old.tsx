import { useState, useRef, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
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
  Calendar,
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

  // Données des établissements
  const { data: establishments = [], isLoading } = useQuery<Establishment[]>({
    queryKey: ['/api/establishments'],
  });

  // NOUVEAU: Récupérer le contenu WYSIWYG du portail
  const { data: portalPageData } = useQuery<any>({
    queryKey: ["/api/admin/pages", "portal-home"],
  });

  // Filtrer les établissements
  const filteredEstablishments = establishments.filter((est) => {
    const matchesSearch = est.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         est.description.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesSearch && est.isActive;
  });

  // Focus sur la recherche avec raccourci clavier
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInputRef.current?.focus();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, []);

  // Fermer le menu mobile quand on clique en dehors
  useEffect(() => {
    const handleClickOutside = () => {
      setIsMobileMenuOpen(false);
    };

    if (isMobileMenuOpen) {
      document.addEventListener('click', handleClickOutside);
      return () => document.removeEventListener('click', handleClickOutside);
    }
  }, [isMobileMenuOpen]);

  // NOUVEAU: Fonction pour rendre les composants WYSIWYG
  const renderWYSIWYGComponent = (component: any) => {
    const { type, data } = component;

    switch (type) {
      case "hero":
        return (
          <section className="py-12 sm:py-16 lg:py-24" key={component.id}>
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
              <div 
                className="text-center py-20 px-4 rounded-2xl"
                style={{
                  backgroundImage: data?.backgroundImage ? `url(${data.backgroundImage})` : undefined,
                  backgroundSize: "cover",
                  backgroundPosition: "center",
                  backgroundColor: data?.backgroundImage ? undefined : "#f3f4f6",
                }}
              >
                <div className="max-w-4xl mx-auto">
                  {data?.title && (
                    <h1 className={`text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 text-${data.textAlign || 'center'}`}>
                      {data.title}
                    </h1>
                  )}
                  {data?.subtitle && (
                    <h2 className={`text-xl sm:text-2xl text-gray-700 dark:text-gray-300 mb-6 text-${data.textAlign || 'center'}`}>
                      {data.subtitle}
                    </h2>
                  )}
                  {data?.description && (
                    <p className={`text-lg text-gray-600 dark:text-gray-400 mb-8 text-${data.textAlign || 'center'} max-w-2xl mx-auto`}>
                      {data.description}
                    </p>
                  )}
                  {data?.buttonText && (
                    <Button 
                      size="lg"
                      onClick={() => window.location.href = data.buttonUrl || "#"}
                      className="bg-blue-600 hover:bg-blue-700 text-white"
                    >
                      {data.buttonText}
                      <ArrowRight className="ml-2 w-4 h-4" />
                    </Button>
                  )}
                </div>
              </div>
            </div>
          </section>
        );

      case "features":
        return (
          <section className="py-16 px-4" key={component.id}>
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
              {data?.title && (
                <h2 className="text-3xl font-bold text-center mb-4 text-gray-900 dark:text-white">{data.title}</h2>
              )}
              {data?.subtitle && (
                <p className="text-xl text-center mb-12 text-gray-600 dark:text-gray-400">{data.subtitle}</p>
              )}
              
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {(data?.features || []).map((feature: any, index: number) => (
                  <Card key={index} className="text-center hover:shadow-lg transition-shadow duration-300">
                    <CardHeader>
                      <div className="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span className="text-2xl">
                          {feature.icon === 'star' && '⭐'}
                          {feature.icon === 'heart' && '❤️'}
                          {feature.icon === 'shield' && '🛡️'}
                          {feature.icon === 'rocket' && '🚀'}
                          {feature.icon === 'trophy' && '🏆'}
                          {feature.icon === 'users' && '👥'}
                        </span>
                      </div>
                      <CardTitle className="text-xl font-semibold mb-2">{feature.title}</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-gray-600 dark:text-gray-400">{feature.description}</p>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          </section>
        );

      case "text":
        return (
          <section className="py-8 px-4" key={component.id}>
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
              <div className="max-w-4xl mx-auto">
                {data?.title && (
                  <h2 className="text-3xl font-bold mb-6 text-gray-900 dark:text-white">{data.title}</h2>
                )}
                {data?.content && (
                  <div className="prose prose-lg dark:prose-invert max-w-none">
                    <p>{data.content}</p>
                  </div>
                )}
              </div>
            </div>
          </section>
        );

      default:
        return (
          <div className="py-8 px-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg" key={component.id}>
            <div className="text-center">
              <p className="text-yellow-800 dark:text-yellow-200">
                Composant "{type}" en cours de développement
              </p>
            </div>
          </div>
        );
    }
  };

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
          <div className="flex items-center justify-between h-16 sm:h-20">
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

            {/* Navigation desktop */}
            <nav className="hidden lg:flex items-center space-x-8">
              <Link href="/" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Accueil
              </Link>
              <Link href="/about" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                À propos
              </Link>
              <Link href="/contact" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Contact
              </Link>
              <Link href="/login">
                <Button variant="outline" size="sm">
                  Connexion
                </Button>
              </Link>
            </nav>

            {/* Menu mobile toggle */}
            <Button
              variant="ghost"
              size="sm"
              className="lg:hidden"
              onClick={(e) => {
                e.stopPropagation();
                setIsMobileMenuOpen(!isMobileMenuOpen);
              }}
            >
              {isMobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </Button>
          </div>

          {/* Menu mobile */}
          {isMobileMenuOpen && (
            <div className="lg:hidden py-4 border-t border-gray-200 dark:border-gray-700">
              <nav className="flex flex-col space-y-4">
                <Link href="/" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  Accueil
                </Link>
                <Link href="/about" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  À propos
                </Link>
                <Link href="/contact" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  Contact
                </Link>
                <Link href="/login">
                  <Button variant="outline" size="sm" className="w-fit">
                    Connexion
                  </Button>
                </Link>
              </nav>
            </div>
          )}
        </div>
      </header>

      {/* Contenu principal : page par défaut */}
      <section className="py-12 sm:py-16 lg:py-24">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center max-w-4xl mx-auto">
              <h2 className="text-3xl sm:text-4xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-4 sm:mb-6">
              Bienvenue sur
              <span className="block bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                StacGateLMS
              </span>
              </h2>
              <p className="text-lg sm:text-xl lg:text-2xl text-gray-600 dark:text-gray-400 mb-8 sm:mb-12 leading-relaxed">
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
                  />
                </div>
              </div>

              {/* Statistiques globales */}
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 max-w-3xl mx-auto">
                <div className="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                  <div className="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3">
                    <div className="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
                      <Building className="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div className="text-center sm:text-left">
                      <p className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                        {establishments.length}
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
                      <p className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                        {establishments.reduce((acc, est) => acc + (est.stats?.users || 0), 0)}
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
                      <p className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                        {establishments.reduce((acc, est) => acc + (est.stats?.courses || 0), 0)}
                      </p>
                      <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Cours</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

      {/* Section des établissements avec design responsive */}
      <section className="py-8 sm:py-12 lg:py-16">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          {/* Filtres par catégorie - design responsive */}
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
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
              {filteredEstablishments.map((establishment) => (
                <Card 
                  key={establishment.id} 
                  className="group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 cursor-pointer border-2 border-transparent hover:border-blue-200 dark:hover:border-blue-800"
                >
                  <CardHeader className="pb-3">
                    {establishment.logo ? (
                      <div className="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                        <img 
                          src={establishment.logo} 
                          alt={`Logo ${establishment.name}`}
                          className="w-full h-full object-cover"
                        />
                      </div>
                    ) : (
                      <div className="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center">
                        <Building className="w-8 h-8 sm:w-10 sm:h-10 text-white" />
                      </div>
                    )}
                    <CardTitle className="text-lg sm:text-xl text-center group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                      {establishment.name}
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="pt-0">
                    <p className="text-sm text-gray-600 dark:text-gray-400 text-center mb-4 line-clamp-2">
                      {establishment.description}
                    </p>
                    
                    {establishment.stats && (
                      <div className="grid grid-cols-3 gap-2 mb-4 text-center">
                        <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-2">
                          <p className="text-xs sm:text-sm font-semibold text-blue-600 dark:text-blue-400">
                            {establishment.stats.users}
                          </p>
                          <p className="text-xs text-gray-500 dark:text-gray-400">Users</p>
                        </div>
                        <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-2">
                          <p className="text-xs sm:text-sm font-semibold text-green-600 dark:text-green-400">
                            {establishment.stats.courses}
                          </p>
                          <p className="text-xs text-gray-500 dark:text-gray-400">Cours</p>
                        </div>
                        <div className="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2">
                          <p className="text-xs sm:text-sm font-semibold text-purple-600 dark:text-purple-400">
                            {establishment.stats.themes}
                          </p>
                          <p className="text-xs text-gray-500 dark:text-gray-400">Thèmes</p>
                        </div>
                      </div>
                    )}
                    
                    <Link href={`/establishment/${establishment.slug}`}>
                      <Button className="w-full group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 text-sm sm:text-base">
                        Accéder
                        <ArrowRight className="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" />
                      </Button>
                    </Link>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}

          {filteredEstablishments.length === 0 && !isLoading && (
            <div className="text-center py-12 sm:py-16">
              <div className="w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <Search className="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
              </div>
              <h3 className="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                Aucun établissement trouvé
              </h3>
              <p className="text-gray-600 dark:text-gray-400 mb-6">
                Essayez de modifier vos critères de recherche.
              </p>
              <Button 
                variant="outline" 
                onClick={() => {
                  setSearchTerm("");
                  setSelectedCategory("all");
                }}
              >
                Réinitialiser les filtres
              </Button>
            </div>
          )}
        </div>
      </section>

      {/* Footer responsive */}
      <footer className="bg-gray-900 dark:bg-black text-white py-8 sm:py-12 lg:py-16">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div className="col-span-1 sm:col-span-2 lg:col-span-1">
              <div className="flex items-center space-x-3 mb-4">
                <div className="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                  <GraduationCap className="w-5 h-5 text-white" />
                </div>
                <h3 className="text-xl font-bold">StacGateLMS</h3>
              </div>
              <p className="text-gray-400 text-sm sm:text-base">
                Plateforme d'apprentissage multi-établissements de nouvelle génération.
              </p>
            </div>
            
            <div>
              <h4 className="font-semibold mb-4">Navigation</h4>
              <ul className="space-y-2 text-sm text-gray-400">
                <li><Link href="/" className="hover:text-white transition-colors">Accueil</Link></li>
                <li><Link href="/about" className="hover:text-white transition-colors">À propos</Link></li>
                <li><Link href="/contact" className="hover:text-white transition-colors">Contact</Link></li>
              </ul>
            </div>
            
            <div>
              <h4 className="font-semibold mb-4">Ressources</h4>
              <ul className="space-y-2 text-sm text-gray-400">
                <li><a href="#" className="hover:text-white transition-colors">Documentation</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Support</a></li>
                <li><a href="#" className="hover:text-white transition-colors">FAQ</a></li>
              </ul>
            </div>
            
            <div>
              <h4 className="font-semibold mb-4">Contact</h4>
              <ul className="space-y-2 text-sm text-gray-400">
                <li>support@stacgatelms.com</li>
                <li>+33 1 23 45 67 89</li>
                <li>Paris, France</li>
              </ul>
            </div>
          </div>
          
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
            <p>&copy; 2025 StacGateLMS. Tous droits réservés.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}