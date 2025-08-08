<?php

class ProgressiveWebAppService {
    private $database;
    private $establishmentId;

    public function __construct($database, $establishmentId = null) {
        $this->database = $database;
        $this->establishmentId = $establishmentId;
    }

    /**
     * Générer le manifeste PWA
     */
    public function generateManifest() {
        $establishment = null;
        if ($this->establishmentId) {
            $establishment = $this->database->findOne('establishments', ['id' => $this->establishmentId]);
        }

        $appName = $establishment['name'] ?? 'StacGate LMS';
        $appShortName = $establishment['short_name'] ?? 'StacGate';
        $themeColor = $this->getThemeColor();
        
        $manifest = [
            'name' => $appName,
            'short_name' => $appShortName,
            'description' => 'Plateforme d\'apprentissage moderne et intuitive',
            'start_url' => '/dashboard',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $themeColor,
            'orientation' => 'portrait-primary',
            'scope' => '/',
            'lang' => 'fr',
            'dir' => 'ltr',
            'categories' => ['education', 'productivity'],
            'icons' => $this->generateIcons(),
            'shortcuts' => $this->generateShortcuts(),
            'screenshots' => $this->generateScreenshots()
        ];

        return $manifest;
    }

    /**
     * Générer les icônes PWA
     */
    private function generateIcons() {
        $baseIcon = '/assets/icons/icon-base.png';
        $icons = [];
        
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
        
        foreach ($sizes as $size) {
            $icons[] = [
                'src' => "/assets/icons/icon-{$size}x{$size}.png",
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ];
        }

        // Icône vectorielle
        $icons[] = [
            'src' => '/assets/icons/icon.svg',
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable'
        ];

        return $icons;
    }

    /**
     * Générer les raccourcis PWA
     */
    private function generateShortcuts() {
        return [
            [
                'name' => 'Mes Cours',
                'short_name' => 'Cours',
                'description' => 'Accéder rapidement à mes cours',
                'url' => '/courses',
                'icons' => [
                    [
                        'src' => '/assets/icons/courses-96x96.png',
                        'sizes' => '96x96'
                    ]
                ]
            ],
            [
                'name' => 'Notifications',
                'short_name' => 'Notifs',
                'description' => 'Voir mes notifications',
                'url' => '/notifications',
                'icons' => [
                    [
                        'src' => '/assets/icons/notifications-96x96.png',
                        'sizes' => '96x96'
                    ]
                ]
            ],
            [
                'name' => 'Profil',
                'short_name' => 'Profil',
                'description' => 'Gérer mon profil',
                'url' => '/profile',
                'icons' => [
                    [
                        'src' => '/assets/icons/profile-96x96.png',
                        'sizes' => '96x96'
                    ]
                ]
            ]
        ];
    }

    /**
     * Générer les captures d'écran PWA
     */
    private function generateScreenshots() {
        return [
            [
                'src' => '/assets/screenshots/desktop-dashboard.png',
                'sizes' => '1280x720',
                'type' => 'image/png',
                'form_factor' => 'wide',
                'label' => 'Tableau de bord principal'
            ],
            [
                'src' => '/assets/screenshots/mobile-courses.png',
                'sizes' => '750x1334',
                'type' => 'image/png',
                'form_factor' => 'narrow',
                'label' => 'Liste des cours sur mobile'
            ]
        ];
    }

    /**
     * Générer le Service Worker
     */
    public function generateServiceWorker() {
        $cacheName = 'stacgate-cache-v' . date('YmdH');
        $staticAssets = $this->getStaticAssets();
        
        $sw = "
const CACHE_NAME = '{$cacheName}';
const STATIC_ASSETS = " . json_encode($staticAssets) . ";

// Installation du Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activation du Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Stratégie de mise en cache
self.addEventListener('fetch', (event) => {
    const { request } = event;
    
    // Stratégie Cache First pour les assets statiques
    if (request.destination === 'style' || 
        request.destination === 'script' || 
        request.destination === 'image') {
        event.respondWith(
            caches.match(request).then((response) => {
                return response || fetch(request);
            })
        );
        return;
    }
    
    // Stratégie Network First pour les API et pages
    if (request.url.includes('/api/') || request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Mettre en cache les réponses réussies
                    if (response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Fallback vers le cache en cas d'erreur réseau
                    return caches.match(request) || 
                           caches.match('/offline.html');
                })
        );
        return;
    }
    
    // Stratégie par défaut
    event.respondWith(
        caches.match(request).then((response) => {
            return response || fetch(request);
        })
    );
});

