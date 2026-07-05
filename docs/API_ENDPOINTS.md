# Lentera API — Referensi Endpoint

Base: **`/api/v1`** · JSON · Auth **JWT** (`Authorization: Bearer <token>`, kecuali grup Auth publik) · Error konsisten **`{ "message": ..., "errors": {...} }`**.

Dua bidang data: **jurnal E2E** (`*_enc` = ciphertext base64 dari device, server buta) vs **komunitas plaintext-dimoderasi**.

**Kontrak kripto E2E (WAJIB sama di device):** `key = Argon2id(passphrase, kdf_salt)`; tiap record `nonce` acak **96-bit (12 byte)**; algoritma **AES-256-GCM**. Format simpan: `*_nonce` = nonce (base64) terpisah; `*_enc` = **ciphertext + tag GCM 16-byte digabung** (base64). `kdf_salt` (16 byte) dibuat device saat register & dikirim base64; bila tak dikirim server buat sendiri. **Selalu baca `kdf_salt` dari respons auth/`/me`** — server menyimpan & mengembalikannya (bukan rahasia; kunci tak pernah ke server).

## Auth & Akun (§1)
| Method | Path | Auth | Catatan |
|---|---|---|---|
| POST | `/auth/register` | — | email+password (+`kdf_salt`) → JWT |
| POST | `/auth/login` | — | → JWT; admin ber-2FA → `{two_factor_required, pending_token}` |
| POST | `/auth/oauth` | — | `{provider:"google", id_token}` → JWT (server **verifikasi** ID token: aud+issuer) |
| POST | `/auth/otp/request` · `/auth/otp/verify` | — | login HP → JWT |
| POST | `/auth/recovery` · `/auth/recovery/confirm` | — | pemulihan via email |
| POST | `/auth/2fa/verify` | pending JWT | → token (admin: scope `mod`) |
| POST | `/auth/2fa/setup` · `/enable` · `/disable` | JWT (admin) | TOTP |
| POST | `/auth/refresh` | JWT (boleh kedaluwarsa) | token lama→baru (blacklist lama); token APP (mod hanya via 2FA) |
| GET | `/me` | JWT | profil + providers + `email`/`phone` (E.164, null bila kosong) + status sinkron + **`kdf_salt`** (base64) |
| POST | `/auth/logout` | JWT | cabut (blacklist) token |

### Lengkapi profil — tambah/ganti/hapus metode masuk (§1)
| Method | Path | Auth | Catatan |
|---|---|---|---|
| POST | `/profile/email` · `/profile/email/confirm` | JWT | tambah/ganti email (OTP via **email**); confirm `{email, code}` → `{user}` (bentuk `/me`) |
| POST | `/profile/phone` · `/profile/phone/confirm` | JWT | tambah/ganti nomor (OTP via **WhatsApp/GOWA**); confirm `{phone, code}` → `{user}` |
| DELETE | `/profile/identity` | JWT | `{provider:"email"\|"phone"}` — hapus, guard sisakan ≥1 cara masuk |

> Verifikasi kepemilikan wajib; keunikan global (422 bila dipakai akun lain). **`kdf_salt` tak pernah berubah** → cadangan E2E lama tetap terbaca. +nomor mengaktifkan login OTP WA; +email mengaktifkan email recovery. `dev_code` `null` di produksi.

## Vault E2E (§2)
| Method | Path | Catatan |
|---|---|---|
| GET | `/vault/status` | `sync_on, synced, has_backup, version, last_synced_at` |
| PUT | `/vault/backup` | `{ciphertext(base64), version?, checksum?}` — server buta |
| GET | `/vault/restore` | ciphertext terakhir |
| DELETE | `/vault/backup` | hapus cadangan |

## Orang / People (§3) — E2E
`GET /people` · `POST /people` · `PUT /people/{id}` · `DELETE /people/{id}`
Field: `name_enc/name_nonce`, `rel_enc/rel_nonce`, `recall_enc/recall_nonce` (base64) + `avatar_color`. Metadata plaintext: `pos_count, neg_count, last_at, last_type` (dari interactions).

## Momen/Interaksi (§4) + Media (§5) — E2E
| Method | Path | Catatan |
|---|---|---|
| GET | `/interactions?person_id=&type=&cursor=` | cursor pagination |
| POST | `/interactions` | `{type, text_enc, text_nonce, topic?, mood?, person_ids[], media_ids[]}` |
| PUT/DELETE | `/interactions/{id}` | metadata orang dihitung ulang |
| POST | `/media` | `{kind, blob(base64), nonce?, mime?}` → `media_id` |
| GET | `/media/{id}` | blob ciphertext |

