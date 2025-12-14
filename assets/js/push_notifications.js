function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function sendSubscriptionToServer(subscription) {
    const key = subscription.getKey('p256dh');
    const auth = subscription.getKey('auth');

    const formData = new URLSearchParams();
    formData.append('action', 'subscribe');
    formData.append('endpoint', subscription.endpoint);
    formData.append('p256dh', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');
    formData.append('auth', auth ? btoa(String.fromCharCode.apply(null, new Uint8Array(auth))) : '');
    formData.append('csrf_token', CSRF_TOKEN);

    try {
        const response = await fetch(BASE_URL + 'subscribe_handler.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            console.error('Failed to save push subscription:', data.error || 'Unknown error');
        }
    } catch (err) {
        console.error('Network error saving subscription:', err);
    }
}

async function registerServiceWorkerAndSubscribe() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    try {
        const cleanKey = VAPID_PUBLIC_KEY
            .toString()
            .trim()
            .replace(/^['"]+|['"]+$/g, '')
            .replace(/\s/g, '');

        if (cleanKey.length < 80) {
            console.warn('VAPID key too short push notifications disabled');
            return;
        }

        const applicationServerKey = urlBase64ToUint8Array(cleanKey);

        const registration = await navigator.serviceWorker.register(BASE_URL + 'push-worker.js');

        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                return;
            }

            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey
            });
        }

        await sendSubscriptionToServer(subscription);

    } catch (err) {
        console.error('Push notification setup failed:', err);
    }
}

document.addEventListener('DOMContentLoaded', registerServiceWorkerAndSubscribe);