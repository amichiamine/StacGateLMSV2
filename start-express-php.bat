@echo off
REM StacGateLMS React/Node.js - Démarrage via Interface PHP
REM Pour hébergements web ne supportant pas Node.js directement

title StacGateLMS React/Node.js - Interface PHP

echo.
echo  ==========================================
echo   StacGateLMS React/Node.js - Interface PHP
echo  ==========================================
echo.
echo  🌐 Installation via interface web PHP
echo  🚀 Compatible avec tous les hébergements
echo.

REM Vérification de PHP
echo [1/3] Verification de PHP...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP n'est pas installe ou non accessible
    echo.
    echo 💡 Solutions :
    echo    - Installer PHP 7.4+ : https://www.php.net/downloads.php
    echo    - Ou utiliser XAMPP/WAMP qui inclut PHP
    echo    - Ajouter PHP au PATH Windows
    echo.
    pause
    exit /b 1
)

for /f "tokens=2" %%i in ('php --version ^| findstr /R "^PHP"') do set PHP_VERSION=%%i
echo ✅ PHP detecte %PHP_VERSION%

REM Vérification du serveur web
echo [2/3] Verification serveur web integre...
php -m | findstr "Core" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP CLI non fonctionnel
    pause
    exit /b 1
)
echo ✅ Serveur PHP CLI pret

REM Démarrage du serveur PHP
echo [3/3] Demarrage interface web...
echo.
echo 🌐 Interface d'installation accessible sur :
echo    http://localhost:8000
echo.
echo 📋 Fonctionnalites disponibles :
echo    ✅ Verification automatique des prerequis
echo    ✅ Assistant d'installation interactif
echo    ✅ Configuration environnement .env
echo    ✅ Generation scripts de deploiement
echo.
echo ⏹️  Appuyez sur Ctrl+C pour arreter le serveur
echo.

REM Ouvrir le navigateur automatiquement
timeout /t 3 /nobreak >nul
start http://localhost:8000/scripts/install-wizard.php

REM Démarrer le serveur PHP sur le port 8000
cd /d "%~dp0"
php -S localhost:8000 -t . scripts/install-wizard.php

pause