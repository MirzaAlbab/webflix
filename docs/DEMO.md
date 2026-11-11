# Webflix demo

The local demo includes eight fictional films with local poster assets and a preview page. It contains no movie footage and makes no payment requests in its default mode.

## Docker

```bash
docker compose -f compose.demo.yaml up --build
```

Open http://localhost:8001 after the server-ready message. A one-shot Node service builds frontend assets into a named volume, then the PHP application starts. Source files are read-only in the asset builder. The application runs as an unprivileged user and stores its SQLite demo database in a separate named volume. Restarting generates a new application key, so sign in again after a restart.

All three accounts initially use `demo-password`:

| Email | Starting state |
| --- | --- |
| `viewer@webflix.example` | Active Standard plan, 1080p preview |
| `new@webflix.example` | No membership |
| `expired@webflix.example` | Expired membership |

The seeder does not erase purchases or reactivate expired sample memberships on restart. It is separate from the older `DatabaseSeeder` and only runs with demo mode enabled in a local/testing sandbox.

To stop while keeping data:

```bash
docker compose -f compose.demo.yaml down
```

To reset this Compose project's fictional demo database:

```bash
docker compose -f compose.demo.yaml down --volumes
docker compose -f compose.demo.yaml up
```

## Walkthrough

1. Sign in as `viewer@webflix.example`. Browse Movie and open Night Signal. The local preview shows the Standard plan's 1080p resolution.
2. Sign in as the new or expired account and open `/movies/night-signal`. The application redirects to plans.
3. Choose Basic. Review the Rp49.999 subtotal, Rp6.000 PPN, and Rp55.999 total.
4. Continue to the simulated payment page. Cancellation leaves the account without access.
5. Create another checkout and simulate success. The receipt confirms activation; the Basic preview now shows 720p.

The simulated payment screen is authenticated, belongs to one account, and is unavailable in production or when using the Midtrans sandbox mode below.

## Native PHP setup

Use a fresh checkout with PHP compatible with `composer.lock`, Composer, Node.js, and npm:

```bash
composer install
npm ci
npm run build
cp .env.demo.example .env
mkdir -p storage/demo
touch storage/demo/database.sqlite
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder
php artisan serve --port=8001
```

The sample database path is relative to the project root; use an absolute `DB_DATABASE` path if your server starts elsewhere.

## Midtrans sandbox payments

To exercise the actual Midtrans sandbox instead of local simulation, configure these values in your untracked `.env`:

```dotenv
APP_ENV=local
WEBFLIX_DEMO=true
DEMO_SIMULATE_PAYMENTS=false
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_MERCHANT_ID=your-sandbox-merchant-id
MIDTRANS_SERVER_KEY=your-sandbox-server-key
MIDTRANS_CLIENT_KEY=your-sandbox-client-key
```

Use credentials from your own Midtrans sandbox account. Never place keys in screenshots, commits, or chat. Run `php artisan config:clear` and restart the server after changing settings. Compose also accepts the four payment environment variables shown in `compose.demo.yaml`; recreate its container after changing them.

Configure Midtrans's payment-notification URL to your reachable development endpoint:

```text
https://YOUR-DEVELOPMENT-HOST/api/transaction/payment/callback
```

The localhost Compose binding is not publicly reachable by itself. Use your own development forwarding setup if you need provider callbacks. The sandbox flow uses Snap and the authenticated Midtrans status API; local simulation controls are disabled. The fictional catalog and membership-gated preview remain available.

Browser success callbacks only open the receipt/status page. Membership becomes active after the server verifies the notification signature, checks the provider's current status, and validates order ID, currency, and amount. A pending receipt updates when verification completes.

The automated suite mocks Midtrans responses and does not require credentials. A live sandbox round trip must be checked with your own credentials and reachable callback URL.

## Browser checks and screenshots

With the default simulated demo running:

```bash
npm ci
npx playwright install chromium
npm run test:browser
```

The tests create unique fictional accounts for checkout flows and change only their demo orders. They also check the seeded viewer, new, and expired account states.

To refresh README screenshots on a POSIX shell:

```bash
CAPTURE_DEMO_SCREENSHOTS=1 npm run test:browser
```

Images are saved under `docs/screenshots/`. Set `DEMO_URL` for another demo address, or `PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` to use an existing Chromium installation.

This Compose setup is for local evaluation. It uses a PHP development server and known demo accounts; it does not configure production hosting.
