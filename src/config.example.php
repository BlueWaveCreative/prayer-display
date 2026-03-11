<?php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'prayer_display');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// PCO OAuth App (registered at api.planningcenteronline.com/oauth/applications)
define('PCO_CLIENT_ID', 'your_pco_client_id');
define('PCO_CLIENT_SECRET', 'your_pco_client_secret');
define('PCO_REDIRECT_URI', 'https://bluewavecreativedesign.com/prayer/oauth/callback');

// App
define('APP_URL', 'https://bluewavecreativedesign.com');
define('BASE_PATH', '/prayer'); // Path prefix (e.g., '/prayer' for domain.com/prayer)
define('SESSION_LIFETIME', 43200); // 12 hours
