# Deploy to Laravel Cloud

Laravel Cloud is the official PaaS for Laravel apps — git-push deploys, zero config beyond env vars. This guide takes you from a fresh repo to a live URL in ~15 minutes.

## 1. Account + project

1. Go to **https://cloud.laravel.com** and sign in with the GitHub account that owns this repo (`magicmoonboy/patientflow`).
2. Click **Create Application** → connect repo `patientflow`, pick the `main` branch.
3. Pick the **EU (Frankfurt)** region for lowest latency to Dutch users.
4. Confirm. Laravel Cloud detects this is a Laravel project, sets up PHP 8.3+ runtime, Node, and a serverless Postgres database.

## 2. Environment variables

In **Project Settings → Environment**, set:

```bash
APP_NAME="PatientFlow"
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=nl
APP_URL=https://patientflow-XXXX.laravel.cloud   # filled in by Laravel Cloud after first deploy

# DB - Laravel Cloud provisions Postgres automatically, these are auto-injected:
DB_CONNECTION=pgsql
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD provided by Laravel Cloud

# Stripe - get from https://dashboard.stripe.com/test/apikeys
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...          # filled after step 4 below

# Optional - Anthropic for PF-9 (AI intake summary):
ANTHROPIC_API_KEY=sk-ant-...
```

`APP_KEY` is auto-generated on first deploy — leave it blank.

## 3. Deploy hook

In **Project Settings → Build & Deploy**, set the deploy commands:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force   # only on first deploy, comment out after
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

After the seed runs once, comment out `db:seed --force` so subsequent deploys don't wipe real data.

## 4. Push + smoke test

```bash
git push origin main
```

Watch the build log in Laravel Cloud. On green:

1. Open `https://patientflow-XXXX.laravel.cloud` (URL shown in dashboard).
2. Log in with `patient@patientflow.test` / `password`.
3. Book any specialist + slot → land on payment page.
4. Open Stripe test mode and confirm the PaymentIntent shows up in the dashboard.

If you see the Stripe Payment Element loading and accepting `4242 4242 4242 4242`, the deploy is healthy.

## 5. Stripe webhook

1. Go to **https://dashboard.stripe.com/test/webhooks** → **Add endpoint**.
2. Endpoint URL: `https://patientflow-XXXX.laravel.cloud/webhooks/stripe`
3. Events to send: `payment_intent.succeeded`, `payment_intent.payment_failed`.
4. After creating, click the endpoint → **Reveal signing secret** → copy the `whsec_...` value.
5. Paste it into Laravel Cloud's `STRIPE_WEBHOOK_SECRET` env var.
6. Trigger a test event from the Stripe dashboard ("Send test webhook" → `payment_intent.succeeded`) and verify the appointment in Laravel Cloud's database flips to `confirmed`.

## 6. Custom domain (optional)

In **Domains**, add `patientflow.huubkuiper.dev` (or any subdomain you control). Laravel Cloud provisions an SSL cert via Let's Encrypt automatically. Add a CNAME record at your DNS provider pointing to the Laravel Cloud target host shown in the dashboard.

## 7. Update README + cover letter

Once the URL is stable, edit the **"Live demo"** section in [README.md](./README.md) with the real URL, and reference both the live URL and this repo in the cover letter for the IndependentRecruiters #10527 vacature.

## Costs

Laravel Cloud's free beta tier covers this demo. If you outgrow it, expect ~$10-20/month for a serverless app with a small Postgres instance.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Build fails on `npm run build` | Bump `node` to 20+ in Laravel Cloud project settings. |
| 500 on first request | Run `php artisan migrate --force` manually via Laravel Cloud's web shell. |
| Webhook returns 400 | Check `STRIPE_WEBHOOK_SECRET` matches the Stripe dashboard's signing secret exactly (no `whsec_test_` prefix issues). |
| Stripe Payment Element doesn't render | Verify `STRIPE_KEY` (publishable, starts `pk_test_`) is set and the iframe isn't blocked by CSP. |
| 419 on POST | Laravel session driver — Laravel Cloud's default is `database`, ensure `SESSION_DRIVER=database` and the `sessions` table exists (auto-migrated). |
