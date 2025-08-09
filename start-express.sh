#!/bin/bash

# StacGateLMS React/Node.js - Script de démarrage Linux/Mac
# Installation et démarrage automatique

set -e

# Couleurs pour la sortie
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Fonctions utilitaires
print_header() {
    echo -e "${BLUE}"
    echo "=========================================="
    echo "  StacGateLMS React/Node.js Installation"
    echo "=========================================="
    echo -e "${NC}"
    echo
}

print_step() {
    echo -e "${BLUE}[$1/6]${NC} $2..."
}

print_success() {
    echo -e "${GREEN}✅${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠️${NC} $1"
}

print_error() {
    echo -e "${RED}❌${NC} $1"
}

# Début du script
print_header

# [1/6] Vérification de Node.js
print_step 1 "Vérification de Node.js"
if ! command -v node &> /dev/null; then
    print_error "Node.js n'est pas installé ou non accessible dans PATH"
    echo
    echo "💡 Solutions selon votre système :"
    echo "   macOS:        brew install node"
    echo "   Ubuntu/Debian: curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -"
    echo "                 sudo apt-get install -y nodejs"
    echo "   CentOS/RHEL:  curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -"
    echo "                 sudo yum install -y nodejs"
    echo "   Site officiel: https://nodejs.org/"
    echo
    exit 1
fi

NODE_VERSION=$(node --version)
MAJOR_VERSION=$(echo $NODE_VERSION | cut -d'.' -f1 | cut -d'v' -f2)

if [ "$MAJOR_VERSION" -lt 18 ]; then
    print_error "Node.js version $NODE_VERSION détectée (requis: 18+)"
    echo "Veuillez mettre à jour Node.js vers la version 18 ou plus récente"
    exit 1
fi

print_success "Node.js détecté ($NODE_VERSION)"

# [2/6] Vérification de NPM
print_step 2 "Vérification de NPM"
if ! command -v npm &> /dev/null; then
    print_error "NPM non détecté"
    echo "NPM devrait être installé avec Node.js. Vérifiez votre installation Node.js."
    exit 1
fi

NPM_VERSION=$(npm --version)
print_success "NPM détecté ($NPM_VERSION)"

# [3/6] Installation des dépendances
print_step 3 "Vérification des dépendances"
if [ ! -d "node_modules" ]; then
    echo "   Installation des dépendances NPM..."
    if ! npm install; then
        print_error "Erreur lors de l'installation des dépendances"
        exit 1
    fi
else
    print_success "Dépendances déjà installées"
fi

# [4/6] Lancement de l'assistant d'installation
print_step 4 "Lancement de l'assistant d'installation"
echo
echo -e "${CYAN}🚀 Démarrage de l'assistant interactif...${NC}"
echo

if ! node scripts/install-wizard.js; then
    echo
    print_error "L'installation a échoué"
    echo "Vérifiez les erreurs ci-dessus"
    exit 1
fi

echo
print_step 5 "Démarrage des services"

# [6/6] Lancement de l'application
print_step 6 "Lancement de l'application"
echo
print_success "StacGateLMS est prêt !"
echo
echo -e "${BLUE}📱 Frontend React :${NC} http://localhost:3000"
echo -e "${BLUE}🔧 Backend API    :${NC} http://localhost:5000"
echo -e "${BLUE}🗄️ Base de données:${NC} PostgreSQL"
echo
echo -e "${YELLOW}⏹️  Appuyez sur Ctrl+C pour arrêter les services${NC}"
echo

# Ouvrir le navigateur automatiquement (si disponible)
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:3000 2>/dev/null &
elif command -v open &> /dev/null; then
    open http://localhost:3000 2>/dev/null &
fi

# Démarrage simultané frontend + backend
npm run dev