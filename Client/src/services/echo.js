import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

const cleanHost = (value, fallback) => (value || fallback).replace(/^https?:\/\//, '');

export function getEcho(token) {
  if (echoInstance) {
    return echoInstance;
  }

  window.Pusher = Pusher;

  const scheme = import.meta.env.VITE_PUSHER_SCHEME || 'http';
  const wsHost = cleanHost(import.meta.env.VITE_PUSHER_HOST, window.location.hostname || '127.0.0.1');
  const wsPort = Number(import.meta.env.VITE_PUSHER_PORT || 6001);
  // pusher-js v8 expects a cluster option even if you use a custom host (Soketi).
  // Defaulting to "mt1" matches the typical local/default cluster used by Laravel.
  const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1';
  const authEndpoint = import.meta.env.VITE_BROADCAST_AUTH_ENDPOINT || 'http://127.0.0.1:8081/broadcasting/auth';

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'local',
    cluster,
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint,
    auth: {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    },
  });

  return echoInstance;
}

export function disconnectEcho() {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}
