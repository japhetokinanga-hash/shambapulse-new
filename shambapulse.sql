-- ============================================================
-- ShambaPulse - database for the 6 pages
-- Nyando Sub-County, Kisumu County only.
-- Home, About, Alerts, Prices, USSD, Contact
-- Import in phpMyAdmin (Import tab), or:
--   mysql -u root < shambapulse.sql
-- XAMPP default: root, no password. Safe to re-import:
-- it drops the old database first.
-- ============================================================
DROP DATABASE IF EXISTS shambapulse;
CREATE DATABASE shambapulse;
USE shambapulse;

-- ------------------------------------------------------------
-- Prices page + Home page snapshot
-- One price = one crop, in one unit, in one market, on one date.
-- The UNIQUE KEY stops the same price being saved twice.
-- ------------------------------------------------------------
CREATE TABLE price (
  price_id INT AUTO_INCREMENT PRIMARY KEY,
  commodity VARCHAR(50) NOT NULL,
  unit VARCHAR(30) NOT NULL,
  market VARCHAR(50) NOT NULL,
  county VARCHAR(50) NOT NULL,
  price_date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  source VARCHAR(80) NOT NULL DEFAULT 'ShambaPulse market survey',
  recorded_by INT NULL,
  UNIQUE KEY uk_price (commodity, unit, market, price_date)
);

INSERT INTO price (commodity, unit, market, county, price_date, price, source) VALUES
-- Ahero market (Ahero ward)
('Maize', '90 kg bag', 'Ahero', 'Kisumu', '2026-11-18', 5000.00, 'ShambaPulse market survey'),
('Beans', '90 kg bag', 'Ahero', 'Kisumu', '2026-11-18', 3500.00, 'ShambaPulse market survey'),
('Milk', '1 litre', 'Ahero', 'Kisumu', '2026-11-18', 70.00, 'ShambaPulse market survey'),
('Tomatoes', 'crate', 'Ahero', 'Kisumu', '2026-11-18', 4000.00, 'ShambaPulse market survey'),
('Potatoes', '90 kg bag', 'Ahero', 'Kisumu', '2026-11-18', 4200.00, 'ShambaPulse market survey'),
-- Awasi market (Awasi/Onjiko ward)
('Maize', '90 kg bag', 'Awasi', 'Kisumu', '2026-11-18', 4800.00, 'ShambaPulse market survey'),
('Beans', '90 kg bag', 'Awasi', 'Kisumu', '2026-11-18', 3600.00, 'ShambaPulse market survey'),
('Milk', '1 litre', 'Awasi', 'Kisumu', '2026-11-18', 72.00, 'ShambaPulse market survey'),
('Tomatoes', 'crate', 'Awasi', 'Kisumu', '2026-11-18', 3900.00, 'ShambaPulse market survey'),
('Potatoes', '90 kg bag', 'Awasi', 'Kisumu', '2026-11-18', 4300.00, 'ShambaPulse market survey'),
-- Nyando market (Kabonyo/Kanyagwal ward)
('Maize', '90 kg bag', 'Nyando', 'Kisumu', '2026-11-18', 5100.00, 'ShambaPulse market survey'),
('Beans', '90 kg bag', 'Nyando', 'Kisumu', '2026-11-18', 3800.00, 'ShambaPulse market survey'),
('Milk', '1 litre', 'Nyando', 'Kisumu', '2026-11-18', 75.00, 'ShambaPulse market survey'),
('Tomatoes', 'crate', 'Nyando', 'Kisumu', '2026-11-18', 4000.00, 'ShambaPulse market survey'),
('Cow Milk(At collection point)', '1 litre', 'Katito', 'Kisumu', '2026-03-26', 60.00, 'KAMIS');

