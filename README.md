# PatientFlow

> **Healthtech booking demo** — Laravel 11 + Vue 3 (Inertia) + Stripe Payment Intents.
>
> Built in one evening as a portfolio piece for a Dutch freelance fullstack role (Laravel/Vue). Demonstrates that an agent-orchestrated workflow makes the framework switch from Node/React to PHP/Vue trivial.

## What it does

- **Patients** register, browse specialists (huisarts / fysiotherapeut / psycholoog), pick a 30-minute slot in the next 7 days, optionally describe their complaint, and pay the consultation fee via Stripe (test mode).
- **Specialists** register with a specialty + fee, then see a dashboard with monthly revenue, upcoming bookings, an inline AI-generated intake summary (Claude Haiku, optional), and a "mark as completed" action.
- **Stripe webhooks** flip an appointment from `pending_payment` → `confirmed` on `payment_intent.succeeded`, idempotently.

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11 (PHP 8.5), SQLite local / Postgres on prod |
| Frontend | Vue 3 Composition API + Inertia.js + TypeScript |
| Styling | Tailwind CSS (via Breeze scaffolding) |
| Auth | Breeze + custom role middleware (patient / specialist) |
| Payments | Stripe PHP SDK 20.x + `@stripe/stripe-js` Payment Element |
| AI (optional) | Anthropic Claude Haiku via queued job |
| Tests | Pest (40 tests, 108 assertions) |
| Deploy | Laravel Cloud (zero-config git push) |

## Live demo

Live URL: **_TODO: paste after Laravel Cloud deploy_**

Demo credentials (after running `php artisan migrate --seed`):

| Role | Email | Password |
|---|---|---|
| Patient | `patient@patientflow.test` | `password` |
| Specialist (Huisarts, €25) | `anna@patientflow.test` | `password` |
| Specialist (Fysiotherapeut, €35) | `pieter@patientflow.test` | `password` |
| Specialist (Psycholoog, €75) | `sara@patientflow.test` | `password` |

Stripe test cards: **`4242 4242 4242 4242`** (any future date / any CVC) succeeds, **`4000 0000 0000 0002`** declines.

## Local setup

Requirements: PHP 8.3+, Composer, Node 20+, SQLite.

```bash
git clone https://github.com/magicmoonboy/patientflow.git
cd patientflow

composer install
npm install

cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed

# Add real Stripe test keys (https://dashboard.stripe.com/test/apikeys):
# STRIPE_KEY=pk_test_...
# STRIPE_SECRET=sk_test_...
# STRIPE_WEBHOOK_SECRET=whsec_...  (from `stripe listen`)

# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev

# Terminal 3 - Stripe webhook forwarding (optional, for payment confirmation)
stripe listen --forward-to localhost:8000/webhooks/stripe
```

Visit `http://localhost:8000`.

## Running tests

```bash
./vendor/bin/pest
```

Covers:

- **Auth:** patient + specialist registration paths, dashboard redirects, role-gate 403s, guest → /login.
- **Booking:** specialist list, slot picker, appointment create, double-booking rejection, guest gate.
- **Stripe webhooks:** signed `payment_intent.succeeded` flips appointment to `confirmed`, `payment_intent.payment_failed` marks payment failed without touching appointment, invalid signature returns 400, duplicate succeeded webhook is idempotent.

PHP 8.5 emits a deprecation notice from Laravel 11's vendor config defaults — every assertion still passes (`40 tests, 108 assertions`).

## Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                  Breeze-shipped + extended RegisteredUserController
│   │   ├── Patient/
│   │   │   ├── BookingController       (list specialists, slot picker)
│   │   │   └── AppointmentController   (create appointment, payment page)
│   │   ├── Specialist/
│   │   │   ├── DashboardController     (revenue widget, bookings tables)
│   │   │   └── AppointmentController   (mark completed)
│   │   ├── PaymentController           (POST /payments/intent → Stripe PI)
│   │   └── StripeWebhookController     (POST /webhooks/stripe, signature-verified)
│   └── Middleware/
│       └── EnsureRole.php              (role:patient | role:specialist)
├── Models/
│   ├── User.php                        (role + relationships)
│   ├── SpecialistProfile.php
│   ├── Appointment.php                 (status constants)
│   └── Payment.php
└── Jobs/
    └── SummarizePatientIntakeJob.php   (optional Claude Haiku intake summary)

resources/js/
├── Pages/
│   ├── Auth/Register.vue               (role radio + conditional specialty/fee fields)
│   ├── Patient/
│   │   ├── Dashboard.vue
│   │   ├── Book/Index.vue              (specialist grid + specialty filter chips)
│   │   ├── Book/Specialist.vue         (day-grouped slot picker)
│   │   └── Appointment/Payment.vue     (Stripe Payment Element)
│   └── Specialist/
│       └── Dashboard.vue               (KPIs, upcoming/past tables)
└── ...
```

## Deploy

See [DEPLOY.md](./DEPLOY.md) for Laravel Cloud setup, env vars, Stripe webhook wiring, and custom domain.

## Linear

[Project board](https://linear.app/jamrlive/project/patientflow-bbe3daa5e932) — every PR commit references its JAM-XXXX ticket.

## License

MIT.
