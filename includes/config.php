<?php
// Database (XAMPP defaults). Override with environment variables if needed.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'shambapulse');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
// Staff login for /admin. Default password: admin123 -- change it:
//   php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"
define('ADMIN_USER', 'admin');
define('ADMIN_HASH', '$2y$10$TM1uoRyGhgIVSsAOQSyQIuq/TFH81wh/UQZuHsdEVHfFRn8Mqi6tC');
