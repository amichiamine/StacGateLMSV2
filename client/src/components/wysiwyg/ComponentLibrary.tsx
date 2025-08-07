import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { 
  Type, Image, Video, Layout, Star, BarChart3, 
  Navigation, Users, FileText, Calendar, Mail,
  ShoppingCart, Award, MapPin, Phone, Globe,
  ChevronRight, MessageSquareQuote, Megaphone, CreditCard, Tag, TrendingUp,
  Sparkles, Play
} from "lucide-react";

interface ComponentLibraryProps {
  onComponentSelect: (componentType: string) => void;
}

interface ComponentCategory {
  name: string;
  components: {
    type: string;
    name: string;
    description: string;
    icon: React.ComponentType<any>;
    category: "header" | "body" | "footer" | "all";
  }[];
}

const componentCategories: ComponentCategory[] = [
  {
    name: "Mise en page",
    components: [
      {
        type: "hero",
        name: "Section Hero",
        description: "Bannière principale avec titre et CTA",
        icon: Layout,
        category: "body",
      },
      {
        type: "features",
        name: "Grille de fonctionnalités",
        description: "Présentation des avantages en grille",
        icon: Star,
        category: "body",
      },
      {
        type: "stats",
        name: "Statistiques",
        description: "Affichage de chiffres clés",
        icon: BarChart3,
        category: "body",
      },
      {
        type: "testimonials",
        name: "Témoignages",
        description: "Avis et retours clients",
        icon: Users,
        category: "body",
      },
    ],
  },
  {
    name: "Navigation",
    components: [
      {
        type: "navigation",
        name: "Menu principal",
        description: "Barre de navigation avec logo",
        icon: Navigation,
        category: "header",
      },
      {
        type: "breadcrumb",
        name: "Fil d'Ariane",
        description: "Navigation contextuelle",
        icon: Navigation,
        category: "header",
      },
      {
        type: "footer",
        name: "Pied de page",
        description: "Liens et informations légales",
        icon: Navigation,
        category: "footer",
      },
    ],
  },
  {
    name: "Contenu",
    components: [
      {
        type: "text",
        name: "Bloc de texte",
        description: "Contenu textuel personnalisable",
        icon: Type,
        category: "all",
      },
      {
        type: "image",
        name: "Image",
        description: "Image avec légende",
        icon: Image,
        category: "all",
      },
      {
        type: "video",
        name: "Vidéo",
        description: "Lecteur vidéo intégré",
        icon: Video,
        category: "all",
      },
      {
        type: "article",
        name: "Article",
        description: "Article avec titre et contenu",
        icon: FileText,
        category: "body",
      },
    ],
  },
  {
    name: "E-learning",
    components: [
      {
        type: "course-grid",
        name: "Grille de cours",
        description: "Affichage des cours disponibles",
        icon: Layout,
        category: "body",
      },
      {
        type: "course-card",
        name: "Carte de cours",
        description: "Présentation individuelle d'un cours",
        icon: Award,
        category: "body",
      },
      {
        type: "trainer-profile",
        name: "Profil formateur",
        description: "Informations sur le formateur",
        icon: Users,
        category: "body",
      },
      {
        type: "progress-tracker",
        name: "Suivi de progression",
        description: "Barre de progression des cours",
        icon: BarChart3,
        category: "body",
      },
      {
        type: "calendar",
        name: "Calendrier",
        description: "Planning des formations",
        icon: Calendar,
        category: "body",
      },
    ],
  },
  {
    name: "Formulaires",
    components: [
      {
        type: "contact-form",
        name: "Formulaire de contact",
        description: "Formulaire de prise de contact",
        icon: Mail,
        category: "body",
      },
      {
        type: "newsletter",
        name: "Newsletter",
        description: "Inscription à la newsletter",
        icon: Mail,
        category: "footer",
      },
      {
        type: "registration-form",
        name: "Inscription",
        description: "Formulaire d'inscription",
        icon: Users,
        category: "body",
      },
    ],
  },
  {
    name: "Commerce",
    components: [
      {
        type: "pricing-table",
        name: "Tableau de prix",
        description: "Grille tarifaire",
        icon: ShoppingCart,
        category: "body",
      },
      {
        type: "product-showcase",
        name: "Vitrine produit",
        description: "Mise en avant de produits",
        icon: Award,
        category: "body",
      },
      {
        type: "product-card",
        name: "Carte Produit",
        description: "Carte produit avec image, prix et bouton",
        icon: ShoppingCart,
        category: "body",
      },
      {
        type: "pricing-card",
        name: "Carte Tarif",
        description: "Carte de tarification avec fonctionnalités",
        icon: CreditCard,
        category: "body",
      },
    ],
  },
  {
    name: "Publicité & Promotion",
    components: [
      {
        type: "cta-banner",
        name: "Bannière CTA",
        description: "Bannière d'appel à l'action pour publicité",
        icon: Megaphone,
        category: "all",
      },
      {
        type: "promo-popup",
        name: "Pop-up Promo",
        description: "Fenêtre promotionnelle",
        icon: TrendingUp,
        category: "body",
      },
      {
        type: "discount-badge",
        name: "Badge Remise",
        description: "Badge de réduction ou offre spéciale",
        icon: Tag,
        category: "all",
      },
      {
        type: "social-proof",
        name: "Preuve Sociale",
        description: "Logos de clients ou partenaires",
        icon: Users,
        category: "body",
      },
    ],
  },
  {
    name: "Médias & Interaction",
    components: [
      {
        type: "carousel",
        name: "Carrousel",
        description: "Carrousel d'images ou de contenu",
        icon: ChevronRight,
        category: "body",
      },
      {
        type: "testimonial",
        name: "Témoignage",
        description: "Citation client avec photo et nom",
        icon: MessageSquareQuote,
        category: "body",
      },
      {
        type: "tag-list",
        name: "Liste d'Étiquettes",
        description: "Liste de tags ou catégories",
        icon: Tag,
        category: "all",
      },
      {
        type: "gallery",
        name: "Galerie",
        description: "Galerie d'images en grille",
        icon: Image,
        category: "body",
      },
      {
        type: "video-player",
        name: "Lecteur Vidéo",
        description: "Lecteur vidéo interactif",
        icon: Play,
        category: "body",
      },
    ],
  },
  {
    name: "Contact",
    components: [
      {
        type: "map",
        name: "Carte interactive",
        description: "Localisation géographique",
        icon: MapPin,
        category: "body",
      },
      {
        type: "contact-info",
        name: "Informations de contact",
        description: "Adresse, téléphone, email",
        icon: Phone,
        category: "footer",
      },
      {
        type: "social-links",
        name: "Liens sociaux",
        description: "Réseaux sociaux",
        icon: Globe,
        category: "footer",
      },
    ],
  },
];

