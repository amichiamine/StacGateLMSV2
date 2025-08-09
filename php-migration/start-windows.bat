@echo off
REM StacGateLMS - Script de démarrage Windows
REM Installation et démarrage automatique

title StacGateLMS - Installation Windows

echo.
echo  ========================================
echo   StacGateLMS - Installation Windows
echo  ========================================
echo.

REM Vérification de PHP
echo [1/5] Verification de PHP...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP n'est pas installe ou non accessible dans PATH
    echo.
    echo 💡 Solutions :
    echo    - Installez XAMPP : https://www.apachefriends.org/
    echo    - Ou installez WAMP : https://www.wampserver.com/
    echo    - Ou PHP standalone : https://windows.php.net/download/
    echo.
    pause
    exit /b 1
)
echo ✅ PHP detecte

REM Vérification Composer (optionnel)
echo [2/5] Verification de Composer...
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  Composer non detecte (optionnel)
) else (
    echo ✅ Composer detecte
    echo [2a/5] Installation des dependances...
    composer install --no-dev --optimize-autoloader
)

REM Vérification des prérequis
echo [3/5] Verification des prerequis...
php install/check-requirements.php
if %errorlevel% neq 0 (
    echo.
    echo ❌ Prerequis non satisfaits
    echo Veuillez corriger les erreurs ci-dessus
    pause
    exit /b 1
)

REM Création de l'environnement
echo [4/5] Creation de l'environnement...
if not exist ".env" (
    php install/create-env.php
)

REM Démarrage du serveur
echo [5/5] Demarrage du serveur...
echo.
echo 🚀 StacGateLMS est pret !
echo.
echo 📱 Interface d'installation : http://localhost:8000/install.php
echo 🌐 Application principale  : http://localhost:8000
echo.
echo ⏹️  Appuyez sur Ctrl+C pour arreter le serveur
echo.

REM Ouvrir le navigateur automatiquement
start http://localhost:8000/install.php

REM Démarrer le serveur PHP
php -S 0.0.0.0:8000

pause