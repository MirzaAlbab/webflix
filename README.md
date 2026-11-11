# webflix

A Laravel movie-catalog and subscription application with membership, transaction, and payment integrations.

[Repository](https://github.com/MirzaAlbab/webflix)

## Features

- Movie catalog, search, and category routes
- Subscription plans and checkout controllers
- Membership expiry command and event
- Laravel Fortify authentication and Midtrans integration

## Technology

`php` ^8.2 · `jenssegers/agent` ^2.6 · `laravel/fortify` ^1.25 · `laravel/framework` ^12.0 · `laravel/tinker` ^2.10.1 · `midtrans/midtrans-php` ^2.6

## Getting started

Install PHP compatible with `composer.json`, Composer, Node.js, and a database supported by the application.

```bash
git clone https://github.com/MirzaAlbab/webflix.git
cd webflix
```

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set `APP_URL` and the `DB_*` values in `.env` for your local database. Configure any mail, payment, or other external service used by this project.

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

Open the URL printed by Artisan. For ongoing frontend development, run `npm run dev` in another terminal.

## Development and checks

```bash
php artisan test
```

Review `tests/` for the current test scope. A configured test runner does not imply coverage of every business workflow.

## Project structure

- `app/` — Application source
- `config/` — Application configuration
- `database/` — Migrations, factories, and seeders
- `public/` — Public web assets and entry points
- `resources/` — Frontend templates and assets
- `routes/` — HTTP route definitions
- `tests/` — Test suite and support files

## Project notes

Configure MIDTRANS_MERCHANT_ID, MIDTRANS_SERVER_KEY, MIDTRANS_CLIENT_KEY, and MIDTRANS_IS_PRODUCTION in .env using config/midtrans.php. Use sandbox credentials during local payment testing. Uses Laravel 12.

## Framework

Built with [Laravel](https://laravel.com/docs). Framework packages and third-party dependencies retain their own licenses.

## License

No root `LICENSE` file was present in the reviewed repository. Consult the repository owner about reuse; dependencies and third-party assets retain their own terms.
