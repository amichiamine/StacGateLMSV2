@echo off
REM StacGateLMS React/Node.js - Script de démarrage Windows
REM Installation et démarrage automatique

title StacGateLMS React/Node.js - Installation Express

echo.
echo  ==========================================
echo   StacGateLMS React/Node.js Installation
echo  ==========================================
echo.

REM Vérification de Node.js
echo [1/6] Verification de Node.js...
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js n'est pas installe ou non accessible dans PATH
    echo.
    echo 💡 Solutions :
    echo    - Telecharger Node.js 18+ : https://nodejs.org/
    echo    - Redemarrer le terminal apres installation
    echo.
    pause
    exit /b 1
)

REM Vérifier la version de Node.js
for /f "tokens=1" %%i in ('node --version') do set NODE_VERSION=%%i
echo ✅ Node.js detecte %NODE_VERSION%

REM Vérification de NPM
echo [2/6] Verification de NPM...
npm --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ NPM non detecte
    pause
    exit /b 1
)
echo ✅ NPM detecte

REM Installation des dépendances si nécessaire
echo [3/6] Verification des dependances...
if not exist "node_modules" (
    echo Installation des dependances NPM...
    npm install
    if %errorlevel% neq 0 (
        echo ❌ Erreur lors de l'installation des dependances
        pause
        exit /b 1
    )
) else (
    echo ✅ Dependances deja installees
)

REM Lancement de l'assistant d'installation
echo [4/6] Lancement de l'assistant d'installation...
echo.
echo 🚀 Demarrage de l'assistant interactif...
echo.
node scripts/install-wizard.js

if %errorlevel% neq 0 (
    echo.
    echo ❌ L'installation a echoue
    echo Verifiez les erreurs ci-dessus
    pause
    exit /b 1
)

echo.
echo [5/6] Demarrage des services...

REM Démarrage en mode développement
echo [6/6] Lancement de l'application...
echo.
echo 🎉 StacGateLMS est pret !
echo.
echo 📱 Frontend React : http://localhost:3000
echo 🔧 Backend API    : http://localhost:5000
echo 🗄️ Base de donnees: PostgreSQL
echo.
echo ⏹️  Appuyez sur Ctrl+C pour arreter les services
echo.

REM Ouvrir le navigateur
start http://localhost:3000

REM Démarrage simultané frontend + backend
npm run dev

pause