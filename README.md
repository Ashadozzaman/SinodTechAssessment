# Sales, Inventory & CRM System

A Laravel 12 + Vue 3 (Inertia) Sales, Inventory & CRM system, built as a
technical assessment on top of an existing role-based-auth starter kit.
Design rationale lives in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md);
working standards and conventions live in [`CLAUDE.md`](CLAUDE.md).

## Completed Features

- **Branches** — full CRUD, Admin/Manager view, Admin-only create/update/delete.
- **Products & Categories** — full CRUD with branch-scoped stock levels
  (`product_stocks`), searchable/paginated index.
- **Sales** — point-of-sale style flow: pick a customer, add products, checkout.
  Stock deduction is atomic with the sale (row-locked inside the DB
  transaction in `SaleService`), so overselling and lost stock under
  concurrent sales are both impossible by construction, not by convention.
- **Invoices** — a PDF invoice is generated and emailed to the customer via a
  queued job after a sale completes (Mailtrap SMTP sandbox).
- **Customers** — full CRUD, searchable/paginated, Employee view narrowed to
  only customers they're actively assigned to.
- **Lost Customer Detection** — a customer with no sale in
  `CRM_LOST_CUSTOMER_DAYS` (default 90) days is "lost." This is never stored
  as a flag — it's always computed live off the `sales` table
  (`Customer::scopeLost()`), so it can't drift out of sync. A scheduled
  command (`crm:flag-lost-customers`) and a dedicated `/lost-customers` page
  surface the list to Admin/Manager.
- **Employee Assignment** — Admins can assign a lost customer to an employee
  for follow-up (`customer_assignments`), guarded against a second concurrent
  active assignment per customer.
- **KPI Tracking** — when an assigned customer buys again, the assigned
  employee's `kpi_score` is incremented automatically (via a `SaleCompleted`
  listener, fully decoupled from the sale transaction) and the assignment is
  marked resolved. An Admin-only `/kpi-leaderboard` page ranks employees by
  score.
- **Re-engagement (bonus)** — Admin/Manager can send a lost customer a
  re-engagement message by email or SMS, with full history on the customer's
  page. Channel dispatch is an `Email`/`SmsReengagementChannel` pair behind a
  shared interface (Open/Closed — adding WhatsApp later is one new class).
- **Multi-branch (bonus)** — every user (except Admin), sale, and stock row
  is branch-scoped from day one.
- **E-Commerce Integration API (bonus)** — a Sanctum-token-gated
  `GET /api/v1/products` endpoint for third-party integrations (see below).
- **Role-based access control** — Admin / Manager / Employee, enforced
  entirely via route-level `permission:` middleware (Spatie
  `laravel-permission`), no Policy classes and no ad-hoc role checks in
  controllers.

### Deliberate Simplifications

- **SMS re-engagement is simulated.** There's no real SMS gateway wired up —
  `SmsReengagementChannel` logs the outgoing message and records the
  `customer_engagements` row with `status = simulated`. It is never marked
  `sent`, so this isn't mistakable for a working integration. Email
  re-engagement is real (same Mailtrap SMTP setup used for invoices).
- **`last_purchase_at` / lost status are derived, not cached** — always
  computed live from `sales` rather than stored and kept in sync, to avoid a
  class of staleness bugs at this scale (see `ARCHITECTURE.md` §4.2/§5.2).
- **No Repository layer** on top of Eloquent, and no generic "notifications"
  table — kept intentionally out of scope; see `CLAUDE.md` §9.

## Requirements

- PHP ^8.2
- Composer
- Node.js 18+ / npm
- MySQL 8 (or any Laravel-supported DB — SQLite works fine for local/testing)

## Setup

```bash
git clone <repo-url>
cd SinodTechAssessment

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mailtrap sandbox SMTP — used for invoice + re-engagement emails.
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password

# Days without a sale before a customer is considered "lost".
CRM_LOST_CUSTOMER_DAYS=90
```

Then:

```bash
php artisan migrate:fresh --seed
```

Seeding leaves the app fully explorable with no further manual setup —
branches, categories, products, stock, customers, historical sales
(including one product deliberately pinned to `quantity = 1` to demo the
insufficient-stock rejection immediately), lost-customer assignments, KPI
history, re-engagement history, and an API consumer token are all created.
Seeded login for every user is password `password`:

| Role | Email |
|---|---|
| Admin | `admin@example.com` |
| Manager (per branch) | `manager.<branch>@example.com` |
| Employee (per branch, x2) | `employee1.<branch>@example.com`, `employee2.<branch>@example.com` |

The seeder also prints a Sanctum API token to the console (from
`ApiConsumerSeeder`) — copy it for the API example below.

Finally, run the frontend and the app:

```bash
npm run dev      # Vite dev server (separate terminal), or `npm run build` for production assets
php artisan serve
```

Visit `http://127.0.0.1:8000` and log in with any seeded account above.

## E-Commerce Integration API

Third-party consumers authenticate with a Sanctum personal-access token
scoped to the `products:read` ability (not the web app's Spatie
permissions — the caller has no user session). The seeded token is printed
by `ApiConsumerSeeder` on every `migrate:fresh --seed`.

```bash
curl -H "Authorization: Bearer <token-from-seeder-output>" \
     -H "Accept: application/json" \
     http://127.0.0.1:8000/api/v1/products
```

Optionally scope `available_stock` to a single branch:

```bash
curl -H "Authorization: Bearer <token-from-seeder-output>" \
     -H "Accept: application/json" \
     "http://127.0.0.1:8000/api/v1/products?branch_id=1"
```

Each entry returns `sku`, `name`, `price`, and `available_stock` (summed
across branches unless `branch_id` is given). The endpoint is versioned
(`/api/v1`), paginated (15/page), and rate-limited at 60 requests/minute per
token via Laravel's `throttle:api`.

## Running Tests

```bash
php artisan test
```

122 tests covering, among other business-critical paths: overselling
rejection with zero partial stock deduction, concurrent-sale row locking
across genuinely separate DB connections, the lost-customer boundary scope,
KPI awarding exactly once per qualifying sale, invoice email queuing, and
Admin/Manager/Employee permission boundaries on every module's routes.

## Tech Stack

Laravel 12, Vue 3 + Inertia.js + TypeScript, Tailwind + shadcn-vue, Spatie
`laravel-permission`, Laravel Sanctum, `barryvdh/laravel-dompdf`. Full
rationale in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).
