# Lentera — Developer Handover

Calm journaling app for tracking gratitude & boundaries with the people around you, plus a moderated community. Three parts:

| Part | Stack | Design file (ground truth) |
|---|---|---|
| **Mobile app** | Flutter · Dart (Android & iOS) | `Lentera - Android.dc.html` |
| **Web console** (moderation) | React · Vite | `Lentera - Konsol Admin.dc.html` |
| **API backend** | PHP · Laravel 11 + PostgreSQL 16 | — (spec in docs below) |
| Moderation AI | Gemini API (pre-publish) | — |

## The design files ARE the spec
Every color, px, radius, shadow, and font in the `.dc.html` files is **real, inspectable CSS** — not a screenshot. To get a value 100% right: open the file in a browser → DevTools → **Computed**. Don't eyeball; read the number.

## Design tokens (use these, don't re-pick values)
- `tokens.json` — all tokens, structured (mobile light/dark + web).
- `theme.dart` — drop into the Flutter app. `LenteraLight` / `LenteraDark` / `LenteraAccent` / `LenteraType` / `LenteraRadius` / `LenteraSpace` / `LenteraTheme`. Needs `google_fonts`.
- `tokens.css` — `:root` custom properties for the React console.

Because both platforms import the same numbers, the build comes out identical.

## Fonts (Google Fonts)
- **Mobile:** Quicksand (display/headings/titles, 700) · Nunito (body, 400–700)
- **Web console:** Newsreader (headings, 400/500) · DM Sans (body, 400–700) · Spline Sans Mono (labels)

## Screen inventory
**Mobile** — Onboarding/Login · Auth (Google/Apple/Email/HP-OTP) + 2FA · Beranda · **Hari Ini** (auto-rekap, tiga baris malam, mood, highlight/lowlight, energi sosial, kalender) · Komunitas (Dinding Syukur, Lingkaran, Prompt bersama, Kirim kekuatan) + Jelajahi lingkaran · Orang + Semua momen · Timeline per orang (kesehatan relasi, pola, "Sebelum bertemu") · Logger (chat-style, kategori, suara, foto, auto-tag, deteksi krisis) · Ruang Tenang (napas, grounding, hotline) · Pengaturan (tema, 2FA, sinkron, cadangan, pemulihan, panic-lock, pengingat lembut).

**Web console** — Login (email + 2FA) · Ringkasan (kesehatan komunitas) · Antrean moderasi · Laporan · Kata terlarang (+ saran AI) · Akun.

## Behaviors to preserve (the soul of the app)
- **Calm > engagement.** No red badges, no follower/like counts, no anxiety metrics.
- Reminders are **opt-in & gentle**: one evening lock-screen notification + a soft sage dot on "Hari Ini" (clears when mood is set). Toggle in Settings.
- **E2E privacy:** journal encrypted on-device (AES-256-GCM), key derived from passphrase (Argon2id), server stores ciphertext only. Community is a separate, moderated plane.
- **Moderation loop:** community posts go *pending → "Lolos tinjauan"*; pre-publish filter = banned-words regex (7 terms, identical in app & console) + Gemini classifier; self-harm signals get gentle handling (hold + Ruang Tenang + route to console "penanganan khusus"), never a cold block.
- **Pagination:** lists use "Muat lebih banyak" (mobile) / load-more (console) — implement as real pagination/infinite-scroll on the API.

## Companion docs (read these)
- **`Lentera - Rencana Produk.dc.html`** — product plan: privacy model (two data planes), DB schema (users, auth_identities, vault_backups, posts, reactions, circles, reports, moderation, banned_terms), API groups (Auth / Sync / Community / Moderation), roadmap (Fase 0–4).
- **`Lentera - Handoff Doc.dc.html`** — technical spec: user flow, wireframes, SQL schema, E2E encryption flow, tech stack, endpoints.

## Driving Claude Code
Point `claude` at this repo with the design files + tokens present, then implement **screen by screen**:
1. Open the relevant `.dc.html` as the visual reference.
2. Import `theme.dart` (Flutter) / `tokens.css` (React) — never hardcode raw hex.
3. Build the widget/component to match the computed CSS; check side-by-side.
4. Wire to the Laravel API per the schema/endpoints in the companion docs.

Tip: ask Claude Code to read the exact element styles from the `.dc.html` rather than from a screenshot — that's how you get pixel parity.
