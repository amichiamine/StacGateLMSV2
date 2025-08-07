# Déploiement StacGateLMS

## Options de déploiement

### 1. Développement Local
```bash
npm run dev
```

### 2. Production avec Node.js
```bash
npm run build
npm run start
```

### 3. Hébergement Web avec cPanel + Node.js
1. Télécharger le dossier `dist/` après build
2. Configurer Node.js dans cPanel
3. Upload et démarrer l'application

### 4. Hébergement Web statique (sans Node.js)
Pour les fonctionnalités frontend uniquement :
1. Build du frontend : `npm run build`
2. Déployer le contenu de `dist/public/`
3. Configurer un backend séparé ou des APIs externes

## Structure de déploiement
```
dist/
├── public/          # Frontend statique
└── index.js         # Backend Node.js
```

## Variables d'environnement requises
- DATABASE_URL
- SESSION_SECRET
- NODE_ENV
- PORT (optionnel, défaut: 5000)