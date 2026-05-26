# GLOBALO — Growth Loop Architecture

## 1. Overview

The Growth Loop System generates **automatic, SEO-optimized, shareable content** that drives traffic and signups:

```
[Expert profiles] + [Job pages] + [Blog] → Indexed by Google
        ↓
[Social sharing] + [Referral links] + [Achievement cards] → Viral loop
        ↓
[Analytics] + [Notifications] → Optimize and re-engage
```

---

## 2. Public Expert Profile Pages (SEO)

**URL pattern:** `/expert/{slug}`  
**Example:** `/expert/amadou-flutter-developer`

| Feature | Implementation |
|--------|-----------------|
| SEO title | `<title>{expert title} — Expert {skill} — GLOBALO` |
| Meta description | First 160 chars of description + "Réservez une session" |
| Structured data | Schema.org `Person` + `Service` + `Offer` |
| Rating | `note_moyenne` + `nombre_avis` (stars) |
| Skills | From `expert_competences` + `competences` |
| Hourly rate | `tarif_horaire` + currency |
| Availability | `disponible` (badge) |
| Booking button | CTA → `/auth/inscription` or `/client/demandes` (if logged in client) |
| Share buttons | Facebook, X (Twitter), LinkedIn, Copy link |

**Slug generation:** `slugify(prenom + '-' + titre)` e.g. `amadou-flutter-developer`. Stored in `profils_experts.slug`, unique.

**Routing:** First segment `expert`, second segment = slug. Controller resolves expert by slug, returns 404 if not found or not public.

---

## 3. Automatic Job Pages (SEO)

**URL pattern:** `/jobs/{slug}`  
**Example:** `/jobs/flutter-bug-fix`

| Feature | Implementation |
|--------|-----------------|
| Job title | `demandes_assistance.titre` |
| Description | `description` (full) |
| Category | From `competence_id` → `competences.nom` |
| Related experts | Experts with same `competence_id`, available, limit 6 |
| Structured data | Schema.org `JobPosting` (or `Service` if B2B task) |
| Share buttons | Same as expert pages |

**Slug generation:** On demand creation, `slugify(titre)` + `-{id}` for uniqueness e.g. `flutter-bug-fix-42`. Stored in `demandes_assistance.slug` (new column). Only **open** or **in progress** jobs can be public (configurable); or all for SEO.

**Routing:** First segment `jobs`, second = slug. Controller resolves job by slug.

---

## 4. Social Media Sharing

Every expert and job page includes:

- **OpenGraph:** `og:title`, `og:description`, `og:image`, `og:url`, `og:type`
- **Twitter Card:** `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`
- **Preview image:** Expert avatar or default OG image; job page = default or category image

**Share buttons:**

- Facebook: `https://www.facebook.com/sharer/sharer.php?u={url}`
- X (Twitter): `https://twitter.com/intent/tweet?url={url}&text={title}`
- LinkedIn: `https://www.linkedin.com/sharing/share-offsite/?url={url}`
- Instagram: No direct share URL; "Copy link" for Stories or bio link.

---

## 5. Referral System

**Code format:** `GLOBALO-{id}` or `GLOBALO-{5 alphanumeric}` for readability.

**Schema (existing `parrainages` extended):**

- `referral_code` VARCHAR(20) UNIQUE — e.g. `GLOBALO-12345`
- `parrain_id` (referrer)
- `filleul_id` (referred user, set on registration)
- `reward_status` ENUM('pending', 'credited_parrain', 'credited_filleul', 'both_credited')

**Flow:**

1. User gets link: `{BASE}/auth/inscription?ref=GLOBALO-12345`
2. Friend registers → `parrainages.filleul_id` set, `reward_status = 'pending'`
3. When reward condition met (e.g. email verified or first booking): credit both; update `reward_status` to `both_credited`

**Storage:** `referrer_id` = parrain_id, `referred_user_id` = filleul_id, `reward_status` in `parrainages`.

---

## 6. Shareable Achievement Cards

After a **completed session** (reservation statut = terminee):

- Generate a **shareable card** text + image (or OG page).
- **Example text:** "Amadou just completed a Flutter support session on GLOBALO ⭐⭐⭐⭐⭐"
- **URL:** `/share/session/{reservation_id}` or `/share/achievement/{id}` — page that shows the card with OG tags so that when shared on LinkedIn/Facebook it shows the card.
- **Image:** Optional: dynamic image (e.g. PHP GD or a small service) with expert name, star rating, "Session completed on GLOBALO".

**Table:** `session_achievements` (id, reservation_id, expert_id, client_id, titre_session, note, created_at) — optional, or derive from reservations + avis.

---

## 7. SEO Blog System

**Tables:**

- `blog_categories` (id, name, slug, description)
- `blog_tags` (id, name, slug)
- `blog_posts` (id, category_id, author_id, title, slug, meta_description, body, published_at, created_at, updated_at)
- `blog_post_tags` (post_id, tag_id)

