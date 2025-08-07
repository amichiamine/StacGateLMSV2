import { useState, useRef } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useAuth } from "@/hooks/useAuth";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";
import { useToast } from "@/hooks/use-toast";
import { 
  Layout,
  Type,
  Image,
  Video,
  FileText,
  List,
  Quote,
  Code,
  Calendar,
  BarChart,
  Users,
  MapPin,
  Link,
  Save,
  Eye,
  Undo,
  Redo,
  Settings,
  Plus,
  Trash2,
  GripVertical,
  MousePointer,
  Palette
} from "lucide-react";

interface WysiwygComponent {
  id: string;
  type: string;
  category: string;
  label: string;
  icon: any;
  defaultProps: any;
  template: string;
}

interface PageSection {
  id: string;
  type: string;
  content: any;
  styles: any;
}

interface PageLayout {
  id: string;
  name: string;
  sections: PageSection[];
  settings: any;
}

const componentCategories = [
  {
    id: 'layout',
    name: 'Mise en page',
    components: [
      { id: 'container', type: 'container', label: 'Conteneur', icon: Layout },
      { id: 'grid', type: 'grid', label: 'Grille', icon: Layout },
      { id: 'columns', type: 'columns', label: 'Colonnes', icon: Layout },
      { id: 'hero', type: 'hero', label: 'Section héro', icon: Layout }
    ]
  },
  {
    id: 'content',
    name: 'Contenu',
    components: [
      { id: 'heading', type: 'heading', label: 'Titre', icon: Type },
      { id: 'paragraph', type: 'paragraph', label: 'Paragraphe', icon: FileText },
      { id: 'list', type: 'list', label: 'Liste', icon: List },
      { id: 'quote', type: 'quote', label: 'Citation', icon: Quote }
    ]
  },
  {
    id: 'media',
    name: 'Médias',
    components: [
      { id: 'image', type: 'image', label: 'Image', icon: Image },
      { id: 'video', type: 'video', label: 'Vidéo', icon: Video },
      { id: 'gallery', type: 'gallery', label: 'Galerie', icon: Image },
      { id: 'carousel', type: 'carousel', label: 'Carrousel', icon: Image }
    ]
  },
  {
    id: 'interactive',
    name: 'Interactif',
    components: [
      { id: 'button', type: 'button', label: 'Bouton', icon: MousePointer },
      { id: 'form', type: 'form', label: 'Formulaire', icon: FileText },
      { id: 'search', type: 'search', label: 'Recherche', icon: FileText },
      { id: 'navigation', type: 'navigation', label: 'Navigation', icon: Link }
    ]
  },
  {
    id: 'data',
    name: 'Données',
    components: [
      { id: 'chart', type: 'chart', label: 'Graphique', icon: BarChart },
      { id: 'table', type: 'table', label: 'Tableau', icon: List },
      { id: 'stats', type: 'stats', label: 'Statistiques', icon: BarChart },
      { id: 'calendar', type: 'calendar', label: 'Calendrier', icon: Calendar }
    ]
  },
  {
    id: 'advanced',
    name: 'Avancé',
    components: [
      { id: 'code', type: 'code', label: 'Code', icon: Code },
      { id: 'embed', type: 'embed', label: 'Intégration', icon: Code },
      { id: 'map', type: 'map', label: 'Carte', icon: MapPin },
      { id: 'custom', type: 'custom', label: 'Personnalisé', icon: Settings }
    ]
  }
];

