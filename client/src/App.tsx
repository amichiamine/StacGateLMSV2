import { Switch, Route } from "wouter";
import { queryClient } from "./lib/queryClient";
import { QueryClientProvider } from "@tanstack/react-query";
import { Toaster } from "@/components/ui/toaster";
import { TooltipProvider } from "@/components/ui/tooltip";
import { useAuth } from "@/hooks/useAuth";
import Home from "@/pages/home";
import Login from "@/pages/login";
import Dashboard from "@/pages/dashboard";
import Landing from "@/pages/landing";
import Portal from "@/pages/portal";
import Establishment from "@/pages/establishment";
import AdminPage from "@/pages/admin";
import SuperAdminPage from "@/pages/super-admin";
import UserManagement from "@/pages/user-management";
import CoursesPage from "@/pages/courses";
import AssessmentsPage from "@/pages/assessments";
import UserManualPage from "@/pages/user-manual";
import ArchiveExportPage from "@/pages/archive-export";
import SystemUpdatesPage from "@/pages/system-updates";
import WysiwygEditorPage from "@/pages/wysiwyg-editor";
import StudyGroupsPage from "@/pages/study-groups";
import NotFound from "@/pages/not-found";

function Router() {
  return (
    <Switch>
      <Route path="/" component={Home} />
      <Route path="/portal" component={Portal} />
      <Route path="/establishment/:slug" component={Establishment} />
      <Route path="/login" component={Login} />
      <Route path="/dashboard" component={Dashboard} />
      <Route path="/admin" component={AdminPage} />
      <Route path="/super-admin" component={SuperAdminPage} />
      <Route path="/user-management" component={UserManagement} />
      <Route path="/courses" component={CoursesPage} />
      <Route path="/assessments" component={AssessmentsPage} />
      <Route path="/manual" component={UserManualPage} />
      <Route path="/archive" component={ArchiveExportPage} />
      <Route path="/system-updates" component={SystemUpdatesPage} />
      <Route path="/wysiwyg-editor" component={WysiwygEditorPage} />
      <Route path="/study-groups" component={StudyGroupsPage} />
      <Route component={NotFound} />
    </Switch>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Toaster />
        <Router />
      </TooltipProvider>
    </QueryClientProvider>
  );
}

export default App;