export function ComponentLibrary({ onComponentSelect }: ComponentLibraryProps) {
  // Debug: toutes les catégories sont bien définies
  console.log("ComponentLibrary rendering with categories:", componentCategories.map(c => c.name));
  
  try {
    return (
      <div className="space-y-4 pb-8 max-h-full overflow-auto">
        {componentCategories.map((category, categoryIndex) => {
          console.log(`Rendering category ${categoryIndex}: ${category.name} with ${category.components.length} components`);
          return (
            <div key={category.name} className="space-y-3">
              <h3 className="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide border-b border-gray-200 dark:border-gray-600 pb-2 sticky top-0 bg-white dark:bg-gray-800 z-10">
                {category.name} ({category.components.length})
              </h3>
              
              <div className="grid gap-2">
                {category.components.map((component, componentIndex) => {
                  console.log(`Rendering component ${componentIndex}: ${component.name} (${component.type})`);
                  if (!component.icon) {
                    console.warn(`Missing icon for component: ${component.type}`);
                    return null;
                  }
                  try {
                    const IconComponent = component.icon;
                    return (
                      <Card
                        key={`${category.name}-${component.type}-${componentIndex}`}
                        className="cursor-pointer hover:shadow-md transition-shadow border-dashed border-2 border-gray-200 dark:border-gray-700 hover:border-blue-300"
                        onClick={() => {
                          console.log(`Component clicked: ${component.type}`);
                          onComponentSelect(component.type);
                        }}
                      >
                        <CardContent className="p-3">
                          <div className="flex items-start space-x-3">
                            <div className="flex-shrink-0">
                              <IconComponent className="w-5 h-5 text-blue-600" />
                            </div>
                            <div className="flex-1 min-w-0">
                              <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                                {component.name}
                              </h4>
                              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {component.description}
                              </p>
                              <div className="mt-2">
                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                  component.category === 'header' ? 'bg-green-100 text-green-800' :
                                  component.category === 'footer' ? 'bg-red-100 text-red-800' :
                                  component.category === 'body' ? 'bg-blue-100 text-blue-800' :
                                  'bg-gray-100 text-gray-800'
                                }`}>
                                  {component.category === 'all' ? 'Universel' : component.category}
                                </span>
                              </div>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    );
                  } catch (error) {
                    console.error(`Error rendering component ${component.type}:`, error);
                    return (
                      <div key={`error-${component.type}`} className="p-2 bg-red-100 text-red-800 rounded">
                        Erreur: {component.name}
                      </div>
                    );
                  }
                })}
              </div>
            </div>
          );
        })}
        
        <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
          <h4 className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
            💡 Astuce
          </h4>
          <p className="text-xs text-blue-700 dark:text-blue-300">
            Cliquez sur un composant pour l'ajouter à la section sélectionnée. 
            Vous pourrez ensuite le personnaliser via l'éditeur de propriétés.
          </p>
        </div>
      </div>
    );
  } catch (error) {
    console.error("Error rendering ComponentLibrary:", error);
    return (
      <div className="p-4 bg-red-100 text-red-800 rounded">
        Erreur de rendu de la bibliothèque de composants
      </div>
    );
  }
}

export default ComponentLibrary;