export default function WysiwygEditorPage() {
  const { user } = useAuth();
  const { toast } = useToast();
  const [selectedComponent, setSelectedComponent] = useState<WysiwygComponent | null>(null);
  const [activeCategory, setActiveCategory] = useState('layout');
  const [currentLayout, setCurrentLayout] = useState<PageLayout>({
    id: 'page-1',
    name: 'Page d\'accueil',
    sections: [],
    settings: {
      theme: 'default',
      layout: 'full-width',
      backgroundColor: '#ffffff'
    }
  });
  const [previewMode, setPreviewMode] = useState(false);
  const [history, setHistory] = useState<PageLayout[]>([currentLayout]);
  const [historyIndex, setHistoryIndex] = useState(0);

  // Fetch available pages
  const { data: pages = [] } = useQuery({
    queryKey: ['/api/wysiwyg/pages'],
    enabled: !!user
  });

  // Save page mutation
  const savePageMutation = useMutation({
    mutationFn: async (pageData: PageLayout) => {
      const response = await fetch('/api/wysiwyg/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(pageData)
      });
      if (!response.ok) throw new Error('Erreur lors de la sauvegarde');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Page sauvegardée",
        description: "La page a été sauvegardée avec succès"
      });
    },
    onError: (error: Error) => {
      toast({
        title: "Erreur de sauvegarde",
        description: error.message,
        variant: "destructive"
      });
    }
  });

  const addComponent = (componentType: string) => {
    const newSection: PageSection = {
      id: `section-${Date.now()}`,
      type: componentType,
      content: getDefaultContent(componentType),
      styles: getDefaultStyles(componentType)
    };

    const newLayout = {
      ...currentLayout,
      sections: [...currentLayout.sections, newSection]
    };

    updateLayout(newLayout);
    toast({
      title: "Composant ajouté",
      description: `Le composant ${componentType} a été ajouté à la page`
    });
  };

  const removeSection = (sectionId: string) => {
    const newLayout = {
      ...currentLayout,
      sections: currentLayout.sections.filter(s => s.id !== sectionId)
    };
    updateLayout(newLayout);
  };

  const updateLayout = (newLayout: PageLayout) => {
    setCurrentLayout(newLayout);
    const newHistory = history.slice(0, historyIndex + 1);
    newHistory.push(newLayout);
    setHistory(newHistory);
    setHistoryIndex(newHistory.length - 1);
  };

  const undo = () => {
    if (historyIndex > 0) {
      setHistoryIndex(historyIndex - 1);
      setCurrentLayout(history[historyIndex - 1]);
    }
  };

  const redo = () => {
    if (historyIndex < history.length - 1) {
      setHistoryIndex(historyIndex + 1);
      setCurrentLayout(history[historyIndex + 1]);
    }
  };

  const getDefaultContent = (type: string) => {
    const defaults: Record<string, any> = {
      heading: { text: 'Nouveau titre', level: 'h2' },
      paragraph: { text: 'Nouveau paragraphe de texte.' },
      image: { src: '', alt: 'Image', caption: '' },
      button: { text: 'Bouton', url: '#', variant: 'primary' },
      container: { children: [] }
    };
    return defaults[type] || {};
  };

  const getDefaultStyles = (type: string) => {
    const defaults: Record<string, any> = {
      heading: { fontSize: '24px', color: '#000000', textAlign: 'left' },
      paragraph: { fontSize: '16px', color: '#333333', lineHeight: '1.6' },
      image: { width: '100%', height: 'auto', borderRadius: '0px' },
      button: { padding: '12px 24px', backgroundColor: '#007bff', color: '#ffffff' },
      container: { padding: '20px', margin: '10px 0' }
    };
    return defaults[type] || {};
  };

  const renderSection = (section: PageSection) => {
    const { type, content, styles } = section;
    
    switch (type) {
      case 'heading':
        return (
          <div style={styles} className="editable-section" data-section-id={section.id}>
            {content.level === 'h1' && <h1>{content.text}</h1>}
            {content.level === 'h2' && <h2>{content.text}</h2>}
            {content.level === 'h3' && <h3>{content.text}</h3>}
          </div>
        );
      case 'paragraph':
        return (
          <div style={styles} className="editable-section" data-section-id={section.id}>
            <p>{content.text}</p>
          </div>
        );
      case 'image':
        return (
          <div style={styles} className="editable-section" data-section-id={section.id}>
            <img src={content.src || '/api/placeholder/400/300'} alt={content.alt} style={{ width: '100%' }} />
            {content.caption && <p className="text-sm text-gray-600 mt-2">{content.caption}</p>}
          </div>
        );
      case 'button':
        return (
          <div style={styles} className="editable-section" data-section-id={section.id}>
            <button style={content.styles} className="px-4 py-2 rounded">
              {content.text}
            </button>
          </div>
        );
      default:
        return (
          <div style={styles} className="editable-section border-2 border-dashed border-gray-300 p-4" data-section-id={section.id}>
            <p className="text-gray-500">Composant {type}</p>
          </div>
        );
    }
  };

  return (
    <div className="h-screen flex bg-background" data-testid="wysiwyg-editor">
      {/* Component Sidebar */}
      {!previewMode && (
        <div className="w-80 border-r bg-card flex flex-col">
          <div className="p-4 border-b">
            <h2 className="font-semibold text-lg">Composants</h2>
            <p className="text-sm text-muted-foreground">Glissez-déposez pour ajouter</p>
          </div>

          <ScrollArea className="flex-1">
            <div className="p-4 space-y-4">
              {componentCategories.map((category) => (
                <div key={category.id}>
                  <h3 className="font-medium mb-2 text-sm uppercase tracking-wide text-muted-foreground">
                    {category.name}
                  </h3>
                  <div className="grid grid-cols-2 gap-2">
                    {category.components.map((component) => (
                      <Button
                        key={component.id}
                        variant="outline"
                        size="sm"
                        className="h-auto p-3 flex flex-col items-center gap-1"
                        onClick={() => addComponent(component.type)}
                        data-testid={`component-${component.id}`}
                      >
                        <component.icon className="h-4 w-4" />
                        <span className="text-xs">{component.label}</span>
                      </Button>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </ScrollArea>
        </div>
      )}

      {/* Main Editor Area */}
      <div className="flex-1 flex flex-col">
        {/* Toolbar */}
        <div className="border-b bg-card p-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                variant="outline"
                onClick={undo}
                disabled={historyIndex === 0}
                data-testid="button-undo"
              >
                <Undo className="h-4 w-4" />
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={redo}
                disabled={historyIndex === history.length - 1}
                data-testid="button-redo"
              >
                <Redo className="h-4 w-4" />
              </Button>
              <Separator orientation="vertical" className="h-6" />
              <Button
                size="sm"
                variant={previewMode ? "default" : "outline"}
                onClick={() => setPreviewMode(!previewMode)}
                data-testid="button-preview"
              >
                <Eye className="h-4 w-4 mr-1" />
                {previewMode ? 'Édition' : 'Aperçu'}
              </Button>
            </div>

            <div className="flex items-center gap-2">
              <Input
                value={currentLayout.name}
                onChange={(e) => setCurrentLayout(prev => ({ ...prev, name: e.target.value }))}
                className="w-48"
                placeholder="Nom de la page"
                data-testid="input-page-name"
              />
              <Button
                onClick={() => savePageMutation.mutate(currentLayout)}
                disabled={savePageMutation.isPending}
                data-testid="button-save"
              >
                <Save className="h-4 w-4 mr-1" />
                Sauvegarder
              </Button>
            </div>
          </div>
        </div>

        {/* Canvas */}
        <div className="flex-1 overflow-auto bg-gray-50">
          <div className="max-w-6xl mx-auto p-6">
            <div className="bg-white min-h-[600px] shadow-lg rounded-lg overflow-hidden">
              {currentLayout.sections.length === 0 ? (
                <div className="flex items-center justify-center h-96 text-gray-500">
                  <div className="text-center">
                    <Layout className="h-12 w-12 mx-auto mb-4 opacity-50" />
                    <p className="text-lg font-medium">Page vide</p>
                    <p className="text-sm">Ajoutez des composants depuis la barre latérale</p>
                  </div>
                </div>
              ) : (
                <div className="relative">
                  {currentLayout.sections.map((section) => (
                    <div key={section.id} className="relative group">
                      {!previewMode && (
                        <div className="absolute -top-2 -right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              variant="secondary"
                              className="h-6 w-6 p-0"
                              data-testid={`edit-${section.id}`}
                            >
                              <Settings className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="h-6 w-6 p-0"
                              onClick={() => removeSection(section.id)}
                              data-testid={`delete-${section.id}`}
                            >
                              <Trash2 className="h-3 w-3" />
                            </Button>
                          </div>
                        </div>
                      )}
                      {renderSection(section)}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Properties Panel */}
      {!previewMode && selectedComponent && (
        <div className="w-80 border-l bg-card">
          <div className="p-4 border-b">
            <h3 className="font-semibold">Propriétés</h3>
            <p className="text-sm text-muted-foreground">
              {selectedComponent.label}
            </p>
          </div>
          <ScrollArea className="h-[calc(100vh-120px)]">
            <div className="p-4 space-y-4">
              {/* Component-specific properties would go here */}
              <div>
                <Label>Style</Label>
                <Select>
                  <SelectTrigger>
                    <SelectValue placeholder="Sélectionner un style" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="default">Par défaut</SelectItem>
                    <SelectItem value="modern">Moderne</SelectItem>
                    <SelectItem value="classic">Classique</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              
              <div>
                <Label>Couleur d'arrière-plan</Label>
                <Input type="color" defaultValue="#ffffff" />
              </div>
              
              <div>
                <Label>Espacement</Label>
                <Input type="number" placeholder="20" />
              </div>
            </div>
          </ScrollArea>
        </div>
      )}
    </div>
  );
}