**URLs:** `/blog`, `/blog/{category_slug}`, `/blog/post/{post_slug}`

**Features:**

- SEO title, meta description, canonical
- Schema.org `Article`
- Internal linking: "Related posts", "Experts in this topic" (link to expert listing by competence)
- Categories and tags for navigation and SEO

**Example articles:**

- How to fix Flutter bugs quickly
- Where to find developers online
- Best way to hire experts instantly

---

## 8. Growth Analytics Dashboard (Admin)

**Metrics to show:**

| Metric | Source |
|--------|--------|
| Traffic sources | GA4 (or stored in `growth_events` if server-side) |
| Referral statistics | `parrainages` (count by referrer, total referred, rewards) |
| SEO performance | Google Search Console API or manual; top queries, impressions |
| Expert profile visits | `growth_page_views` (page_type=expert, entity_id) |
| Job page visits | `growth_page_views` (page_type=job, entity_id) |
| Conversion rates | Inscriptions, reservations, paiements (from existing DB) |

**Table `growth_page_views`:** id, page_type (expert|job|blog), entity_id, viewed_at, ip_hash or session_id (optional), referer (optional). Used for "most viewed experts/jobs".

---

## 9. Notification Growth System

**Triggers:**

- **Experts in category available:** When an expert (in a competence the user cares about) becomes available → notify clients who viewed that category or have open demandes.
- **New job posted:** When a new demande (job) is created → notify experts matching the competence.
- **Expert replied quickly:** When an expert accepts a reservation or replies to a message quickly → notify client.

**Implementation:** Use existing `notifications` table; add notification types e.g. `expert_available`, `new_job_posted`, `expert_replied`. Cron or event-driven (e.g. after creating demande, after expert sets disponible).

---

## 10. Scalable Architecture

- **Thousands of expert/job pages:** Slugs indexed in DB; sitemap generated in chunks; static or cached meta for list pages.
- **Heavy SEO traffic:** 
  - Canonical URLs to avoid duplicate content.
  - Lightweight public pages (minimal auth checks on expert/job public view).
  - Optional: cache full HTML for expert/job by slug (e.g. 1h TTL).
- **Social sharing:** OG tags on every public page; no extra API for "sharing" except tracking share button clicks if desired (optional event in GA).

**Backend services:**

- **SeoService** — meta, schema, canonical (existing).
- **SlugService** or inline — generate unique slug from title/name.
- **GrowthTrackingService** — record page view (expert/job), optional event to GA.
- **ReferralService** — apply referral on signup, grant rewards when conditions met.
- **AchievementCardService** — generate share URL and OG data for session card.
- **BlogService** — CRUD posts, categories, tags; internal linking logic.
- **NotificationGrowthService** — trigger notifications (expert available, new job, quick reply).

---

## 11. API Endpoints (for sharing & referrals)

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/referral/validate` | GET | Validate referral code (e.g. ?code=GLOBALO-12345), return referrer name or error |
| `/api/share/track` | POST | Optional: track share button click (network, entity_type, entity_id) for analytics |
| `/api/growth/achievement/{id}` | GET | Return OG meta for achievement card (for crawlers) |

Referral apply is server-side on inscription (existing flow with `ref` param).

---

## 12. Database Schema Summary

- **profils_experts:** add `slug` VARCHAR(120) UNIQUE
- **demandes_assistance:** add `slug` VARCHAR(120) UNIQUE NULL
- **parrainages:** add `referral_code` VARCHAR(20) UNIQUE (GLOBALO-XXXXX), `reward_status` ENUM
- **growth_page_views:** id, page_type, entity_id, viewed_at, session_id (optional)
- **session_achievements:** id, reservation_id, expert_id, client_id, titre, note, created_at (optional)
- **blog_categories,** **blog_tags,** **blog_posts,** **blog_post_tags**

---

## 13. File Map (Implementation)

| Component | Files |
|-----------|--------|
| Expert public page by slug | Router: /expert/{slug} → ExpertsController::profileBySlug, ProfilExpertModel::getBySlug |
| Job public page by slug | Router: /jobs/{slug} → JobsController::show, DemandeModel::getBySlug |
| Referral GLOBALO-XXXXX | ParrainageModel::generateReferralCode, getByReferralCode; migration referral_code |
| Achievement cards | SessionAchievementModel, /share/achievement/{id}, SeoService for OG |
| Blog | BlogController, BlogPostModel, BlogCategoryModel, BlogTagModel, views blog/* |
| Growth analytics | GrowthPageViewModel, AdminController::growth (extend), growth_page_views |
| Notifications growth | NotificationModel + triggers in DemandeModel create, ProfilExpert update |
| Sitemap | SeoController::sitemap — add URLs for /expert/*, /jobs/*, /blog/* |
