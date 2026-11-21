# Tests — 21 Nov 2026 (local, PHP 8.3 + MariaDB 10.11)

Run with the site on `php -S 127.0.0.1:8080` after importing
`shambapulse.sql`. All 22 checks passed on a fresh import.
Redo 21 Nov 2026: CSS restored to the original, public pages
restored to the uploaded originals (no words added or changed
there). The 22 checks were re-run after the redo.

## Main flow (22 checks)
- Home loads (200); Prices page has no Kisii/Nyamira and shows Ahero.
- Contact form: a message from "Test Farmer" is saved, and the staff
  dashboard shows it as a new message.
- Web farmer sign-up (Test Mwangi, 0712999888, Ahero ward) lands on
  My account; staff sign-in (admin / admin123) lands on the dashboard.
- Dashboard Recent sign-ins shows Test Mwangi; Messages page shows
  the Test Farmer message with a NEW badge.
- Replying to the message saves the reply and marks it read — the
  dashboard unread count drops from 3 to 2 (the 2 seed messages).
- Sign-ins page shows `farmer_register` with name + phone + ward,
  and `staff_login`.
- Farmers page shows Test Mwangi, Ahero, and his last sign-in time.
- USSD simulator: 4 (Subscribe) -> ward 1 (Ahero) -> language 1
  (English) -> consent 1 (Yes) ends "Subscribed to alerts for
  Ahero"; the Sign-ins page then shows `ussd_subscribe` with
  +254700999888, Ahero, Nyando.
- Registering with county Kisii / ward Bobasi is rejected with
  "Please choose your county and ward."

## Smoke test (every page, as staff)
index, about, alerts, prices, ussd, contact, admin dashboard,
messages, sign-ins, farmers, prices, add-price, alerts, ussd,
activity, edit-farmer, edit-price, reply-message — all 200, no
PHP errors. Admin add-price (Sukuma Wiki, Ahero) saves; admin
alert create saves as draft and dispatch sends to 3 farmers.

## Bug found by testing, and fixed
- `admin/alerts.php` used `SELECT a.* ... FROM alert` without the
  `a` alias, so the page gave a 500 error ("Unknown table
  'shambapulse.a'"). Fixed to `FROM alert a`. Re-tested: 200.

## Scope check (database)
After import: 16 prices, all county Kisumu; 4 farmers, all in
Nyando wards; 0 prices, farmers or alerts outside Kisumu.

## Project document implementation — tested 21 Nov 2026
Test file: `shambapulse-nyando-tests/test_doc.py` (37 checks,
all PASS), plus the 22-check suite above (still PASS, no
regressions). Tested with PHP 8.3 + MariaDB 10.11, fresh import.
- Public: Forecast page (7-day, source labelled), Advisories page
  (3 seed advisories, English + Kiswahili), price history and
  trend (Maize/Ahero).
- USSD: option 1 forecast for Ahero; option 2 price with source
  and date; option 3 advisory; option 4 subscribe a new phone,
  re-subscribe same ward ("already subscribed", no duplicate
  account), option 5 opt out.
- Farmer web: register (consent timestamp + home ward
  subscription), My account shows details + consent date +
  7-day forecast + ward subscriptions; subscribe to a further
  ward (Kobura), opt out of one ward.
- Officer: email login (officer@shambapulse.ke / Officer123!),
  Ahero dashboard only; cannot open an Awasi farmer; farmers page
  shows Ahero only; creates an Ahero alert as draft and dispatches
  it to Ahero subscribers; Delivery page shows the counts.
- Admin: creates a Kobura officer; adds a market and a commodity;
  feed refresh adds prices, second refresh adds nothing
  (idempotent); all 4 CSV exports return CSV; templates page
  shows all 6 templates; subscriptions and advisories pages list
  the seeded rows.
- Not tested live here: the Open-Meteo fetch — this machine got a
  429 rate-limit answer from Open-Meteo, so only the SIMULATED
  fallback was verified; and real Africa's Talking SMS (no keys).
