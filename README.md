# KasKo Construction

Marketing site and quote-request form for KasKo Construction, a residential construction and remodeling business serving Middle Georgia.

Canonical hostname: `kaskoconstruction.com` (the nginx config also redirects `www.kaskoconstruction.com`, `kaskomaintenance.com`, and `www.kaskomaintenance.com` to the canonical host).

## Stack

- PHP 8.4
- Symfony 7.4 LTS
- Doctrine ORM 3 / DBAL 4
- Symfony Mailer
- Twig 3
- Webpack Encore + Dart Sass, Bootstrap 4, Font Awesome 5, Stimulus, Vue 2
- MySQL 8.0
- nginx 1.25-alpine (production)
- Docker Compose deployment

## Local development

The application is designed to run under Docker Compose. A bare-metal setup with a matching PHP/MySQL install is also possible.

### With Docker

1. Copy `.env.prod.dist` to `.env.prod` and fill in real values (this file is gitignored).
2. Provide overrides for anything you want to differ from the committed `.env` / `.env.dev` defaults in `.env.local` and/or `.env.dev.local` (also gitignored).
3. Bring the stack up:
   ```bash
   docker compose up -d --build
   ```
4. Run pending migrations inside the app container:
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```

`compose.override.yaml` adds a [Mailpit](https://github.com/axllent/mailpit) container for local mail capture; it is picked up automatically by `docker compose up` and ignored by explicit `docker compose -f docker-compose.yml up` invocations.

### Without Docker

Prereqs: PHP 8.4 (with `pdo_mysql`, `intl`, `opcache`, `zip`, `xml`), Composer 2, MySQL 8.0, Node 22, Yarn 1.

```bash
composer install
yarn install
yarn build              # one-shot production build
# — or —
yarn watch              # webpack in watch mode

php bin/console doctrine:migrations:migrate
symfony server:start    # or: php -S 127.0.0.1:8000 -t public
```

Real credentials for local dev belong in `.env.local`. The committed `.env` and `.env.dev` contain only `!ChangeMe!` placeholders.

## Environment file layout

| File | Committed? | Purpose |
|---|---|---|
| `.env` | yes | Baseline defaults, safe placeholders only. |
| `.env.dev` | yes | `APP_ENV=dev` overrides, safe placeholders only. |
| `.env.test` | yes | Test-env values. |
| `.env.prod.dist` | yes | Template for production; every value is a `CHANGE_ME_*` placeholder. |
| `.env.local` | **no** | Real dev-time credentials. |
| `.env.dev.local` | **no** | Real dev-time env-specific overrides. |
| `.env.prod` | **no** | Real production credentials. Copied from `.env.prod.dist` on the deploy host and used by `docker-compose` via `env_file: .env.prod`. |

Do not commit real credentials.

## Key pages

- Home (`/`)
- About (`/about`)
- FAQ (`/faq`)
- Contact (`/contact`)
- Our Work (`/our-work`)
- Customer Reviews (`/reviews`)
- Terms of Service (`/terms-of-service`)
- Privacy Policy (`/privacy-policy`)
- Sitemap (`/sitemap`, `/sitemap.xml`)

The quote-request form posts to `POST /api/quotes` (`Kasko\Controller\QuotesController::postFormLead`), which persists a `FormLead` via Doctrine and sends a notification email via Symfony Mailer using `MAILER_DSN`.

## Deployment

Production runs the same image built from `Dockerfile` (multi-stage: Node 22 asset build → PHP 8.4-FPM runtime). nginx terminates TLS and forwards to `app:9000` over FastCGI.

A typical release, at a high level:

1. Populate `.env.prod` on the deploy host with real credentials.
2. Build the image: `docker build -t kasko:<sha or tag> .`
3. Bring the stack up: `docker compose up -d`.
4. Run pending Doctrine migrations inside the container.
5. Smoke-test the site, the quote-request form and its email delivery, HTTPS certificates for each configured hostname, and the sitemap.

Do not commit production credentials, API keys, SMTP passwords, or private keys to this repository.

## Business contact

**KasKo Construction**
Middle Georgia
(912) 614-5004
kasasbury@kaskoconstruction.com
