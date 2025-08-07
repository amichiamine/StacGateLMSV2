import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Slider } from "@/components/ui/slider";
import { Upload, Palette, Sparkles, Image } from "lucide-react";

interface ColorPickerProps {
  value: string;
  onChange: (value: string) => void;
  label?: string;
  allowGradient?: boolean;
  allowImage?: boolean;
}

export function ColorPicker({ 
  value, 
  onChange, 
  label = "Couleur", 
  allowGradient = true, 
  allowImage = true 
}: ColorPickerProps) {
  const [colorType, setColorType] = useState<"solid" | "gradient" | "image">("solid");
  const [solidColor, setSolidColor] = useState("#6366f1");
  const [gradientStart, setGradientStart] = useState("#6366f1");
  const [gradientEnd, setGradientEnd] = useState("#8b5cf6");
  const [gradientDirection, setGradientDirection] = useState("to right");
  const [imageUrl, setImageUrl] = useState("");
  const [imageFile, setImageFile] = useState<File | null>(null);

  const presetColors = [
    "#6366f1", "#8b5cf6", "#10b981", "#f59e0b", "#ef4444",
    "#06b6d4", "#84cc16", "#f97316", "#ec4899", "#6b7280",
    "#1f2937", "#374151", "#111827", "#ffffff", "#f3f4f6"
  ];

  const gradientDirections = [
    { value: "to right", label: "Horizontal →" },
    { value: "to left", label: "Horizontal ←" },
    { value: "to bottom", label: "Vertical ↓" },
    { value: "to top", label: "Vertical ↑" },
    { value: "to bottom right", label: "Diagonal ↘" },
    { value: "to bottom left", label: "Diagonal ↙" },
    { value: "to top right", label: "Diagonal ↗" },
    { value: "to top left", label: "Diagonal ↖" },
  ];

  const handleColorTypeChange = (type: "solid" | "gradient" | "image") => {
    setColorType(type);
    
    switch (type) {
      case "solid":
        onChange(solidColor);
        break;
      case "gradient":
        onChange(`linear-gradient(${gradientDirection}, ${gradientStart}, ${gradientEnd})`);
        break;
      case "image":
        if (imageUrl) {
          onChange(`url(${imageUrl})`);
        }
        break;
    }
  };

  const handleSolidColorChange = (color: string) => {
    setSolidColor(color);
    if (colorType === "solid") {
      onChange(color);
    }
  };

  const handleGradientChange = () => {
    if (colorType === "gradient") {
      onChange(`linear-gradient(${gradientDirection}, ${gradientStart}, ${gradientEnd})`);
    }
  };

  const handleImageUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      setImageFile(file);
      
      // Créer une URL temporaire pour l'aperçu
      const tempUrl = URL.createObjectURL(file);
      setImageUrl(tempUrl);
      
      if (colorType === "image") {
        onChange(`url(${tempUrl})`);
      }
      
      // TODO: Ici, vous pourriez uploader le fichier vers votre serveur
      // et obtenir l'URL permanente
    }
  };

  const handleImageUrlChange = (url: string) => {
    setImageUrl(url);
    if (colorType === "image") {
      onChange(`url(${url})`);
    }
  };

  return (
    <div className="space-y-4">
      <Label>{label}</Label>
      
      <Tabs value={colorType} onValueChange={(value) => handleColorTypeChange(value as any)}>
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="solid" className="flex items-center space-x-1">
            <Palette className="w-3 h-3" />
            <span>Couleur</span>
          </TabsTrigger>
          {allowGradient && (
            <TabsTrigger value="gradient" className="flex items-center space-x-1">
              <Sparkles className="w-3 h-3" />
              <span>Dégradé</span>
            </TabsTrigger>
          )}
          {allowImage && (
            <TabsTrigger value="image" className="flex items-center space-x-1">
              <Image className="w-3 h-3" />
              <span>Image</span>
            </TabsTrigger>
          )}
        </TabsList>

        <TabsContent value="solid" className="space-y-3">
          <div className="flex items-center space-x-2">
            <Input
              type="color"
              value={solidColor}
              onChange={(e) => handleSolidColorChange(e.target.value)}
              className="w-16 h-10 p-0 border rounded cursor-pointer"
            />
            <Input
              type="text"
              value={solidColor}
              onChange={(e) => handleSolidColorChange(e.target.value)}
              placeholder="#6366f1"
              className="flex-1"
            />
          </div>
          
          <div className="grid grid-cols-5 gap-2">
            {presetColors.map((color) => (
              <button
                key={color}
                className={`w-8 h-8 rounded border-2 ${
                  solidColor === color ? 'border-gray-400' : 'border-gray-200'
                }`}
                style={{ backgroundColor: color }}
                onClick={() => handleSolidColorChange(color)}
              />
            ))}
          </div>
        </TabsContent>

        {allowGradient && (
          <TabsContent value="gradient" className="space-y-3">
            <div>
              <Label className="text-xs">Direction</Label>
              <Select value={gradientDirection} onValueChange={(value) => {
                setGradientDirection(value);
                handleGradientChange();
              }}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {gradientDirections.map((dir) => (
                    <SelectItem key={dir.value} value={dir.value}>
                      {dir.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            
            <div>
              <Label className="text-xs">Couleur de début</Label>
              <div className="flex items-center space-x-2">
                <Input
                  type="color"
                  value={gradientStart}
                  onChange={(e) => {
                    setGradientStart(e.target.value);
                    handleGradientChange();
                  }}
                  className="w-16 h-8 p-0 border rounded cursor-pointer"
                />
                <Input
                  type="text"
                  value={gradientStart}
                  onChange={(e) => {
                    setGradientStart(e.target.value);
                    handleGradientChange();
                  }}
                  className="flex-1"
                />
              </div>
            </div>
            
            <div>
              <Label className="text-xs">Couleur de fin</Label>
              <div className="flex items-center space-x-2">
                <Input
                  type="color"
                  value={gradientEnd}
                  onChange={(e) => {
                    setGradientEnd(e.target.value);
                    handleGradientChange();
                  }}
                  className="w-16 h-8 p-0 border rounded cursor-pointer"
                />
                <Input
                  type="text"
                  value={gradientEnd}
                  onChange={(e) => {
                    setGradientEnd(e.target.value);
                    handleGradientChange();
                  }}
                  className="flex-1"
                />
              </div>
            </div>
            
            {/* Aperçu du dégradé */}
            <div 
              className="w-full h-8 rounded border"
              style={{ 
                background: `linear-gradient(${gradientDirection}, ${gradientStart}, ${gradientEnd})` 
              }}
            />
          </TabsContent>
        )}

        {allowImage && (
          <TabsContent value="image" className="space-y-3">
            <div>
              <Label className="text-xs">Upload d'image</Label>
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
                  onChange={handleImageUpload}
                  className="hidden"
                />
                {imageFile && (
                  <span className="text-xs text-gray-500 truncate">
                    {imageFile.name}
                  </span>
                )}
              </div>
            </div>
            
            <div>
              <Label className="text-xs">Ou URL d'image</Label>
              <Input
                type="url"
                value={imageUrl}
                onChange={(e) => handleImageUrlChange(e.target.value)}
                placeholder="https://exemple.com/image.jpg"
              />
            </div>
            
            {imageUrl && (
              <div className="space-y-2">
                <Label className="text-xs">Aperçu</Label>
                <div 
                  className="w-full h-20 rounded border bg-cover bg-center"
                  style={{ backgroundImage: `url(${imageUrl})` }}
                />
              </div>
            )}
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}

export default ColorPicker;