const CACHE_NAME = 'stacgate-cache-v20250808';
const STATIC_ASSETS = [
    '/',
    '/dashboard',
    '/courses',
    '/offline.html',
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png'
];

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