// Gestion des notifications push
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/assets/icons/icon-192x192.png',
            badge: '/assets/icons/badge-72x72.png',
            data: data.data || {},
            actions: data.actions || [],
            requireInteraction: data.requireInteraction || false
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Gestion des clics sur notifications
self.addEventListener('notificationclick', (event) => {
    const { notification, action } = event;
    
    event.notification.close();
    
    const urlToOpen = action ? 
        notification.data[action + '_url'] : 
        notification.data.url || '/dashboard';
    
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            // Chercher une fenêtre existante
            for (let client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // Ouvrir une nouvelle fenêtre
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Synchronisation en arrière-plan
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    // Synchroniser les données hors ligne
    try {
        const offlineData = await getOfflineData();
        if (offlineData.length > 0) {
            await syncOfflineData(offlineData);
        }
    } catch (error) {
        console.error('Erreur de synchronisation:', error);
    }
}

async function getOfflineData() {
    // Récupérer les données stockées hors ligne
    return [];
}

async function syncOfflineData(data) {
    // Synchroniser avec le serveur
    for (const item of data) {
        try {
            await fetch('/api/sync', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(item)
            });
        } catch (error) {
            console.error('Erreur sync item:', error);
        }
    }
}
";

        return $sw;
    }

    /**
     * Obtenir la liste des assets statiques à mettre en cache
     */
    private function getStaticAssets() {
        return [
            '/',
            '/dashboard',
            '/courses',
            '/offline.html',
            '/assets/css/style.css',
            '/assets/js/app.js',
            '/assets/icons/icon-192x192.png',
            '/assets/icons/icon-512x512.png'
        ];
    }

    /**
     * Obtenir la couleur de thème de l'établissement
     */
    private function getThemeColor() {
        if (!$this->establishmentId) {
            return '#3B82F6';
        }

        $theme = $this->database->findOne('establishment_themes', [
            'establishment_id' => $this->establishmentId,
            'is_active' => true
        ]);

        return $theme['primary_color'] ?? '#3B82F6';
    }

    /**
     * Enregistrer un token de notification push
     */
    public function subscribePushNotifications($userId, $subscription) {
        $subscriptionId = uniqid('sub_');
        
        $data = [
            'id' => $subscriptionId,
            'user_id' => $userId,
            'endpoint' => $subscription['endpoint'],
            'p256dh_key' => $subscription['keys']['p256dh'],
            'auth_key' => $subscription['keys']['auth'],
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => true
        ];

        return $this->database->insert('push_subscriptions', $data);
    }

    /**
     * Envoyer une notification push
     */
    public function sendPushNotification($userId, $title, $body, $data = []) {
        $subscriptions = $this->database->findAll('push_subscriptions', [
            'user_id' => $userId,
            'is_active' => true
        ]);

        $results = [];
        
        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->deliverPushNotification($subscription, $title, $body, $data);
                $results[] = $result;
            } catch (Exception $e) {
                // Désactiver les abonnements invalides
                $this->database->update('push_subscriptions', [
                    'is_active' => false
                ], ['id' => $subscription['id']]);
            }
        }

        return $results;
    }

    /**
     * Livrer une notification push
     */
    private function deliverPushNotification($subscription, $title, $body, $data) {
        // Dans une vraie implémentation, utiliser une librairie comme web-push
        // Ici on simule l'envoi
        
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'requireInteraction' => false
        ]);

        // Log de l'envoi
        $this->database->insert('notification_logs', [
            'id' => uniqid('notif_'),
            'user_id' => $subscription['user_id'],
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);

        return ['status' => 'sent', 'subscription_id' => $subscription['id']];
    }

    /**
     * Générer la page offline
     */
    public function generateOfflinePage() {
        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hors ligne - StacGate LMS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .offline-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 400px;
        }
        .offline-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        h1 {
            color: white;
            margin: 0 0 1rem 0;
        }
        p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0 0 1.5rem 0;
        }
        .retry-btn {
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 600;
        }
        .retry-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📶</div>
        <h1>Hors ligne</h1>
        <p>Vous n\'êtes pas connecté à Internet. Certaines fonctionnalités peuvent être limitées.</p>
        <button class="retry-btn" onclick="window.location.reload()">
            Réessayer
        </button>
    </div>
</body>
</html>';
    }
}