# PROMPT_PLAYBOOK.md — Step-by-Step Claude Code Prompts (Single Session Log)

## How to Use This File

This is one continuous file containing every prompt for the project, in
order. Work through it **one prompt at a time**:

1. Copy only the next `### Prompt N` block into Claude Code.
2. Let it finish completely (code written, migrations run, tests passing).
3. **Stop. Do not paste the next prompt yet.**
4. Review the diff yourself. Test the feature in the browser if it's
   UI-facing.
5. Give feedback in the same Claude Code session — fixes, changes, or just
   "looks good" — and let Claude Code apply it before moving on.
6. Once you're satisfied: `git commit -m "feat(module): ..."`, then check
   the box below and move to the next prompt.
7. Only after a prompt is committed do you move to the next one. Never run
   two prompts back to back without a review pass in between — that's how
   architecture drifts from `ARCHITECTURE.md` and `CLAUDE.md` unnoticed.

Both `ARCHITECTURE.md` and `CLAUDE.md` must exist in the repo root before
Prompt 0. Every prompt below assumes Claude Code will re-read them, not rely
on memory of an earlier turn — if a session gets long, tell it to re-read
both files before continuing.

## Progress Checklist

- [ ] Prompt 0 — Orientation
- [ ] Prompt 1 — Roles & Branch Foundation
- [ ] Prompt 2 — Product Catalog
- [ ] Prompt 3 — Multi-Branch Inventory
- [ ] Prompt 4 — Customers Module
- [ ] Prompt 5 — Sales / POS Core Flow
- [ ] Prompt 6 — Customer Purchase History
- [ ] Prompt 7 — Invoice Generation & Email
- [ ] Prompt 8 — Lost Customer Detection
- [ ] Prompt 9 — Employee Assignment
- [ ] Prompt 10 — KPI Tracking
- [ ] Prompt 11 — Re-engagement
- [ ] Prompt 12 — E-Commerce API
- [ ] Prompt 13 — Comprehensive Seeders Pass
- [ ] Prompt 14 — Feature Test Coverage Review
- [ ] Prompt 15 — README & Submission Polish

---

### Prompt 0 — Orientation

```
Read ARCHITECTURE.md and CLAUDE.md fully before doing anything else.

Then inspect the existing starter kit:
- What auth system is in place (Breeze/Jetstream/custom)?
- Is there already a roles/permissions table or package installed
  (e.g. spatie/laravel-permission)? Show me the relevant models/migrations.
- What Laravel version, and does it use bootstrap/app.php or
  Http/Kernel.php for middleware registration?
- What's the current users table schema?

Give me a short summary of what exists so we can decide what to extend vs.
build fresh, before writing any code. Don't create or modify files yet.
```

**STOP.** Review the summary. Confirm what already exists matches your
assumptions before moving on — this decides how Prompt 1 gets adapted.

---

### Prompt 1 — Roles & Branch Foundation

```
Following ARCHITECTURE.md §4.2 and §4.3 and CLAUDE.md, and based on what you
found in the orientation step:

1. If no permissions package exists, install and configure
   spatie/laravel-permission. Seed three roles: Admin, Manager, Employee.
2. Create the `branches` table/migration/model as specified in
   ARCHITECTURE.md.
3. Extend the `users` table with `branch_id` (nullable, FK to branches) and
   `kpi_score` (unsigned int, default 0) via a new migration — don't edit
   the original starter kit migration.
4. Register a BranchPolicy: only Admin can create/edit/delete branches;
   Manager/Employee can view their own branch.

Write a BranchSeeder with 3 realistic branches (different cities).
Run migrations and the seeder, confirm it works, then stop.
```

**STOP.** Verify roles seeded correctly and branches migrate/seed cleanly.
Give feedback, commit, then continue.

---

### Prompt 2 — Product Catalog

```
Following ARCHITECTURE.md §4.2, build the product catalog:

1. Migrations/models for `categories` and `products` (no stock_quantity
   column on products — stock is branch-scoped, see Prompt 3).
2. StoreProductRequest / UpdateProductRequest with validation (unique SKU,
   price numeric min 0, etc).
3. ProductController (index/create/store/edit/update/destroy) — thin,
   delegates nothing complex yet (no service needed for plain CRUD).
4. ProductPolicy: Admin/Manager can manage products, Employee can only view.
5. Inertia pages under resources/js/Pages/Products for list/create/edit,
   matching the starter kit's existing UI conventions (check an existing
   page for the component/layout patterns before building new ones).
6. CategorySeeder (5 categories) and ProductSeeder (20+ realistic products
   across those categories, plausible SKUs and prices).

Run the test suite and seeders, confirm the CRUD UI works end to end.
```

