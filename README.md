# Ethio Creative — portfolio-first creative freelance marketplace

A Laravel 13 monolith (Blade + Tailwind CSS v4 + Alpine.js, as the spec recommends) built from the
*Freelance Marketplace Product & Technical Specification (Revision 1.1)*.

Freelancers publish structured case-study portfolios and apply to jobs. Clients pay the platform to
publish jobs, screen applicants with custom questions, chat, and confirm a versioned agreement.
**Only after both parties confirm the same agreement version** does the private project workspace
open and the freelancer's CBE / Telebirr details become visible to that client. The platform never
holds, escrows or takes commission from project payments, and never sells contact access.

## Quick start

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # synthetic demo data + generated artwork
php artisan storage:link
npm run build
php artisan serve
```

Demo logins (password `password`):

| Role | Email |
|---|---|
| Freelancer (Hana) | `freelancer@demo.test` |
| Client (Buna House Coffee) | `client@demo.test` |
| Super admin | `admin@demo.test` |

Run the acceptance tests: `php artisan test`

## What's implemented

| Area | Highlights |
|---|---|
| Public site | Home, talent directory with filters, public profiles, case-study pages, job board (filters, facets, grid/list), job detail, pricing, how-it-works/FAQ |
| Accounts | One login, freelancer and/or client workspaces with a switcher, email verification, password reset, rate-limited auth |
| Portfolio builder | Ordered text / image / YouTube-Vimeo blocks, drafts & publishing, cover images, plan quotas (3 free, 30 Professional) |
| Jobs & screening | Job builder with 7 screening-question types and per-category templates; questions lock once answers exist |
| Applications | Proposal, required answers, portfolio picks (no re-upload), status machine with history, comparison table across answers |
| Invitations & chat | Direct invitations (accept = agree to discuss), conversations tied to a job/application/project |
| Agreements | Immutable versions with content hash; both parties must confirm the same version; changes supersede and require re-confirmation |
| Project workspace | Milestones (records only), status flow incl. two-sided cancellation, file exchange with SHA-256, download events and receipt confirmation, timeline, mutual reviews |
| Private disclosure | `FreelancerPaymentDetails` encrypted at rest, masked in settings, revealed only via `App\Actions\ProjectDisclosure` after dual confirmation, every reveal audit-logged |
| Platform billing | Server-priced orders, sandbox hosted checkout, HMAC-signed idempotent webhooks; entitlements granted only from verified events |
| Admin | Overview metrics, user status with reasons, job moderation/featuring, orders, reports, audit log |

## Deliberate placeholders (owner decisions still open — spec §10/§20)

- **Payment provider**: `SandboxGateway` stands in behind the `PaymentGateway` interface. Swap in the approved provider (e.g. Chapa, Telebirr merchant) once eligibility is confirmed.
- **File storage**: project files and media use local disks. R2 with presigned URLs should be enabled only after the Ethiopian data-protection / residency review (`MEDIA_DISK`, `PROJECT_FILES_DISK`).
- **Prices, quotas, retention days**: proposed defaults in `config/marketplace.php` and the seeder.
- **Disputes / evidence archive** (Phase 3, milestone 12) are not built yet; cancellation and file-event records are in place for it.
- `QUEUE_CONNECTION=sync` locally; use the database queue + a worker in production.
