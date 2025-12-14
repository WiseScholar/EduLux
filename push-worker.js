self.addEventListener('push', function(event) {
    let data = {};
    try {
        data = event.data.json();
    } catch (e) {
        data = {};
    }

    const title = data.title || 'New Update from EduLux';
    const options = {
        body: data.body || 'You have a new notification. Tap to view.',
        icon: 'assets/img/logo_small.png',
        badge: 'assets/img/logo_small.png',
        data: {
            url: data.url || 'dashboard/student/index.php'
        }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const urlToOpen = event.notification.data.url || 'dashboard/student/index.php';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                for (const client of clientList) {
                    if (client.url.includes(urlToOpen) && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});