**STOP.** Click through the CRUD UI yourself. Give feedback, commit, then
continue.

---

### Prompt 3 — Multi-Branch Inventory

```
Following ARCHITECTURE.md §4.2 and §5.5:

1. Create the `product_stocks` table (product_id, branch_id, quantity,
   unique on the pair) and ProductStock model.
2. Build InventoryService with:
   - getStock(Product $product, Branch $branch): int
   - adjustStock(Product $product, Branch $branch, int $delta): void
     (used for manual stock adjustments/restocking, wrapped in a transaction
     with lockForUpdate)
3. Add a "Stock" tab/section to the Product show/edit page showing
   per-branch quantities, editable by Admin/Manager only (enforce via
   ProductPolicy or a dedicated check).
4. ProductStockSeeder: seed realistic stock quantities for every
   product/branch combination.

Write a Feature test confirming adjustStock() correctly increments/decrements
and respects the row lock under a simulated concurrent update.
```

**STOP.** Confirm the stock tab renders correctly per branch. Give
feedback, commit, then continue.

---

### Prompt 4 — Customers Module

```
Following ARCHITECTURE.md §4.2:

1. Migration/model for `customers` (name, email nullable+unique-when-present,
   phone unique, address).
2. StoreCustomerRequest/UpdateCustomerRequest.
3. CustomerController + Inertia pages (list with search by name/phone,
   create/edit, show page — show page is a placeholder for now, purchase
   history comes in Prompt 6).
4. CustomerPolicy: Admin/Manager full access; Employee can view customers
   assigned to them (assignment logic comes in Prompt 9, so for now Employee
   can view all — we'll tighten this later).
5. CustomerSeeder: 40+ realistic customers with varied created_at dates
   (spread over the last 12 months, since Prompt 8's lost-customer logic
   needs some genuinely old ones to detect).

Confirm CRUD works, run tests.
```

**STOP.** Give feedback, commit, then continue.

---

### Prompt 5 — Sales / POS Core Flow (the critical path)

```
This is the most important prompt in the project — read ARCHITECTURE.md
§5.1 carefully before writing code.

1. Migrations/models for `sales` and `sale_items` per §4.2.
2. Create app/Exceptions/InsufficientStockException.php.
3. Build SaleService::create(array $data): Sale that:
   - Wraps everything in DB::transaction()
   - For each line item, locks the relevant product_stocks row
     (lockForUpdate), checks sufficient quantity, throws
     InsufficientStockException if not (transaction rolls back — no partial
     sale, no partial stock deduction)
   - Deducts stock, creates the sale + sale_items rows with unit_price
     snapshotted from the product's current price
   - Generates a unique invoice_number
   - Fires a SaleCompleted event after commit (don't fire inside the
     transaction — fire after, once we know it's committed)
4. StoreSaleRequest validating customer_id, branch_id, and an array of
   {product_id, quantity}.
5. SaleController@store calling SaleService, catching
   InsufficientStockException and returning a clean validation-style error
   back to Inertia.
6. A simple POS-style Inertia page: pick customer, pick branch, add products
   with quantity, see running total, submit.
7. Register (empty, no-op for now) listeners for SaleCompleted — we'll fill
   these in over the next few prompts. Just get the event wired up and
   confirm it fires.

Write Feature tests: successful sale deducts stock correctly; a sale
exceeding available stock is fully rejected with zero stock change; the
SaleCompleted event fires exactly once on success.
```

**STOP.** This is the riskiest prompt — actually try to oversell a product
in the UI and confirm it's rejected cleanly with no stock change. Give
feedback, commit, then continue.

---

### Prompt 6 — Customer Purchase History

```
Following ARCHITECTURE.md §4.2's note on derived (not stored) purchase data:

1. Add relationships: Customer::sales(), Sale::items(), etc. if not already
   present from Prompt 5.
2. Add query scopes/accessors on Customer:
   - lastPurchaseAt(): ?Carbon (max sale_date across sales)
   - purchaseFrequency(): int (count of sales)
3. Build out the Customer show page: full purchase history table (sales,
   dates, totals, line items), last purchase date, frequency — all computed
   live via the relationships/scopes above, not stored columns.

Write a test confirming lastPurchaseAt/purchaseFrequency return correct
values against seeded sales data.
```

**STOP.** Give feedback, commit, then continue.

---

### Prompt 7 — Invoice Generation & Email (bonus)