-- ------------------------------------------------------------
-- USSD page - farmers who signed up on a feature phone
-- consent = 1 means they said yes to the Data Protection Act
-- 2019 prompt. If consent = 0, nothing is stored for them.
-- subscribed = 1 means they still want alerts.
-- Sign up is on the phone (*384#, option 4), not on the website.
-- ------------------------------------------------------------
CREATE TABLE farmer (
  farmer_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(80) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  county VARCHAR(50) NOT NULL,
  ward VARCHAR(50) NOT NULL,
  language VARCHAR(10) NOT NULL DEFAULT 'English',
  consent TINYINT(1) NOT NULL DEFAULT 0,
  consent_at TIMESTAMP NULL,
  subscribed TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO farmer (full_name, phone, county, ward, language, consent, subscribed) VALUES
('Mary Nyambura', '+254712345678', 'Kisumu', 'Ahero', 'English', 1, 1),
('John Mwangi', '+254723456789', 'Kisumu', 'Awasi/Onjiko', 'Kiswahili', 1, 1),
('Grace Achieng', '+254734567890', 'Kisumu', 'East Kano/Wawidhi', 'English', 1, 1),
('Peter Otieno', '+254745678901', 'Kisumu', 'Kobura', 'Kiswahili', 1, 0);

UPDATE farmer SET consent_at = created_at WHERE consent = 1;

-- ------------------------------------------------------------
-- Alerts page - alerts for a county, in English and Kiswahili
-- type: Weather, Advisory or Market.
-- status: draft (no farmer was eligible) or sent.
-- Each message is kept short so it fits one SMS.
-- ------------------------------------------------------------
CREATE TABLE alert (
  alert_id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(20) NOT NULL,
  title VARCHAR(120) NOT NULL DEFAULT '',
  severity VARCHAR(10) NOT NULL DEFAULT 'info',
  county VARCHAR(50) NOT NULL,
  ward VARCHAR(50) NOT NULL DEFAULT '',
  message_en VARCHAR(160) NOT NULL,
  message_sw VARCHAR(160) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'draft',
  created_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO alert (type, title, severity, county, ward, message_en, message_sw, status) VALUES
('heavy_rain', 'Heavy rain expected in Ahero', 'high', 'Kisumu', 'Ahero', 'ShambaPulse: Heavy rain expected in Ahero tomorrow. Avoid crossing rivers and stay indoors during lightning.', 'ShambaPulse: Mvua kubwa inatarajiwa Ahero kesho. Epuka kuvuka mito na ukae ndani wakati wa radi.', 'sent'),
('dry_spell', 'Short rains starting', 'info', 'Kisumu', 'Awasi/Onjiko', 'ShambaPulse: Short rains are starting. Plant certified maize seed now and prepare drainage.', 'ShambaPulse: Mvua za vuli zinaanza. Panda mbegu bora za mahindi sasa na andaa mifereji.', 'sent'),
('price_rise', 'Maize price up', 'info', 'Kisumu', 'Kabonyo/Kanyagwal', 'ShambaPulse: Maize price rose to KSh 5,000 per 90 kg bag in Kisumu. Check two markets before you sell.', 'ShambaPulse: Bei ya mahindi imepanda hadi KSh 5,000 kwa gunia la kilo 90 Kisumu. Angalia masoko mawili kabla ya kuuza.', 'draft');

-- ------------------------------------------------------------
-- Alerts page - who got each alert, and what happened
-- status: sent, delivered (confirmed), failed.
-- "Sent" is not the same as "delivered" - they are recorded
-- separately, so an officer can see the totals for an alert.
-- ------------------------------------------------------------
CREATE TABLE alert_delivery (
  delivery_id INT AUTO_INCREMENT PRIMARY KEY,
  alert_id INT NOT NULL,
  farmer_id INT NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'sent',
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (alert_id) REFERENCES alert (alert_id),
  FOREIGN KEY (farmer_id) REFERENCES farmer (farmer_id)
);

INSERT INTO alert_delivery (alert_id, farmer_id, status) VALUES
(1, 1, 'delivered'),
(1, 4, 'failed'),
(2, 2, 'delivered');

-- ------------------------------------------------------------
-- Contact page - messages from the Contact Us form
-- type: Farmer, Buyer, Partner or organisation, Other.
-- ------------------------------------------------------------
CREATE TABLE message (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(80) NOT NULL,
  email VARCHAR(100) NOT NULL,
  type VARCHAR(30) NOT NULL DEFAULT 'Farmer',
  message TEXT NOT NULL,
  reply TEXT,
  replied_at TIMESTAMP NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO message (full_name, email, type, message) VALUES
('Mary Nyambura', 'mary@example.com', 'Farmer', 'Hello, how do I subscribe to alerts for Ahero ward?'),
('David Kamau', 'david@example.com', 'Buyer', 'I want to buy 20 bags of maize. Who can I talk to?');

-- ------------------------------------------------------------
-- USSD page - a record of one USSD visit
-- menu_choice: 1 Weather, 2 Market prices, 3 Advisories,
-- 4 Subscribe to alerts, 5 Opt out of alerts.
-- ------------------------------------------------------------
CREATE TABLE ussd_session (
  session_id INT AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(20) NOT NULL,
  language VARCHAR(10) NOT NULL DEFAULT 'English',
  menu_choice TINYINT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO ussd_session (phone, language, menu_choice) VALUES
('+254712345678', 'English', 2),
('+254723456789', 'Kiswahili', 4),
('+254734567890', 'English', 1);

-- ------------------------------------------------------------
-- Activity log - anything done on the public website is
-- recorded here, in the database (not shown on the public site).
-- page: index, about, alerts, prices, ussd, contact
-- action: view, contact_message, price_search, ussd_choice, etc.
-- details: what was done, e.g. the search word, or the USSD choice.
-- Only staff can see this, on the admin side.
-- ------------------------------------------------------------
CREATE TABLE activity_log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  page VARCHAR(20) NOT NULL,
  action VARCHAR(30) NOT NULL,
  details VARCHAR(200) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Project document (Ch. 3-4): the 11-table design.
-- Ward, Market, Commodity, Staff (User roles), Forecast,
-- Advisory, Subscription, MessageLog. Nyando Sub-County only.
-- ============================================================

-- Wards (doc Table 4.3) — 5 wards, with forecast coordinates.
CREATE TABLE ward (
  ward_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  sub_county VARCHAR(50) NOT NULL DEFAULT 'Nyando',
  county VARCHAR(50) NOT NULL DEFAULT 'Kisumu',
  lat DECIMAL(9,6) NOT NULL,
  lon DECIMAL(9,6) NOT NULL
);

INSERT INTO ward (name, lat, lon) VALUES
('Ahero', -0.172800, 34.921400),
('Awasi/Onjiko', -0.183300, 34.983300),
('East Kano/Wawidhi', -0.216700, 34.966700),
('Kabonyo/Kanyagwal', -0.283300, 34.916700),
('Kobura', -0.233300, 34.833300);

-- Markets (doc Table 4.3) — 6 markets, each in one ward.
CREATE TABLE market (
  market_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  ward VARCHAR(50) NOT NULL
);

INSERT INTO market (name, ward) VALUES
('Ahero', 'Ahero'),
('Awasi', 'Awasi/Onjiko'),
('Rabuor', 'Kobura'),
('Pap Onditi', 'East Kano/Wawidhi'),
('Nyando', 'Kabonyo/Kanyagwal'),
('Katito', 'East Kano/Wawidhi');

-- Commodities (doc Table 4.3) — 14 crops and livestock products.
CREATE TABLE commodity (
  commodity_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  unit VARCHAR(30) NOT NULL
);

INSERT INTO commodity (name, unit) VALUES
('Maize', '90kg bag'), ('Beans', '90kg bag'), ('Rice', '90kg bag'),
('Sorghum', '90kg bag'), ('Finger Millet', '90kg bag'),
('Groundnuts', 'kg'), ('Tomatoes', 'kg'), ('Onions', 'kg'),
('Sukuma Wiki', 'bunch'), ('Milk', 'litre'), ('Eggs', 'tray'),
('Live Chicken', 'bird'), ('Goat', 'animal'), ('Cattle', 'animal');

-- Staff users (doc Table 3.8): admin = full access, officer = one ward.
-- Staff sign in with email and password (doc 3.4.5). Passwords are
-- hashes only. Admin keeps the password admin123; the Ahero officer
-- is Officer123! — change both before real use (see README).
CREATE TABLE staff (
  staff_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL DEFAULT 'officer',
  ward VARCHAR(50) NOT NULL DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO staff (name, username, email, password_hash, role, ward) VALUES
('ShambaPulse Admin', 'admin', 'admin@shambapulse.ke', '$2y$10$qAL.eqsmd1MC/mW9tvB47uMdBKHR7cX./wjjT0jjVtm1cKTR0EtVG', 'admin', ''),
('Ahero Extension Officer', 'officer', 'officer@shambapulse.ke', '$2y$10$xvFXjxQSHMn3E.lbBTdZCumXQ7GkxXHFKJEs8KtRqrtKIDhhW10h.', 'officer', 'Ahero');

-- Forecast (doc Table 4.3) — 7-day ward forecasts from Open-Meteo,
-- fetched at 06:00 EAT. Seed rows are SIMULATED and are replaced
-- by the live fetch (fetch_forecast.php) when the site is online.
CREATE TABLE forecast (
  forecast_id INT AUTO_INCREMENT PRIMARY KEY,
  ward VARCHAR(50) NOT NULL,
  forecast_date DATE NOT NULL,
  temp_max DECIMAL(4,1) NULL,
  temp_min DECIMAL(4,1) NULL,
  rainfall_mm DECIMAL(5,1) NOT NULL DEFAULT 0,
  summary VARCHAR(60) NOT NULL DEFAULT '',
  source VARCHAR(30) NOT NULL DEFAULT 'SIMULATED',
  fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_forecast (ward, forecast_date, source)
);

INSERT INTO forecast (ward, forecast_date, temp_max, temp_min, rainfall_mm, summary, source) VALUES
('Ahero', DATE_ADD(CURDATE(), INTERVAL 0 DAY), 27, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Ahero', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 28, 18, 4, 'Light rain', 'SIMULATED'),
('Ahero', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 29, 17, 25, 'Heavy rain', 'SIMULATED'),
('Ahero', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 27, 18, 2, 'Cloudy', 'SIMULATED'),
('Ahero', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 28, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Ahero', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 29, 18, 6, 'Light rain', 'SIMULATED'),
('Ahero', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 27, 17, 1, 'Cloudy', 'SIMULATED'),
('Awasi/Onjiko', DATE_ADD(CURDATE(), INTERVAL 0 DAY), 27, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Awasi/Onjiko', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 28, 18, 4, 'Light rain', 'SIMULATED'),
('Awasi/Onjiko', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 29, 17, 25, 'Heavy rain', 'SIMULATED'),
('Awasi/Onjiko', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 27, 18, 2, 'Cloudy', 'SIMULATED'),
('Awasi/Onjiko', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 28, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Awasi/Onjiko', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 29, 18, 6, 'Light rain', 'SIMULATED'),
('Awasi/Onjiko', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 27, 17, 1, 'Cloudy', 'SIMULATED'),
('East Kano/Wawidhi', DATE_ADD(CURDATE(), INTERVAL 0 DAY), 27, 17, 0, 'Sunny intervals', 'SIMULATED'),
('East Kano/Wawidhi', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 28, 18, 4, 'Light rain', 'SIMULATED'),
('East Kano/Wawidhi', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 29, 17, 25, 'Heavy rain', 'SIMULATED'),
('East Kano/Wawidhi', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 27, 18, 2, 'Cloudy', 'SIMULATED'),
('East Kano/Wawidhi', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 28, 17, 0, 'Sunny intervals', 'SIMULATED'),
('East Kano/Wawidhi', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 29, 18, 6, 'Light rain', 'SIMULATED'),
('East Kano/Wawidhi', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 27, 17, 1, 'Cloudy', 'SIMULATED'),
('Kabonyo/Kanyagwal', DATE_ADD(CURDATE(), INTERVAL 0 DAY), 27, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Kabonyo/Kanyagwal', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 28, 18, 4, 'Light rain', 'SIMULATED'),
('Kabonyo/Kanyagwal', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 29, 17, 25, 'Heavy rain', 'SIMULATED'),
('Kabonyo/Kanyagwal', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 27, 18, 2, 'Cloudy', 'SIMULATED'),
('Kabonyo/Kanyagwal', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 28, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Kabonyo/Kanyagwal', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 29, 18, 6, 'Light rain', 'SIMULATED'),
('Kabonyo/Kanyagwal', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 27, 17, 1, 'Cloudy', 'SIMULATED'),
('Kobura', DATE_ADD(CURDATE(), INTERVAL 0 DAY), 27, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Kobura', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 28, 18, 4, 'Light rain', 'SIMULATED'),
('Kobura', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 29, 17, 25, 'Heavy rain', 'SIMULATED'),
('Kobura', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 27, 18, 2, 'Cloudy', 'SIMULATED'),
('Kobura', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 28, 17, 0, 'Sunny intervals', 'SIMULATED'),
('Kobura', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 29, 18, 6, 'Light rain', 'SIMULATED'),
('Kobura', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 27, 17, 1, 'Cloudy', 'SIMULATED');


-- Advisory (doc Table 4.3) — farming advisories, English + Kiswahili.
CREATE TABLE advisory (
  advisory_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  body_en TEXT NOT NULL,
  body_sw TEXT NOT NULL,
  ward VARCHAR(50) NOT NULL,
  published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  published_by INT NULL
);

INSERT INTO advisory (title, body_en, body_sw, ward, published_by) VALUES
('Planting after the rains', 'Plant maize within 3 days of the first heavy rain. Use certified seed and apply fertilizer after germination, not before the rain.', 'Panda mahindi ndani ya siku 3 baada ya mvua ya kwanza. Tumia mbegu bora na weka mbolea baada ya kumea, si kabla ya mvua.', 'Ahero', 2),
('Mulching during dry spells', 'Cover your crops with dry grass or leaves to keep soil moisture. Water early in the morning or late in the evening.', 'Funika mazao yako kwa majani makavu ili kuhifadhi unyevu. Mwagilia maji asubuhi na mapema au jioni.', 'Awasi/Onjiko', 2),
('Vaccinate livestock before the rains', 'Take goats and cattle for vaccination at the nearest veterinary point before the heavy rains start.', 'Peleka mbuzi na ngombe kwa chanjo kwenye kituo cha mifugo kabla ya mvua kubwa kuanza.', 'Kobura', 2);

-- Subscription (doc Table 4.3, FR-F-04) — one per farmer, ward and
-- channel. A farmer's home ward is the default; further wards may
-- be added. Reactivating an opted-out subscription is done by the
-- farmer (USSD option 4 or My account), never by staff alone.
CREATE TABLE subscription (
  subscription_id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  ward VARCHAR(50) NOT NULL,
  channel VARCHAR(10) NOT NULL DEFAULT 'sms',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_subscription (farmer_id, ward, channel),
  FOREIGN KEY (farmer_id) REFERENCES farmer (farmer_id)
);

INSERT INTO subscription (farmer_id, ward, channel, active) VALUES
(1, 'Ahero', 'sms', 1),
(2, 'Awasi/Onjiko', 'sms', 1),
(3, 'East Kano/Wawidhi', 'sms', 1),
(4, 'Kobura', 'sms', 0);

-- MessageLog (doc Table 4.3, FR-F-10/FR-O-04) — audit record of
-- every SMS and USSD message. status: submitted, delivered,
-- failed or simulated. A simulated message delivers nothing.
-- Snapshots (phone, ward, alert title) keep the audit readable
-- after accounts change.
CREATE TABLE message_log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  channel VARCHAR(10) NOT NULL DEFAULT 'sms',
  phone VARCHAR(20) NOT NULL DEFAULT '',
  ward_name VARCHAR(50) NOT NULL DEFAULT '',
  alert_title VARCHAR(120) NOT NULL DEFAULT '',
  message VARCHAR(300) NOT NULL DEFAULT '',
  direction VARCHAR(10) NOT NULL DEFAULT 'out',
  status VARCHAR(10) NOT NULL DEFAULT 'simulated',
  gateway_ref VARCHAR(200) NOT NULL DEFAULT '',
  farmer_id INT NULL,
  alert_id INT NULL,
  ward VARCHAR(50) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES farmer (farmer_id) ON DELETE SET NULL,
  FOREIGN KEY (alert_id) REFERENCES alert (alert_id) ON DELETE SET NULL
);

INSERT INTO message_log (channel, phone, ward_name, alert_title, message, direction, status, farmer_id, alert_id, ward) VALUES
('sms', '+254712345678', 'Ahero', 'Heavy rain expected in Ahero', 'ShambaPulse: Heavy rain expected in Ahero tomorrow. Avoid crossing rivers and stay indoors during lightning.', 'out', 'simulated', 1, 1, 'Ahero');
