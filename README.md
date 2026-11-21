# ShambaPulse (PHP + MySQL) — Nyando only, full project document implemented 21 Nov 2026

Everything in the project document (Mutevu_227167_227610) is now in
this website: the 6 modules, the 11 tables, the 3 roles, the *384#
menu, the 6 SMS templates, the 7-day Open-Meteo forecast, the
prototype price feed, and reports with CSV export. See "4. Project
document" below. The public pages keep your own CSS and your own
words — only the two new pages (Forecast, Advisories) were added to
the menu, and My account gained the forecast and the ward
subscriptions the document requires.

Nyando Sub-County, Kisumu County only. 5 wards: Ahero, Awasi/Onjiko,
East Kano/Wawidhi, Kabonyo/Kanyagwal, Kobura. 6 markets: Ahero, Awasi,
Rabuor, Pap Onditi, Nyando, Katito. Kisii and Nyamira were removed.

Nothing on the public pages was changed — no words added, no wording
changed, and `css/style.css` is your original file, unchanged.
Every page uses the same header, menu and footer (from
`includes/bootstrap.php`), so all pages are aligned the same: the
header line "Where farmers meet quality services.", the Google
fonts, and the footer "ShambaPulse, Nairobi / Site built by
Kinanga Japhet and Gideon Francis, DBIT Year 1, 2026." are your
own words from your original pages, restored on every page.
Only these three things were added:

## 1. Web contact messages seen by admin
The Contact form saved the message, but nothing told staff it had
arrived: no unread count, and the activity log only recorded the
type ("Farmer").
- Every new message is saved unread (`is_read = 0`).
- The staff dashboard lists unread messages first, and the Messages
  link shows the count in brackets, e.g. `Messages (2)`.
- Messages page: newest first, unread ones marked NEW, with
  "Mark read" / "Mark all as read". Opening a message to reply
  marks it read.
- The activity log now records the sender's name, email and the
  start of the message.
- Nothing is sent by email on localhost: messages live in the
  database, in `admin/messages.php`.

## 2. Any sign-in seen by admin
- New page `admin/signins.php`, and a Recent sign-ins box on the
  dashboard: every web farmer sign-in, web farmer sign-up, staff
  sign-in, USSD sign-up (option 4) and USSD opt-out (option 5),
  newest first, with name, phone and ward.
- Farmers page shows each farmer's last sign-in time.

## 3. Nyando only
`includes/bootstrap.php` now has one county (Kisumu) and the 5
Nyando wards. Registration, prices and alerts only use those.
In `shambapulse.sql` the same farmers, phones and prices are kept —
only the county, ward and market were changed to Nyando ones.

## 4. Project document — what was implemented (21 Nov 2026)
Modules (doc Table 3.7): 1 User and access management, 2 Climate
alerts and dispatch, 3 Market prices and feed, 4 Advisory
publishing, 5 USSD and SMS channel, 6 Reports and administration.
Tables (doc Table 4.3): ward, market, commodity, staff, forecast,
advisory, subscription, message_log — added to the existing price,
farmer, alert, message, ussd_session and activity_log tables.
- Roles (doc Table 3.8): Administrator (everything), Extension
  officer (own ward only — prices, farmers, alerts, advisories,
  reports), Farmer (own dashboard and subscriptions on the web;
  forecast, prices, subscription through USSD/SMS). Staff sign in
  with email + password. Demo logins: admin —
  admin@shambapulse.ke / admin123; officer (Ahero) —
  officer@shambapulse.ke / Officer123!. Change both before real use.
  More officers are created in admin > Staff and roles.
- Forecast (FR-F-06/07): Forecast page shows the 7-day forecast
  for a ward, labelled with its source; My account shows the
  farmer's ward forecast; USSD option 1 gives today's forecast.
  Live data comes from Open-Meteo (`fetch_forecast.php`, run daily
  at 06:00 EAT — in XAMPP use Windows Task Scheduler with
  `php C:\xampp\htdocs\shambapulse\fetch_forecast.php`; the
  Forecast page also tries to refresh when the stored data is
  older than a day). Until a live fetch succeeds, the stored
  forecast is shown and badged SIMULATED.
- Alerts (FR-F-10, FR-O-03/04): compose in English and Kiswahili
  from the 6 SMS templates (admin > SMS templates, with segment
  counts); dispatch goes only to subscribed farmers of that ward
  with recorded consent, each in their own language. Delivery
  (admin > Delivery) shows submitted / delivered / failed /
  simulated counts per alert and the full message log.