```
Following ARCHITECTURE.md §4.2 (invoices table) and §5.1:

1. Install barryvdh/laravel-dompdf if not present.
2. Migration/model for `invoices` (sale_id unique, invoice_number,
   pdf_path, emailed_at).
3. Build GenerateInvoicePdfJob: renders a Blade invoice template to PDF,
   stores it in storage/app/invoices, records the path on the Invoice model.
4. Build SendInvoiceEmailJob (chained after the PDF job): sends the
   generated PDF as a Mailable via Mailtrap SMTP, sets emailed_at.
5. Wire both into SaleCompleted via GenerateInvoiceListener and
   SendInvoiceEmailListener (per ARCHITECTURE.md §5.1) — replace the no-op
   listeners from Prompt 5.
6. Configure Mailtrap credentials via .env.example placeholders (don't
   commit real credentials).

Use Mail::fake() and Queue::fake() in tests — confirm the jobs are
dispatched with correct data, without hitting real Mailtrap in the suite.
```

**STOP.** Set your real Mailtrap credentials locally and confirm an actual
email arrives with a correct-looking PDF attached. Give feedback, commit,
then continue.

---

### Prompt 8 — Lost Customer Detection

```
Following ARCHITECTURE.md §5.2:

1. Add config/crm.php with 'lost_customer_days' => env('CRM_LOST_CUSTOMER_DAYS', 90).
2. Add Customer::scopeLost() exactly as described in ARCHITECTURE.md §5.2.
3. Build a "Lost Customers" Inertia page listing customers matching the
   scope, with last purchase date shown, accessible to Admin/Manager.
4. Add an Artisan command crm:flag-lost-customers that runs the scope (for
   now, just logs/outputs the count — we're not storing a status flag,
   per ARCHITECTURE.md). Schedule it daily in the console kernel /
   routes/console.php.

Write a test with seeded sales at controlled dates confirming the scope
correctly includes a customer whose last sale was 91 days ago and excludes
one whose last sale was 89 days ago (boundary test).
```

**STOP.** Give feedback, commit, then continue.

---

### Prompt 9 — Employee Assignment

```
Following ARCHITECTURE.md §4.2 and §5.3:

1. Migration/model for `customer_assignments` (customer_id, employee_id,
   assigned_by, status enum, assigned_at, resolved_at).
2. Build CrmService::assignCustomer(Customer $customer, User $employee, User
   $assignedBy): CustomerAssignment — guards against creating a second
   `active` assignment for the same customer (throw a clear exception if one
   already exists).
3. Add an "Assign to employee" action on the Lost Customers page (Prompt 8),
   Admin-only, with a dropdown of Employee-role users.
4. Update CustomerPolicy: an Employee can now view a customer if they have
   an active assignment for them (tighten the "all customers" placeholder
   from Prompt 4).

Write a test confirming a second active assignment attempt is rejected, and
that resolving an assignment allows a new one to be created.
```

**STOP.** Give feedback, commit, then continue.

---

### Prompt 10 — KPI Tracking

```
Following ARCHITECTURE.md §5.1 and §5.3 — this is the payoff for the event
architecture set up in Prompt 5.

1. Build AwardEmployeeKpiListener, triggered by SaleCompleted:
   - Look up an active customer_assignments row for this sale's customer
   - If found: increment the assigned employee's kpi_score by a fixed
     amount (make this a config value, e.g. config('crm.kpi_award_points'),
     default 10), mark the assignment 'resolved' with resolved_at = now()
   - If no active assignment exists, do nothing
2. Register the listener on SaleCompleted (replacing whatever placeholder
   existed from Prompt 5).
3. Add a small "Employee KPI Leaderboard" Inertia page (Admin-only) showing
   users with the Employee role sorted by kpi_score.

Write a test: seed a customer with an active assignment, process a sale for
that customer, confirm kpi_score increments by exactly the configured
amount and the assignment becomes 'resolved'. Also confirm a sale for an
unassigned customer does NOT change any kpi_score.
```

**STOP.** Manually walk through: assign a lost customer to an employee,
make a sale for them, confirm KPI increments. Give feedback, commit, then
continue.

---

### Prompt 11 — Re-engagement (Simulated Email/SMS)

