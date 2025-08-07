import React from "react";
import { Router, Route, Switch } from "wouter";
import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "./core/lib/queryClient";
import { Toaster } from "./components/ui/toaster";

// Auth feature
import Login from "./features/auth/login";

// Admin features  
import Admin from "./features/admin/admin";
import SuperAdmin from "./features/admin/super-admin";
import UserManagement from "./features/admin/user-management";
import SystemUpdates from "./features/admin/system-updates";

// Content features
import Portal from "./features/content/portal";
import WysiwygEditor from "./features/content/wysiwyg-editor";
import Establishment from "./features/content/establishment";

// Training features
import Courses from "./features/training/courses";
import Assessments from "./features/training/assessments";
import StudyGroups from "./features/training/study-groups";
import UserManual from "./features/training/user-manual";

// Core pages
import Dashboard from "./features/dashboard";
import Home from "./features/home";
import Landing from "./features/landing";
import NotFound from "./features/not-found";
import ArchiveExport from "./features/archive-export";

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <Router>
        <div className="min-h-screen bg-background">
          <Switch>
            <Route path="/" component={Landing} />
            <Route path="/login" component={Login} />
            <Route path="/home" component={Home} />
            <Route path="/dashboard" component={Dashboard} />
            
            {/* Training Routes */}
            <Route path="/courses" component={Courses} />
            <Route path="/assessments" component={Assessments} />
            <Route path="/study-groups" component={StudyGroups} />
            <Route path="/user-manual" component={UserManual} />
            
            {/* Content Routes */}
            <Route path="/portal" component={Portal} />
            <Route path="/wysiwyg-editor" component={WysiwygEditor} />
            <Route path="/establishment" component={Establishment} />
            
            {/* Admin Routes */}
            <Route path="/admin" component={Admin} />
            <Route path="/super-admin" component={SuperAdmin} />
            <Route path="/user-management" component={UserManagement} />
            <Route path="/system-updates" component={SystemUpdates} />
            <Route path="/archive-export" component={ArchiveExport} />
            
            <Route component={NotFound} />
          </Switch>
        </div>
      </Router>
      <Toaster />
    </QueryClientProvider>
  );
}

export default App;