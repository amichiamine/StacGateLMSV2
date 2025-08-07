import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

// Capture global unhandled rejections AVANT le rendu
window.addEventListener('unhandledrejection', (event) => {
  console.error('Promise rejection globale:', event.reason);
  
  // Prevent default for specific error types to avoid console spam
  if (event.reason instanceof Error) {
    const message = event.reason.message.toLowerCase();
    const isNetworkError = message.includes('fetch') || 
                          message.includes('network') ||
                          message.includes('failed to fetch');
    const isAuthError = message.includes('401') || 
                       message.includes('unauthorized') ||
                       message.includes('non authentifié') ||
                       message.includes('authentication required');
    const isAPIError = message.includes('404') ||
                      message.includes('500') ||
                      message.includes('api endpoint not found');
    
    if (isNetworkError || isAuthError || isAPIError) {
      event.preventDefault();
    }
  }
  
  // Also handle errors that are just strings (from API responses)
  if (typeof event.reason === 'string') {
    const reason = event.reason.toLowerCase();
    if (reason.includes('401') || reason.includes('404') || reason.includes('unauthorized')) {
      event.preventDefault();
    }
  }
});

window.addEventListener('error', (event) => {
  console.error('Erreur globale:', event.error);
  // Don't prevent default for actual errors
});

createRoot(document.getElementById("root")!).render(<App />);