- SMS (doc 3.4.5, Appendix C.2): simulated by default — a
  simulated message is logged and delivers nothing. To send real
  SMS, fill in AT_USERNAME and AT_API_KEY in
  includes/bootstrap.php; a failed send is logged as failed.
- Advisories (FR-O-08, FR-F-11): publish in English and Kiswahili
  (admin > Advisories), shown on the Advisories page and on USSD
  option 3, in the farmer's language. Publishing can also send
  the advisory SMS to the ward's subscribers.
- Subscriptions (FR-F-04/05, FR-A-07): one per farmer, ward and
  channel. Farmers subscribe to further wards on My account or
  USSD option 4; a repeat subscribe reactivates instead of
  duplicating. Staff can deactivate any subscription (admin >
  Subscriptions); only the farmer can subscribe again.
- Prices (FR-O-05/06, FR-A-08): officer entries are stamped
  source "officer"; admin > Prices has Delete and "Refresh price
  feed" (20 prototype prices, source "feed", badged SIMULATED —
  a second refresh adds nothing). Prices page has a history and
  trend view for one commodity in one market.
- Reports (FR-O-10, FR-A-06): admin > Reports — farmers by ward
  and language, alerts sent and delivered, prices by commodity
  and market — each downloadable as CSV.
- Not tested live here: the Open-Meteo fetch (this machine got a
  429 rate-limit answer from Open-Meteo, so only the SIMULATED
  fallback was verified) and real Africa's Talking SMS/USSD
  (no keys). Everything else was tested locally, 37 checks — see
  TESTS.md.

## Files added (your upload had no includes/ or admin/ folder)
- `includes/bootstrap.php` — database, COUNTIES, layout, login helpers.
- `includes/ussd.php` — the *384# menu, Nyando wards only.
- `admin/` — dashboard, messages, reply-message, sign-ins, farmers,
  edit-farmer, prices, add-price, edit-price, alerts, ussd, activity.

## Run on XAMPP
1. Copy this folder to `C:\xampp\htdocs\shambapulse` and start
   **Apache** and **MySQL**. Replace the whole old folder — do not
   mix old and new files, or the old Kisii/Nyamira code stays.
2. phpMyAdmin > Import > `shambapulse.sql` (this drops and recreates
   the `shambapulse` database). **Only do this on a fresh install:**
   re-importing deletes messages, farmers and prices already saved.
   On an existing install, just copy the new files over — the site
   adds the new tables and columns itself the first time a page
   loads (wards, markets, commodities, staff, forecast, advisory,
   subscription, message_log, plus `message.is_read`,
   `farmer.last_login_at`, `farmer.consent_at`, `alert.title`,
   `alert.severity`, `alert.ward`, `price.recorded_by`).
3. Open http://localhost/shambapulse/

Database settings are in `includes/bootstrap.php` (XAMPP default:
root, no password).

## Signing in (farmers and staff)
One shared page: http://localhost/shambapulse/login.php
- **Farmers:** sign in with phone number + password, or create an
  account on the same page.
- **Farmers who joined by USSD** have no web password yet: in the
  staff area, Farmers tab, click **Edit / set password** and set one.
- **Staff:** username `admin`, password `admin123`; they land in the
  staff area at /admin/.

**Change the staff password** before real use: run
`php -r "echo password_hash('new-password', PASSWORD_DEFAULT);"`
and paste the hash into `ADMIN_HASH` in `includes/bootstrap.php`
(in place of the `password_hash('admin123', ...)` call).

## USSD
- `ussd.php` has a working simulator (Nyando wards only).
- For Africa's Talking, set the USSD callback URL to
  `https://<your-host>/ussd_callback.php` (needs public HTTPS, for
  example via ngrok).

## If an edit does not show up — check.php
Open http://localhost/shambapulse/check.php. It shows the folder this
copy of the site is running from, the database it uses, how many
prices / messages / farmers it sees, the newest message and price,
and a write test. If those numbers do not match what the admin
area shows, you are opening two different copies of the site —
for example the old folder and the new folder — and the fix is to
keep only ONE copy in `C:\xampp\htdocs`: delete or rename the
other shambapulse folder, then open the site again from the one
folder that is left. Edits only show where they were made.

Two things that used to look like "not reflecting":
- Editing a price to a market and date that already has that
  commodity used to give a blank error page and save nothing.
  It now shows "That price is already recorded for that market
  and date." — change the date to a new one instead.
- The public Prices page shows the newest price for each
  commodity, unit and market. A price added with an old date
  will not show over a newer one for the same market.

## Notes
- SMS is not sent to a real gateway: dispatching an alert records
  each farmer as `sent`.
- Tested locally 21 Nov 2026: see TESTS.md.
#   s h a m b a p u l s e - n e w  
 