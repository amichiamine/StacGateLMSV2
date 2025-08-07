import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

// Capture global unhandled rejections AVANT le rendu
window.addEventListener('unhandledrejection', (event) => {
  console.error('Promise rejection globale:', event.reason);
  // Only prevent for network errors, not for actual bugs
  if (event.reason instanceof Error && event.reason.message.includes('fetch')) {
    event.preventDefault();
  }
});

window.addEventListener('error', (event) => {
  console.error('Erreur globale:', event.error);
  // Don't prevent default for actual errors
});

createRoot(document.getElementById("root")!).render(<App />);
