import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/*
|--------------------------------------------------------------------------
| Laravel Echo (Pusher) bootstrap
|--------------------------------------------------------------------------
|
| Echo is created once per page load. The key / cluster come from Vite
| (which reads them from .env via the VITE_PUSHER_* variables). The
| Pusher client is told to authenticate against our /broadcasting/auth
| route, which is the standard Laravel CSRF-protected endpoint for
| private channel subscriptions.
|
*/

let echoInstance = null;

export function getEcho() {
    if (echoInstance) return echoInstance;

    echoInstance = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
        encrypted: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    return echoInstance;
}
