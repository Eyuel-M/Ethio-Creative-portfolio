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
php artisan migrate
php artisan demo:seed           # optional placeholder content (removable)
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

### Placeholder content

| Command | What it does |
|---|---|
| `php artisan demo:seed` | Adds placeholder creatives, portfolios, jobs, applications, projects, reviews and messages. All-or-nothing; repairs half-loaded data automatically. `--fresh` reloads it. |
| `php artisan demo:status` | Shows what placeholder and real content is in the database. |
| `php artisan demo:clear` | Removes **all** placeholder content. Real accounts and everything they created are kept. |

Placeholder accounts all use `@demo.test` emails — that's how they're found and removed. Don't use that
domain for real accounts.

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

## Typography rule (enforced)

The whole site uses **exactly two font families**:

| Family | Used for | Tailwind |
|---|---|---|
| Inter | all body and UI text, forms, tables, numbers | `font-sans` (default) |
| Inter Tight | headings (`h1`–`h3`) and display text | `font-display` |

- Both are self-hosted; their Latin subsets are **embedded in the stylesheet**, so text is never drawn in another font and never flickers between pages.
- `font-mono` / `font-serif` are aliased to Inter; numbers use `tabular-nums` instead of a monospace font.
- Don't add `@font-face` rules, font `<link>`s (Google Fonts, Bunny…), inline `font-family` styles or `font-[…]` utilities.
- `tests/Feature/FontPolicyTest.php` fails the build if this rule is broken.

## Deliberate placeholders (owner decisions still open — spec §10/§20)

- **Payment provider**: `SandboxGateway` stands in behind the `PaymentGateway` interface. Swap in the approved provider (e.g. Chapa, Telebirr merchant) once eligibility is confirmed.
- **File storage**: project files and media use local disks. R2 with presigned URLs should be enabled only after the Ethiopian data-protection / residency review (`MEDIA_DISK`, `PROJECT_FILES_DISK`).
- **Prices, quotas, retention days**: proposed defaults in `config/marketplace.php` and the seeder.
- **Disputes / evidence archive** (Phase 3, milestone 12) are not built yet; cancellation and file-event records are in place for it.
- `QUEUE_CONNECTION=sync` locally; use the database queue + a worker in production.

## Production note: cache built assets

Files in `public/build/assets` have content hashes in their names, so they can be cached forever. In the Nginx
server block add:

```nginx
location /build/assets/ {
    add_header Cache-Control "public, max-age=31536000, immutable";
    try_files $uri =404;
}
```

(`php artisan serve` sends no cache headers, so locally the fonts are re-fetched per page; the preload +
`font-display: block` setup keeps text from flashing in a fallback font either way.)

## Production note: image uploads

Every uploaded image (portfolio, covers, avatars, logos) goes through `App\Support\ImageCompressor`, which is built
to never visibly change an image (`tests/Feature/ImageQualityTest.php` measures it):

- **PNG** (screenshots, illustrations, UI text) is only re-saved **losslessly** (lossless WebP) — every pixel identical.
- **JPEG** photos always lose their metadata (GPS location, camera serials) **without re-compressing** — the rotation
  flag and colour profile are kept. They are re-encoded only as JPEG quality 95 (≥ 42 dB PSNR, visually identical)
  and only when that saves ≥ 30%, or when wider than 3840 px.
- Lossy WebP is never used: GD's encoder measurably softens sharp coloured edges at every quality setting.
- Colour profiles (Display P3 / Adobe RGB) are preserved, so colours don't shift. Tall pages are never shrunk.
- GIFs are untouched. The **untouched original** of anything that was changed is kept privately in
  `storage/app/private/originals/` (set `KEEP_IMAGE_ORIGINALS=false` to turn this off).

The case-study editor uploads each image on its own and sends it exactly as chosen (the browser only re-encodes, at
quality 95, a file too large for the server to accept).

On the server:

- PHP needs the **GD** extension with **WebP and JPEG** support, plus **exif** (for photo orientation).
- `upload_max_filesize = 20M`, `post_max_size = 25M`, and in Nginx `client_max_body_size 25m;`.
- Run Laravel's scheduler every minute — it deletes images uploaded but never saved into a project (after 24 h):

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Admin console

Sign in as an admin (demo: `admin@demo.test` / `password`) and open **Admin console** from the avatar menu, or go to `/admin`.

- **Roles** (`config/admin.php`): super admin, moderator, support, finance, dispute reviewer, analyst. Menus, routes and
  actions all follow that one map; super admins manage the team under **People → Admin team**.
- **Every action needs a reason** and lands in the audit log. Suspending or banning signs the member out everywhere.
- **Two-step sign-in**: admins confirm an emailed code every 12 hours. On by default in production; set
  `ADMIN_STEP_UP=true` to try it locally (the code is shown on screen outside production).
- **View as user** (support): read-only — any change is refused — with a banner and an audit entry.
- Members feed the queues from the site: **Report** links (jobs, profiles, portfolio projects), **business
  verification** and **Your data** (export / deletion requests) in Settings.

## Analytics (Insights)

First-party and anonymous: a random visitor cookie (`ec_vid`) and 30-minute visits — no IP addresses, no third-party
scripts. Each visit records where it came from (search engine, social platform, other website, email, UTM campaign or
direct), device, landing page, pages viewed, time actually spent on each page and how far it was scrolled. Searches,
appreciations, shares, applications, hires and payments are recorded as interaction events.

- **Admin → Insights**: Traffic & sources, Content & behaviour, Funnels, Marketplace health, and **Data export**
  (pseudonymous CSVs for your own analysis).
- Tag links you share with `?utm_source=telegram&utm_medium=social&utm_campaign=launch-week` to see campaigns.
- Switch tracking off, or make it respect Do Not Track, under **Admin → Settings & switches**.
- Demo analytics (90 days) load with `php artisan demo:seed` and are removed by `php artisan demo:clear`.
