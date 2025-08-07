import { useState } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Plus, Trash2, Move, Upload } from "lucide-react";
import ColorPicker from "./ColorPicker";

interface ComponentEditorProps {
  componentType: string;
  componentData: any;
  onDataChange: (newData: any) => void;
}

export function ComponentEditor({ componentType, componentData, onDataChange }: ComponentEditorProps) {
  const updateField = (field: string, value: any) => {
    onDataChange({ ...componentData, [field]: value });
  };

  const updateNestedField = (parentField: string, index: number, field: string, value: any) => {
    const updatedArray = [...(componentData[parentField] || [])];
    updatedArray[index] = { ...updatedArray[index], [field]: value };
    onDataChange({ ...componentData, [parentField]: updatedArray });
  };

  const addArrayItem = (field: string, defaultItem: any) => {
    const updatedArray = [...(componentData[field] || []), defaultItem];
    onDataChange({ ...componentData, [field]: updatedArray });
  };

  const removeArrayItem = (field: string, index: number) => {
    const updatedArray = (componentData[field] || []).filter((_: any, i: number) => i !== index);
    onDataChange({ ...componentData, [field]: updatedArray });
  };

  const renderHeroEditor = () => (
    <div className="space-y-4">
      <div>
        <Label htmlFor="hero-title">Titre principal</Label>
        <Input
          id="hero-title"
          value={componentData.title || ""}
          onChange={(e) => updateField("title", e.target.value)}
          placeholder="Votre titre accrocheur"
        />
      </div>
      
      <div>
        <Label htmlFor="hero-subtitle">Sous-titre</Label>
        <Input
          id="hero-subtitle"
          value={componentData.subtitle || ""}
          onChange={(e) => updateField("subtitle", e.target.value)}
          placeholder="Sous-titre descriptif"
        />
      </div>
      
      <div>
        <Label htmlFor="hero-description">Description</Label>
        <Textarea
          id="hero-description"
          value={componentData.description || ""}
          onChange={(e) => updateField("description", e.target.value)}
          placeholder="Description détaillée..."
        />
      </div>
      
      <div className="grid grid-cols-2 gap-4">
        <div>
          <Label htmlFor="hero-button-text">Texte du bouton</Label>
          <Input
            id="hero-button-text"
            value={componentData.buttonText || ""}
            onChange={(e) => updateField("buttonText", e.target.value)}
            placeholder="Découvrir"
          />
        </div>
        
        <div>
          <Label htmlFor="hero-button-url">Lien du bouton</Label>
          <Input
            id="hero-button-url"
            value={componentData.buttonUrl || ""}
            onChange={(e) => updateField("buttonUrl", e.target.value)}
            placeholder="#"
          />
        </div>
      </div>
      
      <div>
        <Label>Image de fond</Label>
        <div className="space-y-3">
          <div className="flex items-center space-x-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => document.getElementById('hero-bg-upload')?.click()}
              className="flex items-center space-x-2"
            >
              <Upload className="w-4 h-4" />
              <span>Parcourir</span>
            </Button>
            <Input
              id="hero-bg-upload"
              type="file"
              accept="image/*"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) {
                  const url = URL.createObjectURL(file);
                  updateField("backgroundImage", url);
                }
              }}
              className="hidden"
            />
            <span className="text-xs text-gray-500">ou entrez une URL</span>
          </div>
          
          <Input
            value={componentData.backgroundImage || ""}
            onChange={(e) => updateField("backgroundImage", e.target.value)}
            placeholder="https://exemple.com/image.jpg"
          />
          
          {componentData.backgroundImage && (
            <div 
              className="w-full h-20 rounded border bg-cover bg-center"
              style={{ backgroundImage: `url(${componentData.backgroundImage})` }}
            />
          )}
        </div>
      </div>
      
      <div>
        <ColorPicker
          label="Couleur de fond principale"
          value={componentData.backgroundColor || "#6366f1"}
          onChange={(value) => updateField("backgroundColor", value)}
          allowGradient={true}
          allowImage={true}
        />
      </div>
      
      <div>
        <ColorPicker
          label="Couleur du texte principal"
          value={componentData.textColor || "#ffffff"}
          onChange={(value) => updateField("textColor", value)}
          allowGradient={false}
          allowImage={false}
        />
      </div>
      
      <div>
        <ColorPicker
          label="Couleur du bouton"
          value={componentData.buttonColor || "#ffffff"}
          onChange={(value) => updateField("buttonColor", value)}
          allowGradient={true}
          allowImage={false}
        />
      </div>
      
      <div>
        <Label htmlFor="hero-align">Alignement du texte</Label>
        <Select value={componentData.textAlign || "center"} onValueChange={(value) => updateField("textAlign", value)}>
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="left">Gauche</SelectItem>
            <SelectItem value="center">Centre</SelectItem>
            <SelectItem value="right">Droite</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>
  );

  const renderFeaturesEditor = () => (
    <div className="space-y-4">
      <div>
        <Label htmlFor="features-title">Titre de section</Label>
        <Input
          id="features-title"
          value={componentData.title || ""}
          onChange={(e) => updateField("title", e.target.value)}
          placeholder="Nos fonctionnalités"
        />
      </div>
      
      <div>
        <Label htmlFor="features-subtitle">Sous-titre</Label>
        <Input
          id="features-subtitle"
          value={componentData.subtitle || ""}
          onChange={(e) => updateField("subtitle", e.target.value)}
          placeholder="Découvrez nos avantages"
        />
      </div>

      <Separator />
      
      <div>
        <div className="flex items-center justify-between mb-3">
          <Label>Fonctionnalités</Label>
          <Button
            size="sm"
            onClick={() => addArrayItem("features", {
              icon: "star",
              title: "Nouvelle fonctionnalité",
              description: "Description de la fonctionnalité"
            })}
          >
            <Plus className="w-4 h-4 mr-2" />
            Ajouter
          </Button>
        </div>
        
        {(componentData.features || []).map((feature: any, index: number) => (
          <Card key={index} className="mb-3">
            <CardContent className="p-4">
              <div className="flex items-center justify-between mb-3">
                <span className="text-sm font-medium">Fonctionnalité {index + 1}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => removeArrayItem("features", index)}
                >
                  <Trash2 className="w-4 h-4" />
                </Button>
              </div>
              
              <div className="space-y-3">
                <div>
                  <Label>Icône</Label>
                  <Select
                    value={feature.icon || "star"}
                    onValueChange={(value) => updateNestedField("features", index, "icon", value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="star">Étoile</SelectItem>
                      <SelectItem value="heart">Cœur</SelectItem>
                      <SelectItem value="shield">Bouclier</SelectItem>
                      <SelectItem value="rocket">Fusée</SelectItem>
                      <SelectItem value="trophy">Trophée</SelectItem>
                      <SelectItem value="users">Utilisateurs</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                <div>
                  <Label>Titre</Label>
                  <Input
                    value={feature.title || ""}
                    onChange={(e) => updateNestedField("features", index, "title", e.target.value)}
                    placeholder="Titre de la fonctionnalité"
                  />
                </div>
                
                <div>
                  <Label>Description</Label>
                  <Textarea
                    value={feature.description || ""}
                    onChange={(e) => updateNestedField("features", index, "description", e.target.value)}
                    placeholder="Description de la fonctionnalité"
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );

  const renderStatsEditor = () => (
    <div className="space-y-4">
      <div>
        <Label htmlFor="stats-title">Titre de section</Label>
        <Input
          id="stats-title"
          value={componentData.title || ""}
          onChange={(e) => updateField("title", e.target.value)}
          placeholder="Nos statistiques"
        />
      </div>

      <Separator />
      
      <div>
        <div className="flex items-center justify-between mb-3">
          <Label>Statistiques</Label>
          <Button
            size="sm"
            onClick={() => addArrayItem("stats", {
              number: "100+",
              label: "Nouvelle statistique"
            })}
          >
            <Plus className="w-4 h-4 mr-2" />
            Ajouter
          </Button>
        </div>
        
        {(componentData.stats || []).map((stat: any, index: number) => (
          <Card key={index} className="mb-3">
            <CardContent className="p-4">
              <div className="flex items-center justify-between mb-3">
                <span className="text-sm font-medium">Statistique {index + 1}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => removeArrayItem("stats", index)}
                >
                  <Trash2 className="w-4 h-4" />
                </Button>
              </div>
              
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label>Chiffre</Label>
                  <Input
                    value={stat.number || ""}
                    onChange={(e) => updateNestedField("stats", index, "number", e.target.value)}
                    placeholder="100+"
                  />
                </div>
                
                <div>
                  <Label>Libellé</Label>
                  <Input
                    value={stat.label || ""}
                    onChange={(e) => updateNestedField("stats", index, "label", e.target.value)}
                    placeholder="Utilisateurs"
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );

  const renderTextEditor = () => (
    <div className="space-y-4">
      <div>
        <Label htmlFor="text-content">Contenu</Label>
        <Textarea
          id="text-content"
          value={componentData.content || ""}
          onChange={(e) => updateField("content", e.target.value)}
          placeholder="Votre contenu textuel..."
          rows={6}
        />
      </div>
      
      <div className="grid grid-cols-2 gap-4">
        <div>
          <Label htmlFor="text-align">Alignement</Label>
          <Select value={componentData.textAlign || "left"} onValueChange={(value) => updateField("textAlign", value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="left">Gauche</SelectItem>
              <SelectItem value="center">Centre</SelectItem>
              <SelectItem value="right">Droite</SelectItem>
              <SelectItem value="justify">Justifié</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div>
          <Label htmlFor="text-size">Taille</Label>
          <Select value={componentData.fontSize || "base"} onValueChange={(value) => updateField("fontSize", value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="sm">Petit</SelectItem>
              <SelectItem value="base">Normal</SelectItem>
              <SelectItem value="lg">Grand</SelectItem>
              <SelectItem value="xl">Très grand</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      
      <Separator />
      
      <div>
        <ColorPicker
          label="Couleur du texte"
          value={componentData.textColor || "#000000"}
          onChange={(value) => updateField("textColor", value)}
          allowGradient={false}
          allowImage={false}
        />
      </div>
      
      <div>
        <ColorPicker
          label="Couleur de fond"
          value={componentData.backgroundColor || "transparent"}
          onChange={(value) => updateField("backgroundColor", value)}
          allowGradient={true}
          allowImage={true}
        />
      </div>
    </div>
  );

  const renderImageEditor = () => (
    <div className="space-y-4">
      <div>
        <Label>Image</Label>
        <div className="space-y-3">
          <div className="flex items-center space-x-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => document.getElementById('image-upload')?.click()}
              className="flex items-center space-x-2"
            >
              <Upload className="w-4 h-4" />
              <span>Parcourir</span>
            </Button>
            <Input
              id="image-upload"
              type="file"
              accept="image/*"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) {
                  const url = URL.createObjectURL(file);
                  updateField("src", url);
                }
              }}
              className="hidden"
            />
            <span className="text-xs text-gray-500">ou entrez une URL</span>
          </div>
          
          <Input
            value={componentData.src || ""}
            onChange={(e) => updateField("src", e.target.value)}
            placeholder="https://exemple.com/image.jpg"
          />
          
          {componentData.src && (
            <div className="w-full h-32 rounded border bg-cover bg-center"
                 style={{ backgroundImage: `url(${componentData.src})` }} />
          )}
        </div>
      </div>
      
      <div>
        <Label htmlFor="image-alt">Texte alternatif</Label>
        <Input
          id="image-alt"
          value={componentData.alt || ""}
          onChange={(e) => updateField("alt", e.target.value)}
          placeholder="Description de l'image"
        />
      </div>
      
      <div>
        <Label htmlFor="image-caption">Légende (optionnelle)</Label>
        <Input
          id="image-caption"
          value={componentData.caption || ""}
          onChange={(e) => updateField("caption", e.target.value)}
          placeholder="Légende de l'image"
        />
      </div>
      
      <div className="grid grid-cols-2 gap-4">
        <div>
          <Label htmlFor="image-width">Largeur</Label>
          <Select value={componentData.width || "100%"} onValueChange={(value) => updateField("width", value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="25%">25%</SelectItem>
              <SelectItem value="50%">50%</SelectItem>
              <SelectItem value="75%">75%</SelectItem>
              <SelectItem value="100%">100%</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div>
          <Label htmlFor="image-align">Alignement</Label>
          <Select value={componentData.align || "center"} onValueChange={(value) => updateField("align", value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="left">Gauche</SelectItem>
              <SelectItem value="center">Centre</SelectItem>
              <SelectItem value="right">Droite</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
    </div>
  );

  const renderNavigationEditor = () => (
    <div className="space-y-4">
      <div>
        <Label htmlFor="nav-logo">Logo (URL)</Label>
        <Input
          id="nav-logo"
          value={componentData.logo || ""}
          onChange={(e) => updateField("logo", e.target.value)}
          placeholder="https://exemple.com/logo.png"
        />
      </div>
      
      <div>
        <Label htmlFor="nav-logo-text">Texte du logo</Label>
        <Input
          id="nav-logo-text"
          value={componentData.logoText || ""}
          onChange={(e) => updateField("logoText", e.target.value)}
          placeholder="Nom de votre site"
        />
      </div>

      <Separator />
      
      <div>
        <div className="flex items-center justify-between mb-3">
          <Label>Éléments de menu</Label>
          <Button
            size="sm"
            onClick={() => addArrayItem("menuItems", {
              label: "Nouveau lien",
              url: "#"
            })}
          >
            <Plus className="w-4 h-4 mr-2" />
            Ajouter
          </Button>
        </div>
        
        {(componentData.menuItems || []).map((item: any, index: number) => (
          <Card key={index} className="mb-3">
            <CardContent className="p-4">
              <div className="flex items-center justify-between mb-3">
                <span className="text-sm font-medium">Lien {index + 1}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => removeArrayItem("menuItems", index)}
                >
                  <Trash2 className="w-4 h-4" />
                </Button>
              </div>
              
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label>Libellé</Label>
                  <Input
                    value={item.label || ""}
                    onChange={(e) => updateNestedField("menuItems", index, "label", e.target.value)}
                    placeholder="Accueil"
                  />
                </div>
                
                <div>
                  <Label>URL</Label>
                  <Input
                    value={item.url || ""}
                    onChange={(e) => updateNestedField("menuItems", index, "url", e.target.value)}
                    placeholder="/"
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );

  const renderDefaultEditor = () => (
    <div className="space-y-4">
      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
        <p>Éditeur pour le type de composant "{componentType}" non encore implémenté.</p>
        <p className="text-sm mt-2">Les propriétés de base sont disponibles dans la section Propriétés.</p>
      </div>
    </div>
  );

  const getEditor = () => {
    switch (componentType) {
      case "hero":
        return renderHeroEditor();
      case "features":
        return renderFeaturesEditor();
      case "stats":
        return renderStatsEditor();
      case "text":
        return renderTextEditor();
      case "image":
        return renderImageEditor();
      case "navigation":
        return renderNavigationEditor();
      default:
        return renderDefaultEditor();
    }
  };

  return (
    <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border">
      <div className="mb-4">
        <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-1">
          Édition du composant
        </h4>
        <p className="text-xs text-gray-500 dark:text-gray-400">
          Type: {componentType}
        </p>
      </div>
      
      {getEditor()}
    </div>
  );
}

export default ComponentEditor;