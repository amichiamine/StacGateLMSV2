#!/bin/bash

# StacGateLMS React/Node.js - Démarrage via Interface PHP
# Pour hébergements web ne supportant pas Node.js directement

set -e

# Couleurs pour la sortie
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Fonctions utilitaires
print_header() {
    echo -e "${BLUE}"
    echo "=========================================="
    echo "  StacGateLMS React/Node.js - Interface PHP"
    echo "=========================================="
    echo -e "${NC}"
    echo
    echo -e "${CYAN}🌐 Installation via interface web PHP${NC}"
    echo -e "${CYAN}🚀 Compatible avec tous les hébergements${NC}"
    echo
}

print_step() {
    echo -e "${BLUE}[$1/3]${NC} $2..."
}

print_success() {
    echo -e "${GREEN}✅${NC} $1"
}

print_error() {
    echo -e "${RED}❌${NC} $1"
}

# Début du script
print_header

# [1/3] Vérification de PHP
print_step 1 "Vérification de PHP"
if ! command -v php &> /dev/null; then
    print_error "PHP n'est pas installé ou non accessible dans PATH"
    echo
    echo "💡 Solutions selon votre système :"
    echo "   macOS:        brew install php"
    echo "   Ubuntu/Debian: sudo apt install php-cli php-curl php-json php-mbstring"
    echo "   CentOS/RHEL:  sudo yum install php-cli php-curl php-json php-mbstring"
    echo "   Site officiel: https://www.php.net/downloads.php"
    echo
    exit 1
fi

PHP_VERSION=$(php --version | head -n 1 | cut -d ' ' -f 2)
MAJOR_VERSION=$(echo $PHP_VERSION | cut -d'.' -f1)
MINOR_VERSION=$(echo $PHP_VERSION | cut -d'.' -f2)

if [ "$MAJOR_VERSION" -lt 7 ] || ([ "$MAJOR_VERSION" -eq 7 ] && [ "$MINOR_VERSION" -lt 4 ]); then
    print_error "PHP version $PHP_VERSION détectée (requis: 7.4+)"
    echo "Veuillez mettre à jour PHP vers la version 7.4 ou plus récente"
    exit 1
fi

print_success "PHP détecté ($PHP_VERSION)"

# [2/3] Vérification du serveur web
print_step 2 "Vérification serveur web intégré"
if ! php -m | grep -q "Core"; then
    print_error "PHP CLI non fonctionnel"
    echo "Vérifiez votre installation PHP"
    exit 1
fi

print_success "Serveur PHP CLI prêt"

# [3/3] Démarrage du serveur PHP
print_step 3 "Démarrage interface web"
echo
echo -e "${CYAN}🌐 Interface d'installation accessible sur :${NC}"
echo "   http://localhost:8000"
echo
echo -e "${CYAN}📋 Fonctionnalités disponibles :${NC}"
echo "   ✅ Vérification automatique des prérequis"
echo "   ✅ Assistant d'installation interactif"
echo "   ✅ Configuration environnement .env"
echo "   ✅ Génération scripts de déploiement"
echo
echo -e "${YELLOW}⏹️  Appuyez sur Ctrl+C pour arrêter le serveur${NC}"
echo

# Ouvrir le navigateur automatiquement (si disponible)
sleep 3
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8000/scripts/install-wizard.php 2>/dev/null &
elif command -v open &> /dev/null; then
    open http://localhost:8000/scripts/install-wizard.php 2>/dev/null &
fi

# Démarrer le serveur PHP sur le port 8000
cd "$(dirname "$0")"
echo -e "${GREEN}🚀 Serveur PHP démarré sur http://localhost:8000${NC}"
echo

# Démarrer avec router vers install-wizard.php par défaut
php -S localhost:8000 -t . scripts/install-wizard.php