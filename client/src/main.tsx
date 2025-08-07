import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

// Capture global unhandled rejections AVANT le rendu
window.addEventListener('unhandledrejection', (event) => {
  console.warn('Promise rejection globale interceptée:', event.reason);
  event.preventDefault();
});

window.addEventListener('error', (event) => {
  console.warn('Erreur globale interceptée:', event.error);
  event.preventDefault();
});

createRoot(document.getElementById("root")!).render(<App />);
