import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { useToast } from "@/hooks/use-toast";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";
import { 
  Download, 
  RefreshCw, 
  Settings,
  CheckCircle,
  AlertCircle,
  Info,
  Clock,
  Loader2,
  Shield,
  Database,
  Code,
  Zap
} from "lucide-react";
import { format } from "date-fns";
import { fr } from "date-fns/locale";

interface SystemUpdate {
  id: string;
  version: string;
  title: string;
  description: string;
  type: 'security' | 'feature' | 'bugfix' | 'critical';
  size: string;
  releaseDate: string;
  status: 'available' | 'downloading' | 'installing' | 'installed' | 'failed';
  progress?: number;
  changelog?: string[];
  requiredRestart: boolean;
}

interface SystemInfo {
  currentVersion: string;
  lastUpdateCheck: string;
  nextScheduledUpdate: string;
  autoUpdatesEnabled: boolean;
  maintenanceMode: boolean;
  backupStatus: string;
}

export default function SystemUpdatesPage() {
  const { toast } = useToast();
  const [selectedUpdate, setSelectedUpdate] = useState<SystemUpdate | null>(null);

  // Fetch system status from API
  const { data: systemInfo, isLoading: systemLoading } = useQuery({
    queryKey: ['/api/system/status'],
  });

  // Fetch active system version
  const { data: activeVersion } = useQuery({
    queryKey: ['/api/system/version/active'],
  });

  // Fetch maintenance status
  const { data: maintenanceStatus } = useQuery({
    queryKey: ['/api/system/maintenance'],
  });

  // Fetch available system versions (updates)
  const { data: systemVersions, isLoading: versionsLoading } = useQuery({
    queryKey: ['/api/system/versions'],
  });

  // Convert API data to SystemUpdate format
  const availableUpdates: SystemUpdate[] = systemVersions ? systemVersions.filter(
    (version: any) => !version.isActive
  ).map((version: any) => ({
    id: version.id,
    version: version.version,
    title: version.title,
    description: version.description,
    type: 'feature',
    size: '50 MB',
    releaseDate: version.releaseDate,
    status: 'available',
    requiredRestart: true,
    changelog: version.changelogMarkdown?.split('\n').filter((line: string) => line.trim()) || []
  })) : [
    {
      id: "update-1",
      version: "2.1.5",
      title: "Correctifs de sécurité critiques",
      description: "Mise à jour de sécurité importante incluant des correctifs pour plusieurs vulnérabilités découvertes.",
      type: "security",
      size: "45 MB",
      releaseDate: "2025-08-06T10:00:00Z",
      status: "available",
      requiredRestart: true,
      changelog: [
        "Correction de la vulnérabilité CVE-2025-001",
        "Amélioration du système d'authentification",
        "Mise à jour des dépendances de sécurité",
        "Renforcement du chiffrement des sessions"
      ]
    },
    {
      id: "update-2",
      version: "2.2.0",
      title: "Nouvelles fonctionnalités et améliorations",
      description: "Ajout de nouvelles fonctionnalités pour l'édition collaborative et l'amélioration des performances.",
      type: "feature",
      size: "120 MB",
      releaseDate: "2025-08-05T14:00:00Z",
      status: "available",
      requiredRestart: false,
      changelog: [
        "Éditeur collaboratif en temps réel",
        "Nouveau système de notifications push",
        "Amélioration des performances de 30%",
        "Interface utilisateur modernisée",
        "Support des thèmes personnalisés avancés"
      ]
    }
  ];

  const checkForUpdatesMutation = useMutation({
    mutationFn: async () => {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 2000));
      return { updatesFound: 1 };
    },
    onSuccess: (data) => {
      toast({
        title: "Vérification terminée",
        description: `${data.updatesFound} mise(s) à jour trouvée(s)`
      });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de vérifier les mises à jour",
        variant: "destructive"
      });
    }
  });

  const installUpdateMutation = useMutation({
    mutationFn: async (updateId: string) => {
      const response = await fetch('/api/system/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ updateId }),
      });
      if (!response.ok) throw new Error('Failed to install update');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Installation réussie",
        description: "La mise à jour a été installée avec succès"
      });
    },
    onError: () => {
      toast({
        title: "Erreur d'installation",
        description: "Échec de l'installation de la mise à jour",
        variant: "destructive"
      });
    }
  });

  const getUpdateTypeIcon = (type: SystemUpdate['type']) => {
    switch (type) {
      case 'security':
        return <Shield className="h-4 w-4 text-red-500" />;
      case 'feature':
        return <Zap className="h-4 w-4 text-blue-500" />;
      case 'bugfix':
        return <Settings className="h-4 w-4 text-yellow-500" />;
      case 'critical':
        return <AlertCircle className="h-4 w-4 text-red-600" />;
      default:
        return <Info className="h-4 w-4 text-gray-500" />;
    }
  };

  const getUpdateTypeBadge = (type: SystemUpdate['type']) => {
    const variants = {
      security: 'destructive',
      feature: 'default',
      bugfix: 'secondary',
      critical: 'destructive'
    } as const;

    const labels = {
      security: 'Sécurité',
      feature: 'Fonctionnalité',
      bugfix: 'Correction',
      critical: 'Critique'
    };

    return (
      <Badge variant={variants[type]} className="flex items-center gap-1">
        {getUpdateTypeIcon(type)}
        {labels[type]}
      </Badge>
    );
  };

  return (
    <div className="space-y-6 p-6" data-testid="system-updates-page">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold flex items-center gap-2">
            <RefreshCw className="h-6 w-6" />
            Mises à jour système
          </h1>
          <p className="text-muted-foreground">
            Gérez les mises à jour et la maintenance de la plateforme
          </p>
        </div>

        <Button
          onClick={() => checkForUpdatesMutation.mutate()}
          disabled={checkForUpdatesMutation.isPending}
          data-testid="button-check-updates"
        >
          {checkForUpdatesMutation.isPending ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Vérification...
            </>
          ) : (
            <>
              <RefreshCw className="mr-2 h-4 w-4" />
              Vérifier les mises à jour
            </>
          )}
        </Button>
      </div>

      {/* System Status */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Version actuelle</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {activeVersion?.version || systemInfo?.currentVersion || "1.0.0"}
            </div>
            <p className="text-xs text-muted-foreground">
              Dernière vérification: {systemInfo?.lastUpdateCheck 
                ? format(new Date(systemInfo.lastUpdateCheck), 'dd MMM yyyy à HH:mm', { locale: fr })
                : 'Jamais'
              }
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Statut système</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              {maintenanceStatus?.isMaintenance ? (
                <>
                  <AlertCircle className="h-5 w-5 text-yellow-500" />
                  <span className="font-medium">Maintenance</span>
                </>
              ) : (
                <>
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span className="font-medium">Opérationnel</span>
                </>
              )}
            </div>
            <p className="text-xs text-muted-foreground">
              Sauvegarde: {systemInfo?.backupStatus || 'En attente'}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Prochaine maintenance</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Clock className="h-5 w-5 text-blue-500" />
              <span className="font-medium">
                {systemInfo?.nextScheduledUpdate 
                  ? format(new Date(systemInfo.nextScheduledUpdate), 'dd MMM', { locale: fr })
                  : 'Non programmée'
                }
              </span>
            </div>
            <p className="text-xs text-muted-foreground">02:00 - 04:00</p>
          </CardContent>
        </Card>
      </div>

      {/* Available Updates */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Mises à jour disponibles</CardTitle>
            <CardDescription>
              {availableUpdates.length} mise(s) à jour en attente
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ScrollArea className="h-[400px]">
              <div className="space-y-4">
                {availableUpdates.map((update) => (
                  <div
                    key={update.id}
                    className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                      selectedUpdate?.id === update.id ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'
                    }`}
                    onClick={() => setSelectedUpdate(update)}
                    data-testid={`update-${update.id}`}
                  >
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <span className="font-medium">v{update.version}</span>
                        {getUpdateTypeBadge(update.type)}
                      </div>
                      <span className="text-sm text-muted-foreground">{update.size}</span>
                    </div>
                    
                    <h4 className="font-medium mb-1">{update.title}</h4>
                    <p className="text-sm text-muted-foreground mb-3">{update.description}</p>
                    
                    <div className="flex items-center justify-between">
                      <span className="text-xs text-muted-foreground">
                        {format(new Date(update.releaseDate), 'dd MMM yyyy', { locale: fr })}
                      </span>
                      
                      <Button
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation();
                          installUpdateMutation.mutate(update.id);
                        }}
                        disabled={installUpdateMutation.isPending}
                        data-testid={`install-${update.id}`}
                      >
                        {installUpdateMutation.isPending ? (
                          <>
                            <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                            Installation...
                          </>
                        ) : (
                          <>
                            <Download className="mr-1 h-3 w-3" />
                            Installer
                          </>
                        )}
                      </Button>
                    </div>

                    {update.requiredRestart && (
                      <div className="mt-2 text-xs text-amber-600 flex items-center gap-1">
                        <AlertCircle className="h-3 w-3" />
                        Redémarrage requis
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </ScrollArea>
          </CardContent>
        </Card>

        {/* Update Details */}
        <Card>
          <CardHeader>
            <CardTitle>Détails de la mise à jour</CardTitle>
            <CardDescription>
              {selectedUpdate ? `Version ${selectedUpdate.version}` : 'Sélectionnez une mise à jour'}
            </CardDescription>
          </CardHeader>
          <CardContent>
            {selectedUpdate ? (
              <div className="space-y-4">
                <div>
                  <h4 className="font-medium mb-2">{selectedUpdate.title}</h4>
                  <p className="text-sm text-muted-foreground mb-4">{selectedUpdate.description}</p>
                  
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="font-medium">Version:</span>
                      <div>{selectedUpdate.version}</div>
                    </div>
                    <div>
                      <span className="font-medium">Taille:</span>
                      <div>{selectedUpdate.size}</div>
                    </div>
                    <div>
                      <span className="font-medium">Type:</span>
                      <div>{getUpdateTypeBadge(selectedUpdate.type)}</div>
                    </div>
                    <div>
                      <span className="font-medium">Redémarrage:</span>
                      <div>{selectedUpdate.requiredRestart ? 'Requis' : 'Non requis'}</div>
                    </div>
                  </div>
                </div>

                <Separator />

                <div>
                  <h5 className="font-medium mb-2">Notes de version</h5>
                  <ul className="space-y-1 text-sm">
                    {selectedUpdate.changelog?.map((item, index) => (
                      <li key={index} className="flex items-start gap-2">
                        <CheckCircle className="h-3 w-3 text-green-500 mt-0.5 flex-shrink-0" />
                        {item}
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            ) : (
              <div className="text-center text-muted-foreground py-8">
                <Database className="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>Sélectionnez une mise à jour pour voir les détails</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Maintenance Settings */}
      <Card>
        <CardHeader>
          <CardTitle>Paramètres de maintenance</CardTitle>
          <CardDescription>
            Configuration des mises à jour automatiques et de la maintenance
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">Mises à jour automatiques</h4>
                  <p className="text-sm text-muted-foreground">
                    Installer automatiquement les mises à jour de sécurité
                  </p>
                </div>
                <Button variant="outline" size="sm">
                  {systemInfo.autoUpdatesEnabled ? 'Activé' : 'Désactivé'}
                </Button>
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">Mode maintenance</h4>
                  <p className="text-sm text-muted-foreground">
                    Activer le mode maintenance pour les mises à jour
                  </p>
                </div>
                <Button variant="outline" size="sm">
                  {systemInfo.maintenanceMode ? 'Activé' : 'Désactivé'}
                </Button>
              </div>
            </div>

            <div className="space-y-4">
              <div>
                <h4 className="font-medium mb-2">Fenêtre de maintenance</h4>
                <p className="text-sm text-muted-foreground mb-2">
                  Les mises à jour seront installées pendant cette période
                </p>
                <div className="text-sm">
                  <strong>Tous les jours: 02:00 - 04:00</strong>
                </div>
              </div>

              <Button variant="outline" className="w-full">
                <Settings className="mr-2 h-4 w-4" />
                Configurer les paramètres
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}