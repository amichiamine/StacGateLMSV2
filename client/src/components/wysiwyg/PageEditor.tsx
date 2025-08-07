import { useState, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { ScrollArea } from "@/components/ui/scroll-area";
import { 
  Eye, Edit, Save, Plus, Trash2, GripVertical, 
  Layout, Type, Image, Video, FileText, 
  Settings, Palette, Move, Copy, ChevronUp, ChevronDown
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import ComponentLibrary from "./ComponentLibrary";
import ComponentEditor from "./ComponentEditor";
import PagePreview from "./PagePreview";

interface PageData {
  id: string;
  pageName: string;
  pageTitle: string;
  pageDescription: string;
  layout: any;
  isActive: boolean;
}

interface ComponentData {
  id: string;
  componentName: string;
  componentType: string;
  componentData: any;
  isActive: boolean;
}

interface PageEditorProps {
  pageName?: string;
  pageId?: string;
  initialContent?: string;
  onPageChange?: (pageName: string) => void;
  onSave?: (content: string) => void;
}

export function PageEditor({ 
  pageName, 
  pageId, 
  initialContent, 
  onPageChange, 
  onSave 
}: PageEditorProps) {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [selectedSection, setSelectedSection] = useState<string>("header");
  const [selectedComponent, setSelectedComponent] = useState<string | null>(null);
  const [editMode, setEditMode] = useState(false);
  const [previewMode, setPreviewMode] = useState(false);

  // Récupérer les données de la page
  const { data: pageData, isLoading: pageLoading } = useQuery<PageData>({
    queryKey: ["/api/admin/pages", pageName || pageId],
    enabled: !!(pageName || pageId)
  });

  // Récupérer les composants disponibles
  const { data: availableComponents = [], isLoading: componentsLoading } = useQuery<ComponentData[]>({
    queryKey: ["/api/admin/components"],
  });

  // Mutation pour sauvegarder la page
  const savePageMutation = useMutation({
    mutationFn: async (pageData: any) => {
      try {
        console.log("Saving page data:", pageData);
        return await apiRequest("PATCH", `/api/admin/pages/${pageName || pageId || 'default'}`, pageData);
      } catch (error) {
        console.error("Save page error:", error);
        throw error;
      }
    },
    onSuccess: (result) => {
      console.log("Page saved successfully:", result);
      toast({
        title: "Succès",
        description: "Page sauvegardée avec succès",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/pages"] });
    },
    onError: (error: any) => {
      console.error("Save page mutation error:", error);
      toast({
        title: "Erreur",
        description: `Impossible de sauvegarder la page: ${error?.message || 'Erreur inconnue'}`,
        variant: "destructive",
      });
    },
  });

  const handleSavePage = () => {
    if (pageData) {
      try {
        const dataToSave = {
          pageTitle: pageData.pageTitle,
          pageDescription: pageData.pageDescription,
          layout: pageData.layout,
        };
        console.log("Preparing to save page:", dataToSave);
        savePageMutation.mutate(dataToSave);
      } catch (error) {
        console.error("Error preparing page save:", error);
        toast({
          title: "Erreur de préparation",
          description: "Erreur lors de la préparation de la sauvegarde",
          variant: "destructive",
        });
      }
    } else {
      console.warn("No page data to save");
      toast({
        title: "Aucune donnée",
        description: "Aucune donnée de page à sauvegarder",
        variant: "destructive",
      });
    }
  };

  const addComponentToSection = (componentType: string, sectionType: string) => {
    if (!pageData) {
      console.error("Aucune donnée de page disponible");
      return;
    }

    console.log("Ajout du composant:", componentType, "à la section:", sectionType);

    const newComponent = {
      id: `comp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      type: componentType,
      data: getDefaultComponentData(componentType),
      styles: {},
    };

    // Créer une copie profonde du layout
    const updatedLayout = JSON.parse(JSON.stringify(pageData.layout || {}));
    if (!updatedLayout.sections) {
      updatedLayout.sections = [
        { type: "header", components: [] },
        { type: "body", components: [] },
        { type: "footer", components: [] }
      ];
    }

    const sectionIndex = updatedLayout.sections.findIndex((s: any) => s.type === sectionType);
    if (sectionIndex >= 0) {
      if (!updatedLayout.sections[sectionIndex].components) {
        updatedLayout.sections[sectionIndex].components = [];
      }
      updatedLayout.sections[sectionIndex].components.push(newComponent);
    } else {
      updatedLayout.sections.push({
        type: sectionType,
        components: [newComponent],
        styles: {},
      });
    }

    console.log("Layout mis à jour:", updatedLayout);

    // Mise à jour locale immédiate avec trigger de re-render
    const updatedPageData = { 
      ...pageData, 
      layout: updatedLayout,
      updatedAt: new Date().toISOString() // Force le re-render
    };
    
    queryClient.setQueryData(["/api/admin/pages", pageName], updatedPageData);
    
    // CORRECTION: Sauvegarder immédiatement en base de données avec les nouvelles données
    try {
      const dataToSave = {
        pageTitle: updatedPageData.pageTitle,
        pageDescription: updatedPageData.pageDescription,
        layout: updatedPageData.layout,
      };
      console.log("Auto-saving page after component addition:", dataToSave);
      savePageMutation.mutate(dataToSave);
    } catch (error) {
      console.error("Error auto-saving page:", error);
    }
    
    // Afficher un toast de confirmation
    toast({
      title: "Composant ajouté",
      description: `Le composant ${componentType} a été ajouté à la section ${sectionType}`,
    });
  };

  const removeComponentFromSection = (componentId: string, sectionType: string) => {
    if (!pageData) {
      console.error("Aucune donnée de page disponible pour la suppression");
      return;
    }

    console.log("Suppression du composant:", componentId, "de la section:", sectionType);

    // Créer une copie profonde du layout
    const updatedLayout = JSON.parse(JSON.stringify(pageData.layout || {}));
    if (!updatedLayout.sections) return;

    const sectionIndex = updatedLayout.sections.findIndex((s: any) => s.type === sectionType);
    
    if (sectionIndex >= 0 && updatedLayout.sections[sectionIndex].components) {
      const componentsBefore = updatedLayout.sections[sectionIndex].components.length;
      updatedLayout.sections[sectionIndex].components = updatedLayout.sections[sectionIndex].components.filter(
        (comp: any) => comp.id !== componentId
      );
      const componentsAfter = updatedLayout.sections[sectionIndex].components.length;
      
      console.log(`Composants avant: ${componentsBefore}, après: ${componentsAfter}`);
      
      if (componentsBefore === componentsAfter) {
        console.error("Aucun composant supprimé - ID non trouvé");
        return;
      }
    }

    // Mise à jour avec trigger de re-render
    const updatedPageData = { 
      ...pageData, 
      layout: updatedLayout,
      updatedAt: new Date().toISOString()
    };
    
    queryClient.setQueryData(["/api/admin/pages", pageName], updatedPageData);
    
    // CORRECTION: Auto-sauvegarder après suppression de composant
    try {
      const dataToSave = {
        pageTitle: updatedPageData.pageTitle,
        pageDescription: updatedPageData.pageDescription,
        layout: updatedPageData.layout,
      };
      console.log("Auto-saving page after component removal:", dataToSave);
      savePageMutation.mutate(dataToSave);
    } catch (error) {
      console.error("Error auto-saving page after removal:", error);
    }
    
    // Désélectionner le composant s'il était sélectionné
    if (selectedComponent === componentId) {
      setSelectedComponent(null);
    }
    
    toast({
      title: "Composant supprimé",
      description: "Le composant a été supprimé avec succès",
    });
  };

  const updateComponentData = (componentId: string, sectionType: string, newData: any) => {
    if (!pageData) return;

    const updatedLayout = { ...pageData.layout };
    const sectionIndex = updatedLayout.sections.findIndex((s: any) => s.type === sectionType);
    
    if (sectionIndex >= 0 && updatedLayout.sections[sectionIndex].components) {
      const componentIndex = updatedLayout.sections[sectionIndex].components.findIndex(
        (comp: any) => comp.id === componentId
      );
      
      if (componentIndex >= 0) {
        updatedLayout.sections[sectionIndex].components[componentIndex].data = {
          ...updatedLayout.sections[sectionIndex].components[componentIndex].data,
          ...newData,
        };
      }
    }

    const updatedPageData = { ...pageData, layout: updatedLayout };
    queryClient.setQueryData(["/api/admin/pages", pageName], updatedPageData);
  };

  const getDefaultComponentData = (componentType: string) => {
    const defaults: { [key: string]: any } = {
      hero: {
        title: "Titre principal",
        subtitle: "Sous-titre accrocheur",
        description: "Description détaillée de la section",
        buttonText: "Découvrir",
        buttonUrl: "#",
        backgroundImage: "",
        textAlign: "center",
      },
      features: {
        title: "Nos fonctionnalités",
        subtitle: "Découvrez ce qui nous rend uniques",
        features: [
          {
            icon: "star",
            title: "Fonctionnalité 1",
            description: "Description de la première fonctionnalité",
          },
          {
            icon: "heart",
            title: "Fonctionnalité 2", 
            description: "Description de la deuxième fonctionnalité",
          },
        ],
      },
      stats: {
        title: "Nos statistiques",
        stats: [
          { number: "1000+", label: "Utilisateurs satisfaits" },
          { number: "50+", label: "Projets réalisés" },
          { number: "99%", label: "Taux de satisfaction" },
        ],
      },
      text: {
        content: "Votre contenu textuel ici...",
        textAlign: "left",
        fontSize: "base",
      },
      image: {
        src: "",
        alt: "Description de l'image",
        caption: "",
        width: "100%",
        align: "center",
      },
      navigation: {
        logo: "",
        logoText: "StacGate",
        menuItems: [
          { label: "Accueil", url: "/" },
          { label: "Cours", url: "/courses" },
          { label: "Contact", url: "/contact" },
        ],
      },
      footer: {
        copyright: "© 2025 StacGate Academy. Tous droits réservés.",
        links: [
          { label: "Mentions légales", url: "/legal" },
          { label: "Confidentialité", url: "/privacy" },
        ],
        socialLinks: [],
      },
      "course-card": {
        title: "Nom du cours",
        description: "Description courte du cours",
        image: "https://via.placeholder.com/300x200",
        price: "99€",
        duration: "8h",
        level: "Débutant",
        instructor: "Formateur",
        rating: 4.5,
      },
      "product-card": {
        name: "Nom du produit",
        description: "Description du produit",
        image: "https://via.placeholder.com/300x200",
        price: "49€",
        originalPrice: "59€",
        badge: "Nouveau",
        inStock: true,
      },
      carousel: {
        title: "Galerie d'images",
        items: [
          { type: "image", src: "https://via.placeholder.com/800x400", caption: "Image 1" },
          { type: "image", src: "https://via.placeholder.com/800x400", caption: "Image 2" },
          { type: "image", src: "https://via.placeholder.com/800x400", caption: "Image 3" },
        ],
        autoplay: false,
        showDots: true,
        showArrows: true,
      },
      testimonial: {
        quote: "Ce service est absolument fantastique ! Je le recommande vivement.",
        author: "Jean Dupont",
        position: "CEO, Entreprise ABC",
        avatar: "https://via.placeholder.com/80x80",
        rating: 5,
      },
      "cta-banner": {
        title: "Offre Spéciale !",
        description: "Profitez de -30% sur tous nos cours jusqu'au 31 décembre",
        buttonText: "Profiter de l'offre",
        buttonUrl: "#",
        backgroundColor: "#ff6b6b",
        textColor: "#ffffff",
        urgent: true,
      },
      "pricing-card": {
        name: "Plan Standard",
        price: "29€",
        period: "mois",
        description: "Parfait pour débuter",
        features: [
          "Accès à 10 cours",
          "Support email",
          "Certificats",
          "Forum communauté"
        ],
        buttonText: "Choisir ce plan",
        popular: false,
      },
      "tag-list": {
        title: "Catégories",
        tags: [
          { name: "Web Development", color: "#3b82f6" },
          { name: "Design", color: "#10b981" },
          { name: "Marketing", color: "#f59e0b" },
          { name: "Business", color: "#8b5cf6" },
          { name: "Photography", color: "#ef4444" },
        ],
      },
      "social-proof": {
        title: "Ils nous font confiance",
        logos: [
          { name: "Entreprise 1", url: "https://via.placeholder.com/150x80" },
          { name: "Entreprise 2", url: "https://via.placeholder.com/150x80" },
          { name: "Entreprise 3", url: "https://via.placeholder.com/150x80" },
          { name: "Entreprise 4", url: "https://via.placeholder.com/150x80" },
        ],
      },
    };

    return defaults[componentType] || {};
  };

  const moveComponentUp = (componentId: string, sectionType: string) => {
    if (!pageData) return;
    
    const updatedLayout = JSON.parse(JSON.stringify(pageData.layout || {}));
    const sectionIndex = updatedLayout.sections.findIndex((s: any) => s.type === sectionType);
    
    if (sectionIndex >= 0 && updatedLayout.sections[sectionIndex].components) {
      const components = updatedLayout.sections[sectionIndex].components;
      const componentIndex = components.findIndex((comp: any) => comp.id === componentId);
      
      if (componentIndex > 0) {
        // Échanger avec l'élément précédent
        const temp = components[componentIndex];
        components[componentIndex] = components[componentIndex - 1];
        components[componentIndex - 1] = temp;
        
        const updatedPageData = { 
          ...pageData, 
          layout: updatedLayout,
          updatedAt: new Date().toISOString()
        };
        
        queryClient.setQueryData(["/api/admin/pages", pageName], updatedPageData);
        
        toast({
          title: "Composant déplacé",
          description: "Le composant a été déplacé vers le haut",
        });
      }
    }
  };

  const moveComponentDown = (componentId: string, sectionType: string) => {
    if (!pageData) return;
    
    const updatedLayout = JSON.parse(JSON.stringify(pageData.layout || {}));
    const sectionIndex = updatedLayout.sections.findIndex((s: any) => s.type === sectionType);
    
    if (sectionIndex >= 0 && updatedLayout.sections[sectionIndex].components) {
      const components = updatedLayout.sections[sectionIndex].components;
      const componentIndex = components.findIndex((comp: any) => comp.id === componentId);
      
      if (componentIndex >= 0 && componentIndex < components.length - 1) {
        // Échanger avec l'élément suivant
        const temp = components[componentIndex];
        components[componentIndex] = components[componentIndex + 1];
        components[componentIndex + 1] = temp;
        
        const updatedPageData = { 
          ...pageData, 
          layout: updatedLayout,
          updatedAt: new Date().toISOString()
        };
        
        queryClient.setQueryData(["/api/admin/pages", pageName], updatedPageData);
        
        toast({
          title: "Composant déplacé",
          description: "Le composant a été déplacé vers le bas",
        });
      }
    }
  };

  if (pageLoading || componentsLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (previewMode && pageData) {
    return <PagePreview pageData={pageData} onExitPreview={() => setPreviewMode(false)} />;
  }

  return (
    <div className="h-screen flex bg-gray-50 dark:bg-gray-900">
      {/* Sidebar gauche - Bibliothèque de composants */}
      <div className="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col h-full">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            Éditeur WYSIWYG
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Glissez-déposez pour personnaliser
          </p>
        </div>

        <Tabs defaultValue="components" className="w-full flex flex-col flex-1">
          <TabsList className="grid w-full grid-cols-2 m-4 flex-shrink-0">
            <TabsTrigger value="components">Composants</TabsTrigger>
            <TabsTrigger value="pages">Pages</TabsTrigger>
          </TabsList>

          <TabsContent value="components" className="flex-1 overflow-hidden">
            <ScrollArea className="h-[calc(100vh-240px)] px-4">
              <ComponentLibrary
                onComponentSelect={(componentType: string) => 
                  addComponentToSection(componentType, selectedSection)
                }
              />
            </ScrollArea>
          </TabsContent>

          <TabsContent value="pages" className="flex-1 overflow-hidden">
            <ScrollArea className="h-full px-4">
              <div className="space-y-2">
                {["home", "dashboard-student", "dashboard-trainer", "courses", "formations", "admin"].map((page) => (
                  <Button
                    key={page}
                    variant={pageName === page ? "default" : "ghost"}
                    className="w-full justify-start"
                    onClick={() => onPageChange?.(page)}
                  >
                    <Layout className="w-4 h-4 mr-2" />
                    {page.replace("-", " ").replace(/\b\w/g, l => l.toUpperCase())}
                  </Button>
                ))}
              </div>
            </ScrollArea>
          </TabsContent>
        </Tabs>
      </div>

      {/* Zone centrale - Éditeur de mise en page */}
      <div className="flex-1 flex flex-col">
        {/* Barre d'outils */}
        <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <Select value={selectedSection} onValueChange={setSelectedSection}>
                <SelectTrigger className="w-40">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="header">Header</SelectItem>
                  <SelectItem value="body">Body</SelectItem>
                  <SelectItem value="footer">Footer</SelectItem>
                </SelectContent>
              </Select>

              <Badge variant="outline" className="capitalize">
                {(pageName || pageId || 'default').replace("-", " ")}
              </Badge>
            </div>

            <div className="flex items-center space-x-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPreviewMode(true)}
              >
                <Eye className="w-4 h-4 mr-2" />
                Aperçu
              </Button>
              <Button
                size="sm"
                onClick={handleSavePage}
                disabled={savePageMutation.isPending}
              >
                <Save className="w-4 h-4 mr-2" />
                Sauvegarder
              </Button>
            </div>
          </div>
        </div>

        {/* Zone d'édition principale */}
        <ScrollArea className="flex-1 p-6">
          <div className="max-w-6xl mx-auto">
            <div className="mb-6">
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                {pageData?.pageTitle || "Titre de la page"}
              </h1>
              <p className="text-gray-600 dark:text-gray-400">
                {pageData?.pageDescription || "Description de la page"}
              </p>
            </div>

            {/* Sections de la page */}
            {["header", "body", "footer"].map((sectionType) => (
              <Card key={sectionType} className={`mb-6 ${selectedSection === sectionType ? 'ring-2 ring-blue-500' : ''}`}>
                <CardHeader className="cursor-pointer" onClick={() => setSelectedSection(sectionType)}>
                  <CardTitle className="flex items-center justify-between">
                    <span className="capitalize">{sectionType}</span>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={(e) => {
                        e.stopPropagation();
                        console.log("Bouton + cliqué pour la section:", sectionType);
                        // Ajouter un composant par défaut
                        addComponentToSection("text", sectionType);
                      }}
                    >
                      <Plus className="w-4 h-4" />
                    </Button>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {pageData?.layout?.sections
                      ?.find((s: any) => s.type === sectionType)
                      ?.components?.map((component: any, index: number) => (
                        <div
                          key={component.id}
                          className={`border border-gray-200 dark:border-gray-700 rounded-lg p-4 ${
                            selectedComponent === component.id ? 'ring-2 ring-blue-500' : ''
                          }`}
                        >
                          <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center space-x-2">
                              <GripVertical className="w-4 h-4 text-gray-400" />
                              <Badge variant="secondary">{component.type}</Badge>
                            </div>
                            <div className="flex items-center space-x-1">
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => moveComponentUp(component.id, sectionType)}
                                disabled={index === 0}
                                title="Déplacer vers le haut"
                              >
                                <ChevronUp className="w-4 h-4" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => moveComponentDown(component.id, sectionType)}
                                disabled={index === (pageData?.layout?.sections?.find((s: any) => s.type === sectionType)?.components?.length || 0) - 1}
                                title="Déplacer vers le bas"
                              >
                                <ChevronDown className="w-4 h-4" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setSelectedComponent(
                                  selectedComponent === component.id ? null : component.id
                                )}
                                title="Modifier"
                              >
                                <Edit className="w-4 h-4" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => removeComponentFromSection(component.id, sectionType)}
                                title="Supprimer"
                              >
                                <Trash2 className="w-4 h-4" />
                              </Button>
                            </div>
                          </div>

                          {selectedComponent === component.id && (
                            <ComponentEditor
                              componentType={component.type}
                              componentData={component.data}
                              onDataChange={(newData: any) => 
                                updateComponentData(component.id, sectionType, newData)
                              }
                            />
                          )}

                          {selectedComponent !== component.id && (
                            <div className="text-sm text-gray-600 dark:text-gray-400">
                              {component.data?.title || component.data?.content || "Composant configuré"}
                            </div>
                          )}
                        </div>
                      )) || (
                      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                        Aucun composant dans cette section.
                        <br />
                        Glissez un composant depuis la bibliothèque.
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </ScrollArea>
      </div>

      {/* Sidebar droite - Propriétés */}
      <div className="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
            Propriétés
          </h3>
        </div>
        
        <ScrollArea className="h-full p-4">
          {pageData && (
            <div className="space-y-4">
              <div>
                <Label htmlFor="page-title">Titre de la page</Label>
                <Input
                  id="page-title"
                  value={pageData.pageTitle}
                  onChange={(e) => {
                    const updatedPageData = { ...pageData, pageTitle: e.target.value };
                    queryClient.setQueryData(["/api/admin/pages", pageName], updatedPageData);
                  }}
                />
              </div>
              
              <div>
                <Label htmlFor="page-description">Description</Label>
                <Textarea
                  id="page-description"
                  value={pageData.pageDescription || ""}
                  onChange={(e) => {
                    const updatedPageData = { ...pageData, pageDescription: e.target.value };
                    queryClient.setQueryData(["/api/admin/pages", pageName], updatedPageData);
                  }}
                />
              </div>

              <Separator />

              <div>
                <h4 className="font-medium mb-2">Styles de section</h4>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Sélectionnez une section pour modifier ses styles
                </p>
              </div>
            </div>
          )}
        </ScrollArea>
      </div>
    </div>
  );
}

export default PageEditor;