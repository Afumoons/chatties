# Laravel React Starter Kit

## Realtime Chat Setup

This project uses [laravel/reverb](https://github.com/laravel/reverb) with Laravel Echo for realtime messaging.

To start the local Reverb websocket server:

```bash
php artisan reverb:start --host=127.0.0.1 --port=8080
```

Make sure your `.env` has the following (see `.env.example`):

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
```

Client side, `resources/js/app.tsx` configures Echo with broadcaster `'reverb'`.

### Chat UI

Once authenticated you can click the **Chat** link in the sidebar to open the chat interface. The page displays a list of other users and a conversation panel. Messages are fetched from the server, sent via AJAX, and delivered in real‑time using Reverb/Echo. Typing indicators and presence are shown automatically.


For production or scaling, consult the package docs; the server can be managed with Supervisor or systemd.
