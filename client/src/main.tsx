import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

// Capture global unhandled rejections AVANT le rendu
window.addEventListener('unhandledrejection', (event) => {
  console.error('Promise rejection globale:', event.reason);
  // Prevent for common network/auth errors but allow real errors to surface
  if (event.reason instanceof Error) {
    const message = event.reason.message.toLowerCase();
    const isNetworkError = message.includes('fetch') || 
                          message.includes('network') ||
                          message.includes('failed to fetch');
    const isAuthError = message.includes('401') || 
                       message.includes('unauthorized') ||
                       message.includes('non authentifié');
    
    if (isNetworkError || isAuthError) {
      event.preventDefault();
    }
  }
});

window.addEventListener('error', (event) => {
  console.error('Erreur globale:', event.error);
  // Don't prevent default for actual errors
});

createRoot(document.getElementById("root")!).render(<App />);
