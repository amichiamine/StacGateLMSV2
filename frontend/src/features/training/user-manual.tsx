import { useState, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { useAuth } from "@/hooks/useAuth";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ChevronRight, ChevronDown, Book, User, Users, GraduationCap, Settings, FileText, Search, Loader2 } from "lucide-react";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";

interface ManualSection {
  id: string;
  title: string;
  content: string;
  subsections?: ManualSection[];
  roles: string[];
}

const manualContent: ManualSection[] = [
  {
    id: "getting-started",
    title: "Premiers pas avec StacGateLMS",
    content: `
      <h3>Bienvenue sur StacGateLMS</h3>
      <p>StacGateLMS est une plateforme d'apprentissage en ligne complète qui permet de gérer formations, cours, évaluations et apprenants de manière centralisée.</p>
      
      <h4>Fonctionnalités principales</h4>
      <ul>
        <li>Gestion multi-établissements avec isolation complète des données</li>
        <li>Système de rôles granulaire (Super Admin, Admin, Manager, Formateur, Apprenant)</li>
        <li>Personnalisation complète de l'interface via base de données</li>
        <li>Création et gestion de cours avec contenus multimédias</li>
        <li>Système d'évaluation avec workflow de validation</li>
        <li>Tableau de bord adaptatif selon le rôle utilisateur</li>
      </ul>
    `,
    roles: ["super_admin", "admin", "manager", "formateur", "apprenant"],
    subsections: [
      {
        id: "login",
        title: "Connexion à la plateforme",
        content: `
          <h4>Se connecter</h4>
          <ol>
            <li>Accédez à la page de connexion</li>
            <li>Saisissez votre email et mot de passe</li>
            <li>Cliquez sur "Se connecter"</li>
            <li>Vous serez redirigé vers votre tableau de bord personnalisé</li>
          </ol>
          
          <h4>Mot de passe oublié</h4>
          <p>Contactez votre administrateur pour réinitialiser votre mot de passe.</p>
        `,
        roles: ["super_admin", "admin", "manager", "formateur", "apprenant"]
      }
    ]
  },
  {
    id: "super-admin-guide",
    title: "Guide Super Administrateur",
    content: `
      <h3>Rôle du Super Administrateur</h3>
      <p>Le Super Administrateur a accès à l'ensemble de la plateforme et peut gérer tous les établissements.</p>
      
      <h4>Responsabilités principales</h4>
      <ul>
        <li>Création et gestion des établissements</li>
        <li>Configuration globale de la plateforme</li>
        <li>Gestion des utilisateurs système</li>
        <li>Supervision générale</li>
      </ul>
    `,
    roles: ["super_admin"],
    subsections: [
      {
        id: "manage-establishments",
        title: "Gestion des établissements",
        content: `
          <h4>Créer un nouvel établissement</h4>
          <ol>
            <li>Accédez à la section "Établissements"</li>
            <li>Cliquez sur "Nouvel établissement"</li>
            <li>Remplissez les informations : nom, slug, description</li>
            <li>Configurez les paramètres spécifiques</li>
            <li>Validez la création</li>
          </ol>
          
          <h4>Modifier un établissement</h4>
          <ol>
            <li>Sélectionnez l'établissement dans la liste</li>
            <li>Cliquez sur "Modifier"</li>
            <li>Ajustez les paramètres nécessaires</li>
            <li>Enregistrez les modifications</li>
          </ol>
        `,
        roles: ["super_admin"]
      }
    ]
  },
  {
    id: "admin-guide",
    title: "Guide Administrateur",
    content: `
      <h3>Rôle de l'Administrateur</h3>
      <p>L'Administrateur gère son établissement de manière autonome avec tous les pouvoirs sur son écosystème.</p>
      
      <h4>Fonctionnalités disponibles</h4>
      <ul>
        <li>Gestion des utilisateurs de l'établissement</li>
        <li>Configuration des cours et formations</li>
        <li>Personnalisation de l'interface</li>
        <li>Validation des évaluations</li>
        <li>Consultation des statistiques</li>
      </ul>
    `,
    roles: ["admin"],
    subsections: [
      {
        id: "user-management",
        title: "Gestion des utilisateurs",
        content: `
          <h4>Ajouter un utilisateur</h4>
          <ol>
            <li>Allez dans "Gestion des utilisateurs"</li>
            <li>Cliquez sur "Nouvel utilisateur"</li>
            <li>Remplissez le formulaire d'inscription</li>
            <li>Sélectionnez le rôle approprié</li>
            <li>Définissez les permissions</li>
            <li>Enregistrez</li>
          </ol>
          
          <h4>Modifier les permissions</h4>
          <ol>
            <li>Sélectionnez l'utilisateur</li>
            <li>Accédez à l'onglet "Permissions"</li>
            <li>Ajustez les droits d'accès</li>
            <li>Sauvegardez les modifications</li>
          </ol>
        `,
        roles: ["admin", "manager"]
      }
    ]
  },
  {
    id: "teacher-guide",
    title: "Guide Formateur",
    content: `
      <h3>Rôle du Formateur</h3>
      <p>Le Formateur peut créer des cours, gérer ses apprenants et créer des évaluations.</p>
      
      <h4>Vos outils pédagogiques</h4>
      <ul>
        <li>Création et modification de cours</li>
        <li>Gestion des inscriptions</li>
        <li>Création d'évaluations</li>
        <li>Suivi des progressions</li>
        <li>Communication avec les apprenants</li>
      </ul>
    `,
    roles: ["formateur"],
    subsections: [
      {
        id: "create-course",
        title: "Créer un cours",
        content: `
          <h4>Étapes de création</h4>
          <ol>
            <li>Accédez à "Mes cours"</li>
            <li>Cliquez sur "Nouveau cours"</li>
            <li>Définissez le titre et la description</li>
            <li>Sélectionnez la catégorie et le niveau</li>
            <li>Ajoutez les modules de contenu</li>
            <li>Configurez les paramètres d'accès</li>
            <li>Soumettez pour validation</li>
          </ol>
          
          <h4>Gestion du contenu</h4>
          <p>Vous pouvez ajouter différents types de contenus : texte, vidéos, documents, quiz interactifs.</p>
        `,
        roles: ["formateur"]
      }
    ]
  },
  {
    id: "student-guide",
    title: "Guide Apprenant",
    content: `
      <h3>Votre parcours d'apprentissage</h3>
      <p>En tant qu'apprenant, vous avez accès à tous les cours auxquels vous êtes inscrit et pouvez suivre votre progression.</p>
      
      <h4>Fonctionnalités disponibles</h4>
      <ul>
        <li>Accès aux cours inscrits</li>
        <li>Suivi de progression personnalisé</li>
        <li>Passage d'évaluations</li>
        <li>Consultation des résultats</li>
        <li>Communication avec les formateurs</li>
      </ul>
    `,
    roles: ["apprenant"],
    subsections: [
      {
        id: "take-course",
        title: "Suivre un cours",
        content: `
          <h4>Navigation dans un cours</h4>
          <ol>
            <li>Cliquez sur le cours depuis votre tableau de bord</li>
            <li>Parcourez les modules dans l'ordre</li>
            <li>Marquez les sections comme terminées</li>
            <li>Prenez des notes si nécessaire</li>
            <li>Passez aux évaluations quand elles sont disponibles</li>
          </ol>
          
          <h4>Gestion de votre progression</h4>
          <p>Votre progression est automatiquement sauvegardée. Vous pouvez reprendre là où vous vous êtes arrêté.</p>
        `,
        roles: ["apprenant"]
      }
    ]
  },
  {
    id: "customization",
    title: "Personnalisation de l'interface",
    content: `
      <h3>Personnaliser votre plateforme</h3>
      <p>StacGateLMS permet une personnalisation complète de l'interface pour correspondre à l'identité de votre établissement.</p>
      
      <h4>Éléments personnalisables</h4>
      <ul>
        <li>Couleurs et thèmes</li>
        <li>Logo et images</li>
        <li>Textes et contenus</li>
        <li>Structure des menus</li>
        <li>Pages d'accueil</li>
      </ul>
    `,
    roles: ["super_admin", "admin"],
    subsections: [
      {
        id: "theme-config",
        title: "Configuration des thèmes",
        content: `
          <h4>Modifier les couleurs</h4>
          <ol>
            <li>Accédez aux "Paramètres" > "Thèmes"</li>
            <li>Sélectionnez le thème à modifier</li>
            <li>Utilisez les color-pickers pour ajuster</li>
            <li>Prévisualisez les changements</li>
            <li>Appliquez le nouveau thème</li>
          </ol>
          
          <h4>Personnaliser les contenus</h4>
          <p>Modifiez les textes, images et blocs de contenu via l'éditeur WYSIWYG intégré.</p>
        `,
        roles: ["super_admin", "admin"]
      }
    ]
  }
];

