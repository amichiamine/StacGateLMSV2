#!/bin/bash

# StacGateLMS - Script de démarrage Linux/Mac
# Installation et démarrage automatique

set -e

# Couleurs pour la sortie
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions utilitaires
print_header() {
    echo -e "${BLUE}"
    echo "========================================"
    echo "  StacGateLMS - Installation Linux/Mac"
    echo "========================================"
    echo -e "${NC}"
    echo
}

print_step() {
    echo -e "${BLUE}[$1/5]${NC} $2..."
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

# [1/5] Vérification de PHP
print_step 1 "Vérification de PHP"
if ! command -v php &> /dev/null; then
    print_error "PHP n'est pas installé ou non accessible dans PATH"
    echo
    echo "💡 Solutions selon votre distribution :"
    echo "   Ubuntu/Debian: sudo apt-get install php php-cli php-mbstring php-pdo"
    echo "   CentOS/RHEL:   sudo yum install php php-cli php-mbstring php-pdo"
    echo "   macOS:         brew install php"
    echo
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
print_success "PHP détecté (version $PHP_VERSION)"

# Vérification de la version minimale
if ! php -r "exit(version_compare(PHP_VERSION, '8.1.0', '>=') ? 0 : 1);"; then
    print_error "PHP 8.1.0 ou plus récent requis (version actuelle: $PHP_VERSION)"
    exit 1
fi

# [2/5] Vérification de Composer
print_step 2 "Vérification de Composer"
if ! command -v composer &> /dev/null; then
    print_warning "Composer non détecté (optionnel)"
    echo "   Installation: curl -sS https://getcomposer.org/installer | php"
else
    print_success "Composer détecté"
    echo "   Installation des dépendances..."
    composer install --no-dev --optimize-autoloader --quiet
fi

# [3/5] Vérification des prérequis
print_step 3 "Vérification des prérequis système"
if ! php install/check-requirements.php; then
    echo
    print_error "Prérequis non satisfaits"
    echo "Veuillez corriger les erreurs ci-dessus"
    exit 1
fi

# [4/5] Configuration de l'environnement
print_step 4 "Configuration de l'environnement"
if [ ! -f ".env" ]; then
    php install/create-env.php
else
    print_success "Fichier .env déjà présent"
fi

# Permissions des dossiers
for dir in cache logs uploads; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
    fi
    chmod 755 "$dir"
done

# [5/5] Démarrage du serveur
print_step 5 "Démarrage du serveur de développement"
echo
print_success "StacGateLMS est prêt !"
echo
echo -e "${BLUE}📱 Interface d'installation :${NC} http://localhost:8000/install.php"
echo -e "${BLUE}🌐 Application principale  :${NC} http://localhost:8000"
echo
echo -e "${YELLOW}⏹️  Appuyez sur Ctrl+C pour arrêter le serveur${NC}"
echo

# Ouvrir le navigateur automatiquement (si disponible)
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8000/install.php 2>/dev/null &
elif command -v open &> /dev/null; then
    open http://localhost:8000/install.php 2>/dev/null &
fi

# Démarrer le serveur PHP
php -S 0.0.0.0:8000