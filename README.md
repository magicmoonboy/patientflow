# PatientFlow

Healthtech demo: Laravel 11 + Vue 3 (Inertia) + Stripe Payment Intents.

Built in one evening, agent-orchestrated, as a portfolio piece for a Laravel/Vue freelance role.

## Stack

- **Backend:** Laravel 11, PHP 8.5
- **Frontend:** Vue 3 (Composition API) + Inertia.js + TypeScript
- **Styling:** Tailwind CSS (via Breeze)
- **Payments:** Stripe (Payment Intents + webhook)
- **Tests:** Pest

## Status

Work in progress. Tickets tracked in Linear under the [PatientFlow project](https://linear.app/jamrlive/project/patientflow-bbe3daa5e932).

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
npm run dev   # in another terminal
```

Visit `http://localhost:8000`.

## Tests

```bash
./vendor/bin/pest
```

## Demo

Live URL: _coming soon (Laravel Cloud deploy)_
