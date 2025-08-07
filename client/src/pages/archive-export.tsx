import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useAuth } from "@/hooks/useAuth";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { ScrollArea } from "@/components/ui/scroll-area";
import { 
  Download, 
  Archive, 
  FileText, 
  Database,
  Calendar,
  Users,
  GraduationCap,
  Loader2,
  CheckCircle,
  AlertCircle
} from "lucide-react";
import { format } from "date-fns";
import { fr } from "date-fns/locale";

interface ExportJob {
  id: string;
  type: 'zip' | 'pdf' | 'csv' | 'sql';
  status: 'pending' | 'processing' | 'completed' | 'failed';
  progress: number;
  filename: string;
  createdAt: string;
  completedAt?: string;
  downloadUrl?: string;
  error?: string;
}

interface ExportRequest {
  type: 'zip' | 'pdf' | 'csv' | 'sql';
  dateRange: {
    start: string;
    end: string;
  };
  includeData: {
    courses: boolean;
    users: boolean;
    assessments: boolean;
    results: boolean;
    content: boolean;
  };
  format?: string;
}

export default function ArchiveExportPage() {
  const { user } = useAuth();
  const { toast } = useToast();
  const [exportForm, setExportForm] = useState<ExportRequest>({
    type: 'zip',
    dateRange: {
      start: '',
      end: ''
    },
    includeData: {
      courses: true,
      users: true,
      assessments: true,
      results: true,
      content: false
    }
  });

  // Fetch export jobs
  const { data: exportJobs = [], refetch } = useQuery({
    queryKey: ['/api/export/jobs'],
    refetchInterval: 5000 // Poll every 5 seconds for updates
  }) as { data: ExportJob[], refetch: () => void };

  // Create export mutation
  const createExportMutation = useMutation({
    mutationFn: async (request: ExportRequest) => {
      const response = await fetch('/api/export/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
      if (!response.ok) throw new Error('Erreur lors de la création de l\'export');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Export créé",
        description: "L'export a été ajouté à la file d'attente"
      });
      refetch();
    },
    onError: (error: Error) => {
      toast({
        title: "Erreur",
        description: error.message,
        variant: "destructive"
      });
    }
  });

  const handleCreateExport = () => {
    if (!exportForm.dateRange.start || !exportForm.dateRange.end) {
      toast({
        title: "Erreur",
        description: "Veuillez sélectionner une période",
        variant: "destructive"
      });
      return;
    }

    const hasData = Object.values(exportForm.includeData).some(v => v);
    if (!hasData) {
      toast({
        title: "Erreur", 
        description: "Veuillez sélectionner au moins un type de données",
        variant: "destructive"
      });
      return;
    }

    createExportMutation.mutate(exportForm);
  };

  const downloadFile = async (job: ExportJob) => {
    try {
      const response = await fetch(`/api/export/download/${job.id}`);
      if (!response.ok) throw new Error('Erreur lors du téléchargement');
      
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = job.filename;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      toast({
        title: "Erreur",
        description: "Impossible de télécharger le fichier",
        variant: "destructive"
      });
    }
  };

  const getStatusIcon = (status: ExportJob['status']) => {
    switch (status) {
      case 'pending':
        return <Calendar className="h-4 w-4 text-yellow-500" />;
      case 'processing':
        return <Loader2 className="h-4 w-4 text-blue-500 animate-spin" />;
      case 'completed':
        return <CheckCircle className="h-4 w-4 text-green-500" />;
      case 'failed':
        return <AlertCircle className="h-4 w-4 text-red-500" />;
    }
  };

  const getStatusText = (status: ExportJob['status']) => {
    switch (status) {
      case 'pending':
        return 'En attente';
      case 'processing':
        return 'En cours';
      case 'completed':
        return 'Terminé';
      case 'failed':
        return 'Échoué';
    }
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'zip':
        return <Archive className="h-4 w-4" />;
      case 'pdf':
        return <FileText className="h-4 w-4" />;
      case 'csv':
        return <FileText className="h-4 w-4" />;
      case 'sql':
        return <Database className="h-4 w-4" />;
      default:
        return <FileText className="h-4 w-4" />;
    }
  };

  return (
    <div className="space-y-6 p-6" data-testid="archive-export-page">
      <div className="flex items-center gap-2">
        <Archive className="h-6 w-6" />
        <h1 className="text-2xl font-bold">Archivage et Export</h1>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Export Form */}
        <Card>
          <CardHeader>
            <CardTitle>Créer un nouvel export</CardTitle>
            <CardDescription>
              Sélectionnez les données à archiver et le format d'export
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Export Type */}
            <div className="space-y-2">
              <Label>Type d'export</Label>
              <Select 
                value={exportForm.type} 
                onValueChange={(value: any) => setExportForm(prev => ({ ...prev, type: value }))}
                data-testid="select-export-type"
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="zip">Archive ZIP (tous fichiers)</SelectItem>
                  <SelectItem value="pdf">Rapport PDF</SelectItem>
                  <SelectItem value="csv">Données CSV</SelectItem>
                  <SelectItem value="sql">Sauvegarde SQL</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Date Range */}
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Date de début</Label>
                <Input
                  type="date"
                  value={exportForm.dateRange.start}
                  onChange={(e) => setExportForm(prev => ({
                    ...prev,
                    dateRange: { ...prev.dateRange, start: e.target.value }
                  }))}
                  data-testid="input-start-date"
                />
              </div>
              <div className="space-y-2">
                <Label>Date de fin</Label>
                <Input
                  type="date"
                  value={exportForm.dateRange.end}
                  onChange={(e) => setExportForm(prev => ({
                    ...prev,
                    dateRange: { ...prev.dateRange, end: e.target.value }
                  }))}
                  data-testid="input-end-date"
                />
              </div>
            </div>

            {/* Data Selection */}
            <div className="space-y-3">
              <Label>Données à inclure</Label>
              <div className="space-y-2">
                {[
                  { key: 'courses', label: 'Cours et formations', icon: GraduationCap },
                  { key: 'users', label: 'Utilisateurs', icon: Users },
                  { key: 'assessments', label: 'Évaluations', icon: FileText },
                  { key: 'results', label: 'Résultats', icon: CheckCircle },
                  { key: 'content', label: 'Contenus multimédias', icon: Archive }
                ].map((item) => (
                  <div key={item.key} className="flex items-center space-x-2">
                    <Checkbox
                      id={item.key}
                      checked={exportForm.includeData[item.key as keyof typeof exportForm.includeData]}
                      onCheckedChange={(checked) => setExportForm(prev => ({
                        ...prev,
                        includeData: { ...prev.includeData, [item.key]: checked }
                      }))}
                      data-testid={`checkbox-${item.key}`}
                    />
                    <Label 
                      htmlFor={item.key} 
                      className="flex items-center gap-2 text-sm font-normal cursor-pointer"
                    >
                      <item.icon className="h-4 w-4" />
                      {item.label}
                    </Label>
                  </div>
                ))}
              </div>
            </div>

            <Button
              onClick={handleCreateExport}
              disabled={createExportMutation.isPending}
              className="w-full"
              data-testid="button-create-export"
            >
              {createExportMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Création en cours...
                </>
              ) : (
                <>
                  <Download className="mr-2 h-4 w-4" />
                  Créer l'export
                </>
              )}
            </Button>
          </CardContent>
        </Card>

        {/* Export Jobs List */}
        <Card>
          <CardHeader>
            <CardTitle>Historique des exports</CardTitle>
            <CardDescription>
              Suivez l'état de vos exports et téléchargez les fichiers
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ScrollArea className="h-[400px]">
              <div className="space-y-3">
                {exportJobs.length === 0 ? (
                  <p className="text-center text-muted-foreground py-8">
                    Aucun export en cours
                  </p>
                ) : (
                  exportJobs.map((job) => (
                    <div 
                      key={job.id}
                      className="border rounded-lg p-4 space-y-3"
                      data-testid={`export-job-${job.id}`}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          {getTypeIcon(job.type)}
                          <span className="font-medium">{job.filename}</span>
                        </div>
                        <Badge variant={job.status === 'completed' ? 'default' : 'secondary'}>
                          {getStatusIcon(job.status)}
                          <span className="ml-1">{getStatusText(job.status)}</span>
                        </Badge>
                      </div>

                      {job.status === 'processing' && (
                        <div className="space-y-1">
                          <div className="flex justify-between text-sm">
                            <span>Progression</span>
                            <span>{job.progress}%</span>
                          </div>
                          <Progress value={job.progress} className="h-2" />
                        </div>
                      )}

                      <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                          Créé le {format(new Date(job.createdAt), 'dd MMM yyyy à HH:mm', { locale: fr })}
                        </span>
                        {job.status === 'completed' && (
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => downloadFile(job)}
                            data-testid={`button-download-${job.id}`}
                          >
                            <Download className="mr-1 h-3 w-3" />
                            Télécharger
                          </Button>
                        )}
                      </div>

                      {job.error && (
                        <div className="bg-red-50 border border-red-200 rounded p-2 text-sm text-red-700">
                          {job.error}
                        </div>
                      )}
                    </div>
                  ))
                )}
              </div>
            </ScrollArea>
          </CardContent>
        </Card>
      </div>

      {/* Storage Info */}
      <Card>
        <CardHeader>
          <CardTitle>Informations de stockage</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">2.3 GB</div>
              <div className="text-sm text-muted-foreground">Espace utilisé</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">15</div>
              <div className="text-sm text-muted-foreground">Exports créés</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-purple-600">7 jours</div>
              <div className="text-sm text-muted-foreground">Rétention moyenne</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}