```
Following ARCHITECTURE.md §5.4:

1. Migration/model for `customer_engagements` (customer_id, channel enum
   [email, sms], message, status enum [sent, failed, simulated], sent_at,
   triggered_by nullable FK to users).
2. Build CrmService::sendReengagement(Customer $customer, string $channel,
   string $message, ?User $triggeredBy): CustomerEngagement.
   - channel = email: dispatch a real Mailable via Mailtrap, status 'sent'
     (or 'failed' if it throws)
   - channel = sms: no real gateway available — log the message and mark
     status 'simulated'. Make this distinction visible in a code comment
     and in the README later.
3. Add a "Send re-engagement" action on the Lost Customers page, letting
   Admin/Manager pick channel + a message (with a sensible default
   template), visible engagement history on the Customer show page.

Test: confirm email channel dispatches Mail::fake()'d mail and creates a
'sent' row; sms channel creates a 'simulated' row without attempting real
delivery.
```

**STOP.** Give feedback, commit, then continue.

---

### Prompt 12 — E-Commerce Integration API (bonus)

```
Following ARCHITECTURE.md §6:

1. Install/enable Laravel Sanctum if not already present.
2. Create an ApiConsumerSeeder that creates a dedicated user (or a
   lightweight "api_consumers" record if you prefer not to reuse the users
   table) and issues a personal access token scoped to a 'products:read'
   ability. Print the plaintext token when the seeder runs so it can be
   used for testing.
3. Build GET /api/v1/products (rate-limited via throttle:api, Sanctum
   auth required, ability check for 'products:read'), returning
   ProductApiResource: sku, name, price, available_stock (summed across
   branches, or filtered by an optional ?branch_id= query param).
4. Paginate the endpoint.

Write a Feature test hitting the endpoint with a valid token (asserting
correct shape/values) and confirming a request with no token or wrong
ability returns 401/403.
```

**STOP.** Hit the endpoint yourself with curl/Postman using the seeded
token. Give feedback, commit, then continue.

---

### Prompt 13 — Comprehensive Seeders Pass

```
Review every seeder built in Prompts 1-12. Following the submission
requirement that "the application should be ready for testing immediately
after running the seeders":

1. Create/confirm a DatabaseSeeder that calls all seeders in correct
   dependency order (branches → categories → products → product stocks →
   customers → users/employees → sales → assignments → engagements → api
   consumer).
2. Make sure seeded sales data is realistic enough to exercise every
   feature out of the box: some customers should already be "lost" (no
   sales in 90+ days), at least a few should have active assignments with
   completed follow-up sales (so the KPI leaderboard isn't empty), and
   stock levels should include at least one near-zero product to
   demonstrate the insufficient-stock rejection.
3. Confirm `php artisan migrate:fresh --seed` runs clean from empty.

Run the full test suite one more time end to end.
```

**STOP.** Run `php artisan migrate:fresh --seed` yourself on a clean DB and
click through the whole app. Give feedback, commit, then continue.

---

### Prompt 14 — Feature Test Coverage Review

```
Read through CLAUDE.md §6 (Testing Expectations). Audit the existing test
suite against that checklist and fill any gaps — in particular:
- Concurrent-sale stock race condition test, if not already covered
- Any Policy (authorization) that doesn't yet have a test confirming
  Employee/Manager/Admin boundaries
- Boundary test for the lost-customer scope, if skipped earlier

Don't add tests for the sake of coverage numbers — only for the
business-critical paths listed in CLAUDE.md.
```

**STOP.** Review the new tests added — make sure they're testing real
behavior, not just asserting trivial truths. Give feedback, commit, then
continue.

---

### Prompt 15 — README & Submission Polish

```
Write the README.md required by the assessment, covering:
- Completed features list (pull from the running list kept per CLAUDE.md §8)
- Explicitly note which bonus features were implemented (multi-branch,
  email invoices, e-commerce API) and any deliberate simplifications
  (e.g. SMS re-engagement is simulated, not a real gateway)
- Step-by-step setup: clone, composer install, npm install, .env setup
  (including Mailtrap SMTP placeholders and CRM_LOST_CUSTOMER_DAYS),
  php artisan key:generate, migrate, seed, npm run dev / build, serve
- API usage example for the e-commerce endpoint (example curl with the
  seeded token)
- How to run the test suite

Then do a final pass: confirm no .env or real credentials are committed,
confirm migrate:fresh --seed works from a clean database, confirm the app
boots and every module from the playbook is reachable in the UI.
```

**STOP.** Read the README as if you were the interviewer seeing this repo
cold. Give feedback, commit, then you're done.

---

## Notes for the Interview

Since you'll be asked to defend these decisions live, the parts most worth
having a crisp verbal answer ready for are:

- Why stock lives on `product_stocks`, not `products` (§4.2 note)
- Why purchase history/last-purchase-date is derived, not cached (§4.2 note)
- Why the sale transaction and the event listeners are split the way they
  are (§5.1 — atomicity vs. side effects)
- The concurrency handling on stock deduction (`lockForUpdate`)