## Mood, Statistik & Rekap (§6)
| Method | Path | Catatan |
|---|---|---|
| GET | `/stats/summary?range=week\|month` | `week[], distribution, streak, recap` |
| GET | `/stats/mood?month=YYYY-MM` | kalender mood 1 bulan → `{data:[{date, mood_index}]}` |
| POST | `/mood` | `{mood_index 0-4, date?}` (upsert harian) |
| GET | `/today` | mood + counts + `social_energy{filled,drained}` |
| PUT | `/reflections/{date}` | **E2E** upsert "Tiga baris malam": `{grateful_enc/nonce, drained_enc/nonce, tomorrow_enc/nonce}` (semua opsional) → `{date, ...fields}` |
| GET | `/reflections/{date}` | refleksi 1 hari (field `null` bila kosong) |
| GET | `/reflections?from=&to=` | riwayat kalender → `{data:[...]}` |

## Komunitas — Feed & Post (§7)
| Method | Path | Catatan |
|---|---|---|
| GET | `/community/feed?cursor=&circle_id=` | approved, non-hidden |
| POST | `/community/posts` | `{text, anon?, surface?, circle_id?}` → `pending` |
| GET | `/community/posts/{id}` | detail + status |
| POST/DELETE | `/community/posts/{id}/react` | `{kind: peluk\|kekuatan\|paham}` (+jumlah) |
| POST | `/community/posts/{id}/hide` | sembunyikan dari feed-ku |

## Lingkaran (§8)
`GET /circles` · `GET /circles/{id}` · `GET /circles/{id}/feed` · `POST /circles/{id}/join` · `DELETE /circles/{id}/join`
| Method | Path | Catatan |
|---|---|---|
| POST | `/circles` | Buat lingkaran: `{name, emoji?, description?}` → **201** item circle (flat, sama bentuk item `GET /circles`); pembuat auto-join (`joined:true`, `member_count:1`). Langsung publik; nama/desc disaring kata terlarang (422 bila melanggar); maks **5/user**; nama boleh duplikat (slug unik). |

## Prompt & Kirim kekuatan (§9)
| Method | Path | Catatan |
|---|---|---|
| GET | `/prompts/today` | pertanyaan hari ini + `share_count` (selalu ada — dirotasi harian dari pool, WIB) |
| GET | `/prompts/today/answers?cursor=` | jawaban approved |
| POST | `/prompts/today/answers` | `{text, anon?}` → **dimoderasi sinkron**: `moderation.status` = approved/held/rejected langsung |
| GET | `/strength/queue` | struggle (butuh dukungan) |
| POST | `/strength/{postId}/send` | `{message}` siap-pakai — **instan** |

## Laporan & Moderasi mobile (§10)
| Method | Path | Catatan |
|---|---|---|
| POST | `/reports` | `{post_id, reason, note?}` (reason = label app) |
| GET | `/moderation/banned-terms` | `{banned_terms[], crisis_signals[]}` — identik app |

## Ringkasan AI (gated consent — plaintext, transien)
| Method | Path | Catatan |
|---|---|---|
| POST | `/ai/summarize/person` | `{name, relation?, pos_count?, neg_count?, interactions[]}` → `{summary}` (hangat, 1-2 kalimat; `null` bila kosong/gagal) |
| POST | `/ai/summarize/day` | `{date, mood_index?, moments[]}` → `{summary}` |

> **Sengaja menembus E2E** hanya saat consent ON: app mengirim **plaintext** hasil dekripsi. Server **tak menyimpan/log** konten (transien; hasil di-cache by hash). Hanya panggil bila toggle "Ringkasan AI" aktif.

## Keselamatan (§11) & Pengaturan/Notifikasi (§12)
| Method | Path | Catatan |
|---|---|---|
| GET | `/safety/hotlines?region=` | "Segera hadir" |
| GET/PUT | `/settings` | `sync_on, reminder_on, reminder_at, accent, theme` |
| PUT | `/settings/sync` · `/settings/reminder` | toggle sinkron · jadwal pengingat |
| POST | `/notifications/token` | `{token, platform: fcm\|apns}` |

## Konsol Moderasi (admin — scope `mod`, dipakai web console)
`GET /mod/queue` · `POST /mod/action` · `GET /mod/reports` · `POST /mod/reports/action` · `GET\|POST /mod/terms` · `GET /mod/terms/suggest` · `DELETE /mod/terms/{id}` · `GET /mod/accounts` · `POST /mod/accounts/{id}/action` · `GET /mod/metrics`

---

**Koleksi Postman:** `docs/lentera-api.postman_collection.json` (import ke Postman; set `base_url`, jalankan Register/Login → token otomatis tersimpan).