export default function UserManualPage() {
  const { user } = useAuth();
  const [expandedSections, setExpandedSections] = useState<string[]>(["getting-started"]);
  const [activeSection, setActiveSection] = useState("getting-started");
  const [searchQuery, setSearchQuery] = useState("");

  // Query to fetch help contents from the API
  const { data: helpContents, isLoading, error } = useQuery({
    queryKey: ['/api/documentation/help', user?.role],
    enabled: !!user,
  });

  // Query to search help content
  const { data: searchResults, isLoading: isSearching } = useQuery({
    queryKey: ['/api/documentation/search', searchQuery],
    enabled: searchQuery.length > 2,
  });

  // Use API data if available, otherwise fall back to static content
  const userRole = (user as any)?.role || "apprenant";
  const displayContent = helpContents && helpContents.length > 0 
    ? helpContents 
    : manualContent.filter(section => section.roles.includes(userRole)).map(section => ({
        ...section,
        subsections: section.subsections?.filter(sub => sub.roles.includes(userRole))
      }));

  // Handle search results
  const searchContent = searchQuery.length > 2 && searchResults ? searchResults : [];
  const filteredContent = searchContent.length > 0 ? searchContent : displayContent;

  const toggleSection = (sectionId: string) => {
    setExpandedSections(prev => 
      prev.includes(sectionId)
        ? prev.filter(id => id !== sectionId)
        : [...prev, sectionId]
    );
  };

  const scrollToSection = (sectionId: string) => {
    setActiveSection(sectionId);
    const element = document.getElementById(`content-${sectionId}`);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <div className="flex h-screen bg-background" data-testid="user-manual-page">
      {/* Sidebar Navigation */}
      <div className="w-80 border-r bg-card">
        <div className="p-6 border-b space-y-4">
          <div className="flex items-center gap-2">
            <Book className="h-6 w-6 text-primary" />
            <h1 className="text-xl font-semibold">Manuel d'utilisation</h1>
          </div>
          <p className="text-sm text-muted-foreground">
            Guide complet pour {(user as any)?.role || "utilisateur"}
          </p>
          
          {/* Search Input */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Rechercher dans la documentation..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
              data-testid="input-search"
            />
            {isSearching && (
              <Loader2 className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground" />
            )}
          </div>
        </div>
        
        <ScrollArea className="h-[calc(100vh-100px)]">
          <div className="p-4 space-y-2">
            {filteredContent.map((section) => (
              <div key={section.id}>
                <Collapsible 
                  open={expandedSections.includes(section.id)}
                  onOpenChange={() => toggleSection(section.id)}
                >
                  <CollapsibleTrigger asChild>
                    <Button
                      variant={activeSection === section.id ? "secondary" : "ghost"}
                      className="w-full justify-start text-left h-auto p-3"
                      data-testid={`section-${section.id}`}
                    >
                      <div className="flex items-center gap-2">
                        {expandedSections.includes(section.id) ? 
                          <ChevronDown className="h-4 w-4" /> : 
                          <ChevronRight className="h-4 w-4" />
                        }
                        <span className="font-medium">{section.title}</span>
                      </div>
                    </Button>
                  </CollapsibleTrigger>
                  
                  <CollapsibleContent className="ml-6 mt-1 space-y-1">
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-full justify-start text-muted-foreground"
                      onClick={() => scrollToSection(section.id)}
                      data-testid={`nav-${section.id}`}
                    >
                      Vue d'ensemble
                    </Button>
                    
                    {section.subsections?.map((subsection) => (
                      <Button
                        key={subsection.id}
                        variant="ghost"
                        size="sm"
                        className="w-full justify-start text-muted-foreground"
                        onClick={() => scrollToSection(subsection.id)}
                        data-testid={`nav-${subsection.id}`}
                      >
                        {subsection.title}
                      </Button>
                    ))}
                  </CollapsibleContent>
                </Collapsible>
              </div>
            ))}
          </div>
        </ScrollArea>
      </div>

      {/* Main Content */}
      <div className="flex-1 overflow-hidden">
        <ScrollArea className="h-full">
          <div className="p-8 max-w-4xl mx-auto">
            {filteredContent.map((section) => (
              <div key={section.id} className="mb-12">
                <Card id={`content-${section.id}`} className="mb-8">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      {section.id === 'getting-started' && <Book className="h-5 w-5" />}
                      {section.id === 'super-admin-guide' && <Settings className="h-5 w-5" />}
                      {section.id === 'admin-guide' && <Users className="h-5 w-5" />}
                      {section.id === 'teacher-guide' && <GraduationCap className="h-5 w-5" />}
                      {section.id === 'student-guide' && <User className="h-5 w-5" />}
                      {section.id === 'customization' && <Settings className="h-5 w-5" />}
                      {section.title}
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div 
                      className="prose prose-sm max-w-none dark:prose-invert"
                      dangerouslySetInnerHTML={{ __html: section.content }}
                      data-testid={`content-${section.id}`}
                    />
                  </CardContent>
                </Card>

                {section.subsections?.map((subsection) => (
                  <Card key={subsection.id} id={`content-${subsection.id}`} className="mb-6 ml-8">
                    <CardHeader>
                      <CardTitle className="text-lg flex items-center gap-2">
                        <FileText className="h-4 w-4" />
                        {subsection.title}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div 
                        className="prose prose-sm max-w-none dark:prose-invert"
                        dangerouslySetInnerHTML={{ __html: subsection.content }}
                        data-testid={`content-${subsection.id}`}
                      />
                    </CardContent>
                  </Card>
                ))}
              </div>
            ))}
          </div>
        </ScrollArea>
      </div>
    </div>
  );
}