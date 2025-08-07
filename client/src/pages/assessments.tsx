import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { 
  BookOpen, 
  Clock, 
  Star, 
  Search, 
  Plus, 
  Trash2, 
  Edit,
  Play,
  CheckCircle,
  XCircle,
  Trophy,
  Target,
  FileText,
  Brain,
  Calendar,
  Users
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { useAuth } from "@/hooks/useAuth";

interface Assessment {
  id: string;
  courseId: string;
  title: string;
  description: string;
  assessmentType: string;
  maxScore: number;
  passingScore: number;
  timeLimit: number;
  maxAttempts: number;
  isPublic: boolean;
  dueDate: string;
  questions: any[];
  status: string; // draft, pending_approval, approved, rejected
  approvedBy?: string;
  approvedAt?: string;
  rejectionReason?: string;
  createdBy: string;
  course?: {
    title: string;
    category: string;
  };
}

interface AssessmentAttempt {
  id: string;
  assessmentId: string;
  score: number;
  maxScore: number;
  status: string;
  timeSpent: number;
  startedAt: string;
  completedAt: string;
  assessment?: Assessment;
}

export default function AssessmentsPage() {
  const { user, isAuthenticated } = useAuth();
  const { toast } = useToast();
  const queryClient = useQueryClient();
  
  const [searchTerm, setSearchTerm] = useState("");
  const [typeFilter, setTypeFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");
  const [showCreateModal, setShowCreateModal] = useState(false);
  
  const [newAssessment, setNewAssessment] = useState({
    courseId: "",
    title: "",
    description: "",
    assessmentType: "quiz",
    maxScore: 100,
    passingScore: 70,
    timeLimit: 60,
    maxAttempts: 3,
    isPublic: true,
    dueDate: "",
    questions: []
  });

  const isInstructor = user?.role === 'formateur' || user?.role === 'admin' || user?.role === 'super_admin';

  // Fetch assessments
  const { data: assessments = [], isLoading: assessmentsLoading } = useQuery<Assessment[]>({
    queryKey: ['/api/assessments'],
    enabled: isAuthenticated
  });

  // Fetch user attempts
  const { data: userAttempts = [], isLoading: attemptsLoading } = useQuery<AssessmentAttempt[]>({
    queryKey: ['/api/assessments/attempts'],
    enabled: isAuthenticated
  });

  // Fetch courses for instructors
  const { data: courses = [], isLoading: coursesLoading } = useQuery({
    queryKey: ['/api/courses'],
    enabled: isAuthenticated && isInstructor
  });

  // Create assessment mutation
  const createAssessmentMutation = useMutation({
    mutationFn: async (assessmentData: any) => {
      const response = await fetch('/api/assessments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(assessmentData),
      });
      if (!response.ok) throw new Error('Failed to create assessment');
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Succès",
        description: "Évaluation créée avec succès.",
      });
      queryClient.invalidateQueries({ queryKey: ['/api/assessments'] });
      setShowCreateModal(false);
      setNewAssessment({
        courseId: "",
        title: "",
        description: "",
        assessmentType: "quiz",
        maxScore: 100,
        passingScore: 70,
        timeLimit: 60,
        maxAttempts: 3,
        isPublic: true,
        dueDate: "",
        questions: []
      });
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de créer l'évaluation.",
        variant: "destructive",
      });
    },
  });

  // Start assessment mutation
  const startAssessmentMutation = useMutation({
    mutationFn: async (assessmentId: string) => {
      const response = await fetch(`/api/assessments/${assessmentId}/start`, {
        method: 'POST',
      });
      if (!response.ok) throw new Error('Failed to start assessment');
      return response.json();
    },
    onSuccess: (data) => {
      toast({
        title: "Évaluation démarrée",
        description: "Bonne chance !",
      });
      // Redirect to assessment taking interface
      window.location.href = `/assessments/${data.attemptId}/take`;
    },
    onError: () => {
      toast({
        title: "Erreur",
        description: "Impossible de démarrer l'évaluation.",
        variant: "destructive",
      });
    },
  });

  const handleCreateAssessment = () => {
    if (!newAssessment.title.trim() || !newAssessment.courseId) {
      toast({
        title: "Erreur",
        description: "Le titre et le cours sont requis.",
        variant: "destructive",
      });
      return;
    }
    createAssessmentMutation.mutate(newAssessment);
  };

  // Filter assessments
  const filteredAssessments = assessments.filter(assessment => {
    const matchesSearch = assessment.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         assessment.description?.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesType = typeFilter === "all" || assessment.assessmentType === typeFilter;
    return matchesSearch && matchesType;
  });

  // Get assessment statistics
  const getAssessmentStats = (assessmentId: string) => {
    const attempts = userAttempts.filter(attempt => attempt.assessmentId === assessmentId);
    const bestAttempt = attempts.reduce((best, current) => 
      current.score > (best?.score || 0) ? current : best, null as AssessmentAttempt | null);
    
    return {
      totalAttempts: attempts.length,
      bestScore: bestAttempt?.score || 0,
      maxScore: bestAttempt?.maxScore || 100,
      isPassed: bestAttempt ? bestAttempt.score >= (assessments.find(a => a.id === assessmentId)?.passingScore || 70) : false,
      lastAttempt: attempts[attempts.length - 1]
    };
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'quiz': return <Brain className="w-4 h-4" />;
      case 'exam': return <FileText className="w-4 h-4" />;
      case 'assignment': return <Edit className="w-4 h-4" />;
      case 'project': return <Target className="w-4 h-4" />;
      default: return <BookOpen className="w-4 h-4" />;
    }
  };

  const getTypeBadgeColor = (type: string) => {
    switch (type) {
      case 'quiz': return 'bg-blue-100 text-blue-800';
      case 'exam': return 'bg-red-100 text-red-800';
      case 'assignment': return 'bg-green-100 text-green-800';
      case 'project': return 'bg-purple-100 text-purple-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
        <Card className="w-full max-w-md">
          <CardContent className="flex flex-col items-center justify-center h-64 space-y-4">
            <Trophy className="w-16 h-16 text-gray-400" />
            <div className="text-center">
              <h3 className="text-lg font-semibold text-gray-900">Connexion requise</h3>
              <p className="text-gray-600">Veuillez vous connecter pour accéder aux évaluations.</p>
            </div>
            <Button onClick={() => window.location.href = '/login'}>
              Se connecter
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
      {/* Header */}
      <header className="bg-white/80 backdrop-blur-sm shadow-sm border-b rounded-b-3xl">
        <div className="container mx-auto px-4 py-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center">
                <Trophy className="w-6 h-6 text-white" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-gray-900">Évaluations</h1>
                <p className="text-gray-600">Testez vos connaissances et suivez vos progrès</p>
              </div>
            </div>
            <div className="flex items-center space-x-3">
              <Button 
                variant="outline" 
                onClick={() => window.location.href = '/dashboard'}
                className="flex items-center gap-2"
              >
                <BookOpen className="h-4 w-4" />
                Dashboard
              </Button>
              <Button 
                variant="outline" 
                onClick={() => window.location.href = '/courses'}
                className="flex items-center gap-2"
              >
                <BookOpen className="h-4 w-4" />
                Cours
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="container mx-auto py-8 px-4">
        <Tabs defaultValue="available" className="space-y-6">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="available" className="flex items-center gap-2">
              <Target className="w-4 h-4" />
              Disponibles
            </TabsTrigger>
            <TabsTrigger value="completed" className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4" />
              Terminées
            </TabsTrigger>
            {isInstructor && (
              <TabsTrigger value="manage" className="flex items-center gap-2">
                <Edit className="w-4 h-4" />
                Gestion
              </TabsTrigger>
            )}
          </TabsList>

          {/* Available Assessments */}
          <TabsContent value="available" className="space-y-6">
            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
              <div className="flex flex-col sm:flex-row gap-4 flex-1">
                <div className="relative flex-1 max-w-md">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <Input
                    placeholder="Rechercher une évaluation..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10 bg-white/80 backdrop-blur-sm"
                  />
                </div>
                <Select value={typeFilter} onValueChange={setTypeFilter}>
                  <SelectTrigger className="w-full sm:w-48 bg-white/80 backdrop-blur-sm">
                    <SelectValue placeholder="Type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Tous les types</SelectItem>
                    <SelectItem value="quiz">Quiz</SelectItem>
                    <SelectItem value="exam">Examen</SelectItem>
                    <SelectItem value="assignment">Devoir</SelectItem>
                    <SelectItem value="project">Projet</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* Assessments Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredAssessments.map((assessment) => {
                const stats = getAssessmentStats(assessment.id);
                const canTakeAssessment = stats.totalAttempts < (assessment.maxAttempts || 999);
                
                return (
                  <Card key={assessment.id} className="group hover:shadow-xl transition-all duration-300 bg-white/80 backdrop-blur-sm">
                    <CardHeader className="pb-4">
                      <div className="flex items-start justify-between">
                        <div className="flex items-center space-x-2">
                          {getTypeIcon(assessment.assessmentType)}
                          <Badge className={`${getTypeBadgeColor(assessment.assessmentType)} border-0`}>
                            {assessment.assessmentType}
                          </Badge>
                        </div>
                        {stats.isPassed && (
                          <CheckCircle className="w-5 h-5 text-green-600" />
                        )}
                      </div>
                      <CardTitle className="line-clamp-2">{assessment.title}</CardTitle>
                      <CardDescription className="line-clamp-2">
                        {assessment.description}
                      </CardDescription>
                    </CardHeader>
                    
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-2 gap-4 text-sm">
                        <div className="flex items-center space-x-2">
                          <Clock className="w-4 h-4 text-gray-500" />
                          <span>{assessment.timeLimit} min</span>
                        </div>
                        <div className="flex items-center space-x-2">
                          <Target className="w-4 h-4 text-gray-500" />
                          <span>{assessment.passingScore}/{assessment.maxScore}</span>
                        </div>
                      </div>
                      
                      {stats.totalAttempts > 0 && (
                        <div className="bg-gray-50 rounded-lg p-3">
                          <div className="text-sm text-gray-600 mb-1">Vos résultats</div>
                          <div className="flex justify-between items-center">
                            <span className="font-medium">
                              Meilleur score: {stats.bestScore}/{stats.maxScore}
                            </span>
                            <span className="text-sm text-gray-500">
                              {stats.totalAttempts}/{assessment.maxAttempts} tentatives
                            </span>
                          </div>
                        </div>
                      )}
                      
                      <div className="flex gap-2">
                        {canTakeAssessment ? (
                          <Button 
                            onClick={() => startAssessmentMutation.mutate(assessment.id)}
                            disabled={startAssessmentMutation.isPending}
                            className="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                          >
                            <Play className="w-4 h-4 mr-2" />
                            {stats.totalAttempts > 0 ? 'Retenter' : 'Commencer'}
                          </Button>
                        ) : (
                          <Button disabled className="flex-1">
                            <XCircle className="w-4 h-4 mr-2" />
                            Limite atteinte
                          </Button>
                        )}
                      </div>
                    </CardContent>
                  </Card>
                );
              })}
            </div>

            {filteredAssessments.length === 0 && (
              <Card>
                <CardContent className="flex flex-col items-center justify-center h-64 space-y-4">
                  <Trophy className="w-16 h-16 text-gray-400" />
                  <div className="text-center">
                    <h3 className="text-lg font-semibold text-gray-900">Aucune évaluation disponible</h3>
                    <p className="text-gray-600">Il n'y a pas d'évaluations correspondant à vos critères.</p>
                  </div>
                </CardContent>
              </Card>
            )}
          </TabsContent>

          {/* Completed Assessments */}
          <TabsContent value="completed" className="space-y-6">
            <div className="grid gap-4">
              {userAttempts
                .filter(attempt => attempt.status === 'completed' || attempt.status === 'graded')
                .map((attempt) => (
                <Card key={attempt.id} className="p-4">
                  <div className="flex justify-between items-start">
                    <div className="flex-1">
                      <h4 className="font-semibold">{attempt.assessment?.title}</h4>
                      <p className="text-sm text-gray-600 mt-1">
                        {attempt.assessment?.course?.title} • {attempt.assessment?.course?.category}
                      </p>
                      <div className="flex items-center space-x-4 mt-2">
                        <Badge className={`${
                          attempt.score >= (attempt.assessment?.passingScore || 70) 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                        } border-0`}>
                          {attempt.score >= (attempt.assessment?.passingScore || 70) ? 'Réussi' : 'Échoué'}
                        </Badge>
                        <span className="text-sm text-gray-500">
                          {Math.round(attempt.timeSpent)} min
                        </span>
                        <span className="text-sm text-gray-500">
                          {new Date(attempt.completedAt).toLocaleDateString()}
                        </span>
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="text-2xl font-bold">
                        {attempt.score}/{attempt.maxScore}
                      </div>
                      <div className="text-sm text-gray-500">
                        {Math.round((attempt.score / attempt.maxScore) * 100)}%
                      </div>
                    </div>
                  </div>
                </Card>
              ))}
            </div>

            {userAttempts.filter(attempt => attempt.status === 'completed' || attempt.status === 'graded').length === 0 && (
              <Card>
                <CardContent className="flex flex-col items-center justify-center h-64 space-y-4">
                  <CheckCircle className="w-16 h-16 text-gray-400" />
                  <div className="text-center">
                    <h3 className="text-lg font-semibold text-gray-900">Aucune évaluation terminée</h3>
                    <p className="text-gray-600">Vos évaluations terminées apparaîtront ici.</p>
                  </div>
                </CardContent>
              </Card>
            )}
          </TabsContent>

          {/* Management Tab for Instructors */}
          {isInstructor && (
            <TabsContent value="manage" className="space-y-6">
              <div className="flex justify-between items-center">
                <div>
                  <h3 className="text-xl font-semibold">Gestion des évaluations</h3>
                  <p className="text-gray-600">Créez et gérez vos évaluations</p>
                </div>
                <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                  <DialogTrigger asChild>
                    <Button className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                      <Plus className="w-4 h-4 mr-2" />
                      Créer une évaluation
                    </Button>
                  </DialogTrigger>
                  <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                      <DialogTitle>Créer une nouvelle évaluation</DialogTitle>
                      <DialogDescription>
                        Configurez votre évaluation et ses paramètres
                      </DialogDescription>
                    </DialogHeader>
                    
                    <div className="space-y-4 py-4">
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                          <Label htmlFor="title">Titre *</Label>
                          <Input
                            id="title"
                            value={newAssessment.title}
                            onChange={(e) => setNewAssessment({...newAssessment, title: e.target.value})}
                            placeholder="Ex: Quiz JavaScript Chapitre 1"
                          />
                        </div>
                        <div>
                          <Label htmlFor="courseId">Cours *</Label>
                          <Select 
                            value={newAssessment.courseId} 
                            onValueChange={(value) => setNewAssessment({...newAssessment, courseId: value})}
                          >
                            <SelectTrigger>
                              <SelectValue placeholder="Sélectionner un cours" />
                            </SelectTrigger>
                            <SelectContent>
                              {courses.map((course: any) => (
                                <SelectItem key={course.id} value={course.id}>
                                  {course.title}
                                </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                        </div>
                      </div>
                      
                      <div>
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                          id="description"
                          value={newAssessment.description}
                          onChange={(e) => setNewAssessment({...newAssessment, description: e.target.value})}
                          placeholder="Décrivez l'évaluation..."
                          rows={3}
                        />
                      </div>
                      
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                          <Label htmlFor="type">Type d'évaluation</Label>
                          <Select 
                            value={newAssessment.assessmentType} 
                            onValueChange={(value) => setNewAssessment({...newAssessment, assessmentType: value})}
                          >
                            <SelectTrigger>
                              <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="quiz">Quiz</SelectItem>
                              <SelectItem value="exam">Examen</SelectItem>
                              <SelectItem value="assignment">Devoir</SelectItem>
                              <SelectItem value="project">Projet</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                        <div>
                          <Label htmlFor="timeLimit">Durée (minutes)</Label>
                          <Input
                            id="timeLimit"
                            type="number"
                            value={newAssessment.timeLimit}
                            onChange={(e) => setNewAssessment({...newAssessment, timeLimit: parseInt(e.target.value) || 60})}
                          />
                        </div>
                      </div>
                      
                      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                          <Label htmlFor="maxScore">Score maximum</Label>
                          <Input
                            id="maxScore"
                            type="number"
                            value={newAssessment.maxScore}
                            onChange={(e) => setNewAssessment({...newAssessment, maxScore: parseInt(e.target.value) || 100})}
                          />
                        </div>
                        <div>
                          <Label htmlFor="passingScore">Score de passage</Label>
                          <Input
                            id="passingScore"
                            type="number"
                            value={newAssessment.passingScore}
                            onChange={(e) => setNewAssessment({...newAssessment, passingScore: parseInt(e.target.value) || 70})}
                          />
                        </div>
                        <div>
                          <Label htmlFor="maxAttempts">Tentatives max</Label>
                          <Input
                            id="maxAttempts"
                            type="number"
                            value={newAssessment.maxAttempts}
                            onChange={(e) => setNewAssessment({...newAssessment, maxAttempts: parseInt(e.target.value) || 3})}
                          />
                        </div>
                      </div>
                      
                      <div>
                        <Label htmlFor="dueDate">Date limite (optionnel)</Label>
                        <Input
                          id="dueDate"
                          type="datetime-local"
                          value={newAssessment.dueDate}
                          onChange={(e) => setNewAssessment({...newAssessment, dueDate: e.target.value})}
                        />
                      </div>
                    </div>
                    
                    <div className="flex justify-end space-x-2">
                      <Button variant="outline" onClick={() => setShowCreateModal(false)}>
                        Annuler
                      </Button>
                      <Button 
                        onClick={handleCreateAssessment}
                        disabled={createAssessmentMutation.isPending}
                        className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                      >
                        {createAssessmentMutation.isPending ? "Création..." : "Créer l'évaluation"}
                      </Button>
                    </div>
                  </DialogContent>
                </Dialog>
              </div>

              {/* Instructor's assessments list */}
              <div className="grid gap-4">
                {assessments.map((assessment) => (
                  <Card key={assessment.id} className="p-4">
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <div className="flex items-center space-x-2 mb-2">
                          {getTypeIcon(assessment.assessmentType)}
                          <h4 className="font-semibold">{assessment.title}</h4>
                          <Badge className={`${getTypeBadgeColor(assessment.assessmentType)} border-0`}>
                            {assessment.assessmentType}
                          </Badge>
                        </div>
                        <p className="text-sm text-gray-600 mb-2">{assessment.description}</p>
                        <div className="flex items-center space-x-4 text-sm text-gray-500">
                          <span>{assessment.timeLimit} min</span>
                          <span>{assessment.passingScore}/{assessment.maxScore} pts</span>
                          <span>{assessment.maxAttempts} tentatives</span>
                          {assessment.dueDate && (
                            <span>Échéance: {new Date(assessment.dueDate).toLocaleDateString()}</span>
                          )}
                        </div>
                      </div>
                      <div className="flex space-x-2">
                        <Button variant="outline" size="sm">
                          <Edit className="w-4 h-4" />
                        </Button>
                        <Button variant="outline" size="sm">
                          <Users className="w-4 h-4" />
                        </Button>
                        <Button variant="outline" size="sm" className="text-red-600 border-red-200 hover:bg-red-50">
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    </div>
                  </Card>
                ))}
              </div>

              {assessments.length === 0 && (
                <Card>
                  <CardContent className="flex flex-col items-center justify-center h-64 space-y-4">
                    <FileText className="w-16 h-16 text-gray-400" />
                    <div className="text-center">
                      <h3 className="text-lg font-semibold text-gray-900">Aucune évaluation créée</h3>
                      <p className="text-gray-600">Créez votre première évaluation pour commencer.</p>
                    </div>
                  </CardContent>
                </Card>
              )}
            </TabsContent>
          )}
        </Tabs>
      </div>
    </div>
  );
}