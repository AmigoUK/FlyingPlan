<?php
/**
 * FlyingPlan Installer
 * Self-contained setup wizard — no CI4 framework needed.
 * Handles: requirements check, DB setup, branding, admin account, config.
 * Deletes itself after successful installation.
 */

// Prevent running if already installed
$writablePath = dirname(__DIR__) . '/writable';
if (file_exists($writablePath . '/.installed')) {
    header('Location: /');
    exit;
}

session_start();
$step = (int) ($_POST['step'] ?? $_GET['step'] ?? 1);
$error = '';
$success = '';

// ─── Handle POST actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 2: Test DB & create tables
    if ($action === 'setup_db') {
        $db = @new mysqli(
            $_POST['db_host'],
            $_POST['db_user'],
            $_POST['db_pass'],
            '',
            (int) ($_POST['db_port'] ?: 3306)
        );
        if ($db->connect_error) {
            $error = 'Connection failed: ' . htmlspecialchars($db->connect_error);
            $step = 2;
        } else {
            $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_name']);
            if (!empty($_POST['create_db'])) {
                $db->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            if (!$db->select_db($dbName)) {
                $error = "Database '$dbName' does not exist. Check the name or enable 'Create database'.";
                $step = 2;
            } else {
                // Create tables
                $tableErrors = createTables($db);
                if ($tableErrors) {
                    $error = 'Table creation errors: ' . implode('; ', $tableErrors);
                    $step = 2;
                } else {
                    // Store DB config in session for later steps
                    $_SESSION['fp_db'] = [
                        'host' => $_POST['db_host'],
                        'port' => (int) ($_POST['db_port'] ?: 3306),
                        'name' => $dbName,
                        'user' => $_POST['db_user'],
                        'pass' => $_POST['db_pass'],
                    ];
                    $step = 3;
                }
            }
            $db->close();
        }
    }

    // Step 3: Save branding & seed lookup data
    if ($action === 'save_branding') {
        $dbc = $_SESSION['fp_db'] ?? null;
        if (!$dbc) { $step = 2; $error = 'DB session lost. Please re-enter.'; }
        else {
            $db = new mysqli($dbc['host'], $dbc['user'], $dbc['pass'], $dbc['name'], $dbc['port']);
            $color = $_POST['primary_color'] ?? '#0d6efd';
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#0d6efd';

            // Seed app_settings
            $stmt = $db->prepare("INSERT INTO app_settings (id, business_name, logo_url, primary_color, contact_email, tagline, show_heard_about, show_customer_type_toggle, show_purpose_fields, show_output_format, guide_mode, dark_mode) VALUES (1, ?, ?, ?, ?, ?, 1, 1, 1, 1, 1, ?) ON DUPLICATE KEY UPDATE business_name=VALUES(business_name), primary_color=VALUES(primary_color), contact_email=VALUES(contact_email), tagline=VALUES(tagline), dark_mode=VALUES(dark_mode), logo_url=VALUES(logo_url)");
            $darkMode = !empty($_POST['dark_mode']) ? 1 : 0;
            $bName = $_POST['business_name'] ?: 'FlyingPlan';
            $email = $_POST['contact_email'] ?: '';
            $tagline = $_POST['tagline'] ?: 'Drone Flight Brief';
            $logo = $_POST['logo_url'] ?: '';
            $stmt->bind_param('sssssi', $bName, $logo, $color, $email, $tagline, $darkMode);
            $stmt->execute();

            // Seed lookup tables
            seedLookupTables($db);
            $db->close();
            $step = 4;
        }
    }

    // Step 4: Create admin user
    if ($action === 'create_admin') {
        $dbc = $_SESSION['fp_db'] ?? null;
        if (!$dbc) { $step = 2; $error = 'DB session lost.'; }
        else {
            $pass = $_POST['admin_pass'] ?? '';
            $pass2 = $_POST['admin_pass2'] ?? '';
            if (strlen($pass) < 8) { $error = 'Password must be at least 8 characters.'; $step = 4; }
            elseif ($pass !== $pass2) { $error = 'Passwords do not match.'; $step = 4; }
            else {
                $db = new mysqli($dbc['host'], $dbc['user'], $dbc['pass'], $dbc['name'], $dbc['port']);
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (username, display_name, password_hash, is_active_user, role, email, created_at) VALUES (?, ?, ?, 1, 'admin', ?, NOW())");
                $uname = $_POST['admin_user'] ?: 'admin';
                $dname = $_POST['admin_display'] ?: 'Administrator';
                $uemail = $_POST['admin_email'] ?: '';
                $stmt->bind_param('ssss', $uname, $dname, $hash, $uemail);
                if (!$stmt->execute()) {
                    $error = 'Could not create user: ' . htmlspecialchars($stmt->error);
                    $step = 4;
                } else {
                    $step = 5;
                }
                $db->close();
            }
        }
    }

    // Step 5: Write config & finalize
    if ($action === 'finalize') {
        $dbc = $_SESSION['fp_db'] ?? null;
        if (!$dbc) { $step = 2; $error = 'DB session lost.'; }
        else {
            $baseURL = $_POST['base_url'] ?? detectBaseURL();
            if (substr($baseURL, -1) !== '/') $baseURL .= '/';
            $envContent = "CI_ENVIRONMENT = production\n\n";
            $envContent .= "app.baseURL = '$baseURL'\n\n";
            $envContent .= "database.default.hostname = {$dbc['host']}\n";
            $envContent .= "database.default.database = {$dbc['name']}\n";
            $envContent .= "database.default.username = {$dbc['user']}\n";
            $envContent .= "database.default.password = {$dbc['pass']}\n";
            $envContent .= "database.default.DBDriver = MySQLi\n";
            $envContent .= "database.default.port = {$dbc['port']}\n";

            $envPath = dirname(__DIR__) . '/.env';
            if (file_put_contents($envPath, $envContent) === false) {
                $error = 'Could not write .env file. Check permissions on: ' . dirname(__DIR__);
                $step = 5;
            } else {
                // Create lock file
                file_put_contents($writablePath . '/.installed', date('Y-m-d H:i:s'));
                $_SESSION['fp_installed'] = true;
                $step = 6; // Done
            }
        }
    }

    // Delete installer
    if ($action === 'delete_installer') {
        @unlink(__FILE__);
        header('Location: /login');
        exit;
    }
}

// ─── Helper: detect base URL ──────────────────────────────────────────
function detectBaseURL(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    if ($path === '/' || $path === '\\') $path = '';
    return $proto . '://' . $host . $path . '/';
}

// ─── Helper: create all tables ────────────────────────────────────────
function createTables(mysqli $db): array {
    $errors = [];
    $statements = getSchemaStatements();
    foreach ($statements as $name => $sql) {
        if (!$db->query($sql)) {
            $errors[] = "$name: " . $db->error;
        }
    }
    return $errors;
}

// ─── Helper: seed lookup tables ───────────────────────────────────────
function seedLookupTables(mysqli $db): void {
    $now = date('Y-m-d H:i:s');

    // Job types
    $jobTypes = [
        ['aerial_photo','Aerial Photography','bi-camera','technical',0],
        ['inspection','Inspection','bi-search','technical',1],
        ['survey','Survey / Mapping','bi-map','technical',2],
        ['event_celebration','Event / Celebration','bi-balloon','creative',3],
        ['real_estate','Real Estate','bi-house','creative',4],
        ['construction','Construction Progress','bi-building','technical',5],
        ['agriculture','Agriculture','bi-tree','technical',6],
        ['emergency_insurance','Emergency / Insurance','bi-shield-exclamation','technical',7],
        ['custom_other','Custom / Other','bi-three-dots','other',8],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO job_types (value,label,icon,category,is_active,sort_order,created_at) VALUES (?,?,?,?,1,?,?)");
    foreach ($jobTypes as $jt) {
        $stmt->bind_param('ssssis', $jt[0], $jt[1], $jt[2], $jt[3], $jt[4], $now);
        $stmt->execute();
    }

    // Purpose options
    $purposes = [
        ['marketing','Marketing Material','bi-megaphone',0],
        ['insurance','Insurance Claim','bi-shield-check',1],
        ['progress_report','Progress Report','bi-graph-up',2],
        ['personal','Personal Keepsake','bi-heart',3],
        ['social_media','Social Media','bi-phone',4],
        ['real_estate_listing','Real Estate Listing','bi-house-door',5],
        ['legal_evidence','Legal / Evidence','bi-file-earmark-text',6],
        ['other','Other','bi-three-dots',7],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO purpose_options (value,label,icon,is_active,sort_order,created_at) VALUES (?,?,?,1,?,?)");
    foreach ($purposes as $po) {
        $stmt->bind_param('sssis', $po[0], $po[1], $po[2], $po[3], $now);
        $stmt->execute();
    }

    // Heard about
    $heardAbout = [
        ['google','Google Search','bi-google',0],
        ['social_media','Social Media','bi-phone',1],
        ['referral','Referral','bi-people',2],
        ['website','Website','bi-globe',3],
        ['other','Other','bi-three-dots',4],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO heard_about_options (value,label,icon,is_active,sort_order,created_at) VALUES (?,?,?,1,?,?)");
    foreach ($heardAbout as $ha) {
        $stmt->bind_param('sssis', $ha[0], $ha[1], $ha[2], $ha[3], $now);
        $stmt->execute();
    }
}

// ─── Helper: schema DDL ──────────────────────────────────────────────
function getSchemaStatements(): array {
    return [
        'app_settings' => "CREATE TABLE IF NOT EXISTS `app_settings` (
            `id` int NOT NULL AUTO_INCREMENT,
            `business_name` varchar(200) NOT NULL,
            `logo_url` varchar(500) NOT NULL DEFAULT '',
            `primary_color` varchar(7) NOT NULL DEFAULT '#0d6efd',
            `contact_email` varchar(200) NOT NULL DEFAULT '',
            `tagline` varchar(300) NOT NULL DEFAULT 'Drone Flight Brief',
            `show_heard_about` tinyint(1) NOT NULL DEFAULT 1,
            `show_customer_type_toggle` tinyint(1) NOT NULL DEFAULT 1,
            `show_purpose_fields` tinyint(1) NOT NULL DEFAULT 1,
            `show_output_format` tinyint(1) NOT NULL DEFAULT 1,
            `guide_mode` tinyint(1) NOT NULL DEFAULT 1,
            `dark_mode` tinyint(1) NOT NULL DEFAULT 1,
            `active_template` varchar(40) NOT NULL DEFAULT 'general',
            `modules_json` text DEFAULT NULL,
            `solo_mode` tinyint(1) NOT NULL DEFAULT 0,
            `default_drone_model` varchar(50) NOT NULL DEFAULT 'mini_4_pro',
            `form_fields_json` text DEFAULT NULL,
            `planning_panels_json` text DEFAULT NULL,
            `pilot_steps_json` text DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` int NOT NULL AUTO_INCREMENT,
            `username` varchar(80) NOT NULL,
            `display_name` varchar(120) NOT NULL,
            `password_hash` varchar(256) NOT NULL,
            `is_active_user` tinyint(1) DEFAULT NULL,
            `role` varchar(20) NOT NULL,
            `email` varchar(200) DEFAULT NULL,
            `phone` varchar(50) DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `flying_id` varchar(100) DEFAULT NULL,
            `operator_id` varchar(100) DEFAULT NULL,
            `flying_id_expiry` date DEFAULT NULL,
            `operator_id_expiry` date DEFAULT NULL,
            `insurance_provider` varchar(200) DEFAULT NULL,
            `insurance_policy_no` varchar(100) DEFAULT NULL,
            `insurance_expiry` date DEFAULT NULL,
            `availability_status` varchar(20) DEFAULT NULL,
            `pilot_bio` text DEFAULT NULL,
            `a2_cofc_expiry` date DEFAULT NULL,
            `a2_cofc_number` varchar(100) DEFAULT NULL,
            `gvc_mr_expiry` date DEFAULT NULL,
            `gvc_fw_expiry` date DEFAULT NULL,
            `gvc_level` varchar(20) DEFAULT NULL,
            `gvc_cert_number` varchar(100) DEFAULT NULL,
            `oa_type` varchar(30) DEFAULT NULL,
            `oa_reference` varchar(100) DEFAULT NULL,
            `oa_expiry` date DEFAULT NULL,
            `practical_competency_date` date DEFAULT NULL,
            `mentor_examiner` varchar(200) DEFAULT NULL,
            `article16_agreed` tinyint(1) DEFAULT NULL,
            `article16_agreed_date` date DEFAULT NULL,
            `address_line1` varchar(200) DEFAULT NULL,
            `address_line2` varchar(200) DEFAULT NULL,
            `address_city` varchar(100) DEFAULT NULL,
            `address_county` varchar(100) DEFAULT NULL,
            `address_postcode` varchar(20) DEFAULT NULL,
            `address_country` varchar(100) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'job_types' => "CREATE TABLE IF NOT EXISTS `job_types` (
            `id` int NOT NULL AUTO_INCREMENT,
            `value` varchar(50) NOT NULL,
            `label` varchar(100) NOT NULL,
            `icon` varchar(50) NOT NULL,
            `category` varchar(30) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `sort_order` int NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `value` (`value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'purpose_options' => "CREATE TABLE IF NOT EXISTS `purpose_options` (
            `id` int NOT NULL AUTO_INCREMENT,
            `value` varchar(50) NOT NULL,
            `label` varchar(100) NOT NULL,
            `icon` varchar(50) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `sort_order` int NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `value` (`value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'heard_about_options' => "CREATE TABLE IF NOT EXISTS `heard_about_options` (
            `id` int NOT NULL AUTO_INCREMENT,
            `value` varchar(50) NOT NULL,
            `label` varchar(100) NOT NULL,
            `icon` varchar(50) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `sort_order` int NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `value` (`value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'flight_plans' => "CREATE TABLE IF NOT EXISTS `flight_plans` (
            `id` int NOT NULL AUTO_INCREMENT,
            `reference` varchar(20) NOT NULL,
            `status` varchar(20) NOT NULL,
            `customer_name` varchar(200) NOT NULL,
            `customer_email` varchar(200) NOT NULL,
            `customer_phone` varchar(50) DEFAULT NULL,
            `customer_company` varchar(200) DEFAULT NULL,
            `heard_about` varchar(100) DEFAULT NULL,
            `job_type` varchar(30) NOT NULL,
            `job_description` text DEFAULT NULL,
            `preferred_dates` varchar(200) DEFAULT NULL,
            `time_window` varchar(100) DEFAULT NULL,
            `urgency` varchar(20) DEFAULT NULL,
            `special_requirements` text DEFAULT NULL,
            `location_address` varchar(500) DEFAULT NULL,
            `location_lat` float DEFAULT NULL,
            `location_lng` float DEFAULT NULL,
            `area_polygon` text DEFAULT NULL,
            `estimated_area_sqm` float DEFAULT NULL,
            `altitude_preset` varchar(20) DEFAULT NULL,
            `altitude_custom_m` float DEFAULT NULL,
            `camera_angle` varchar(20) DEFAULT NULL,
            `video_resolution` varchar(10) DEFAULT NULL,
            `photo_mode` varchar(30) DEFAULT NULL,
            `no_fly_notes` text DEFAULT NULL,
            `privacy_notes` text DEFAULT NULL,
            `customer_type` varchar(10) DEFAULT NULL,
            `business_abn` varchar(50) DEFAULT NULL,
            `billing_contact` varchar(200) DEFAULT NULL,
            `billing_email` varchar(200) DEFAULT NULL,
            `purchase_order` varchar(100) DEFAULT NULL,
            `footage_purpose` varchar(50) DEFAULT NULL,
            `footage_purpose_other` varchar(300) DEFAULT NULL,
            `output_format` varchar(30) DEFAULT NULL,
            `video_duration` varchar(100) DEFAULT NULL,
            `shot_types` text DEFAULT NULL,
            `delivery_timeline` varchar(50) DEFAULT NULL,
            `drone_model` varchar(50) DEFAULT 'mini_4_pro',
            `admin_notes` text DEFAULT NULL,
            `consent_given` tinyint(1) NOT NULL DEFAULT 0,
            `source` varchar(30) DEFAULT 'public_form',
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ix_flight_plans_reference` (`reference`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'pilot_equipment' => "CREATE TABLE IF NOT EXISTS `pilot_equipment` (
            `id` int NOT NULL AUTO_INCREMENT,
            `user_id` int NOT NULL,
            `drone_model` varchar(200) NOT NULL,
            `serial_number` varchar(100) DEFAULT NULL,
            `registration_id` varchar(100) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `class_mark` varchar(20) DEFAULT NULL,
            `mtom_grams` int DEFAULT NULL,
            `has_camera` tinyint(1) DEFAULT NULL,
            `green_light_type` varchar(20) DEFAULT NULL,
            `green_light_weight_grams` int DEFAULT NULL,
            `has_low_speed_mode` tinyint(1) DEFAULT NULL,
            `remote_id_capable` tinyint(1) DEFAULT NULL,
            `max_speed_ms` float DEFAULT NULL,
            `max_dimension_m` float DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `pilot_equipment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'waypoints' => "CREATE TABLE IF NOT EXISTS `waypoints` (
            `id` int NOT NULL AUTO_INCREMENT,
            `flight_plan_id` int NOT NULL,
            `index` int NOT NULL,
            `lat` float NOT NULL,
            `lng` float NOT NULL,
            `altitude_m` float DEFAULT NULL,
            `speed_ms` float DEFAULT NULL,
            `heading_deg` float DEFAULT NULL,
            `gimbal_pitch_deg` float DEFAULT NULL,
            `turn_mode` varchar(50) DEFAULT NULL,
            `turn_damping_dist` float DEFAULT NULL,
            `hover_time_s` float DEFAULT NULL,
            `action_type` varchar(30) DEFAULT NULL,
            `poi_lat` float DEFAULT NULL,
            `poi_lng` float DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `flight_plan_id` (`flight_plan_id`),
            CONSTRAINT `waypoints_ibfk_1` FOREIGN KEY (`flight_plan_id`) REFERENCES `flight_plans` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'pois' => "CREATE TABLE IF NOT EXISTS `pois` (
            `id` int NOT NULL AUTO_INCREMENT,
            `flight_plan_id` int NOT NULL,
            `lat` float NOT NULL,
            `lng` float NOT NULL,
            `label` varchar(200) DEFAULT NULL,
            `sort_order` int DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `flight_plan_id` (`flight_plan_id`),
            CONSTRAINT `pois_ibfk_1` FOREIGN KEY (`flight_plan_id`) REFERENCES `flight_plans` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'uploads' => "CREATE TABLE IF NOT EXISTS `uploads` (
            `id` int NOT NULL AUTO_INCREMENT,
            `flight_plan_id` int NOT NULL,
            `original_filename` varchar(300) NOT NULL,
            `stored_filename` varchar(300) NOT NULL,
            `file_size` int DEFAULT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `flight_plan_id` (`flight_plan_id`),
            CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`flight_plan_id`) REFERENCES `flight_plans` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'shared_links' => "CREATE TABLE IF NOT EXISTS `shared_links` (
            `id` int NOT NULL AUTO_INCREMENT,
            `flight_plan_id` int NOT NULL,
            `token` varchar(64) NOT NULL,
            `created_at` datetime DEFAULT NULL,
            `expires_at` datetime DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ix_shared_links_token` (`token`),
            KEY `flight_plan_id` (`flight_plan_id`),
            CONSTRAINT `shared_links_ibfk_1` FOREIGN KEY (`flight_plan_id`) REFERENCES `flight_plans` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
            `id` int NOT NULL AUTO_INCREMENT,
            `flight_plan_id` int NOT NULL,
            `pilot_id` int DEFAULT NULL,
            `assigned_by_id` int DEFAULT NULL,
            `status` varchar(30) NOT NULL,
            `scheduled_date` date DEFAULT NULL,
            `scheduled_time` varchar(50) DEFAULT NULL,
            `assignment_notes` text DEFAULT NULL,
            `pilot_notes` text DEFAULT NULL,
            `completion_notes` text DEFAULT NULL,
            `decline_reason` text DEFAULT NULL,
            `risk_assessment_completed` tinyint(1) NOT NULL DEFAULT 0,
            `assigned_at` datetime DEFAULT NULL,
            `accepted_at` datetime DEFAULT NULL,
            `started_at` datetime DEFAULT NULL,
            `completed_at` datetime DEFAULT NULL,
            `delivered_at` datetime DEFAULT NULL,
            `closed_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            `equipment_id` int DEFAULT NULL,
            `time_of_day` varchar(20) DEFAULT NULL,
            `proximity_to_people` varchar(30) DEFAULT NULL,
            `environment_type` varchar(30) DEFAULT NULL,
            `proximity_to_buildings` varchar(20) DEFAULT NULL,
            `airspace_type` varchar(20) DEFAULT NULL,
            `vlos_type` varchar(20) DEFAULT NULL,
            `speed_mode` varchar(20) DEFAULT NULL,
            `operational_category` varchar(30) DEFAULT NULL,
            `category_determined_at` datetime DEFAULT NULL,
            `category_blockers` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `flight_plan_id` (`flight_plan_id`),
            KEY `pilot_id` (`pilot_id`),
            KEY `assigned_by_id` (`assigned_by_id`),
            KEY `equipment_id` (`equipment_id`),
            CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`flight_plan_id`) REFERENCES `flight_plans` (`id`),
            CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`pilot_id`) REFERENCES `users` (`id`),
            CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`assigned_by_id`) REFERENCES `users` (`id`),
            CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`equipment_id`) REFERENCES `pilot_equipment` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'order_activities' => "CREATE TABLE IF NOT EXISTS `order_activities` (
            `id` int NOT NULL AUTO_INCREMENT,
            `order_id` int NOT NULL,
            `user_id` int DEFAULT NULL,
            `action` varchar(50) NOT NULL,
            `old_value` varchar(100) DEFAULT NULL,
            `new_value` varchar(100) DEFAULT NULL,
            `details` text DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `order_activities_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
            CONSTRAINT `order_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'order_deliverables' => "CREATE TABLE IF NOT EXISTS `order_deliverables` (
            `id` int NOT NULL AUTO_INCREMENT,
            `order_id` int NOT NULL,
            `uploaded_by_id` int NOT NULL,
            `original_filename` varchar(300) NOT NULL,
            `stored_filename` varchar(300) NOT NULL,
            `file_size` int DEFAULT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `description` text DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            KEY `uploaded_by_id` (`uploaded_by_id`),
            CONSTRAINT `order_deliverables_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
            CONSTRAINT `order_deliverables_ibfk_2` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'risk_assessments' => "CREATE TABLE IF NOT EXISTS `risk_assessments` (
            `id` int NOT NULL AUTO_INCREMENT,
            `order_id` int NOT NULL,
            `pilot_id` int NOT NULL,
            `site_ground_hazards` tinyint(1) NOT NULL DEFAULT 0,
            `site_obstacles_mapped` tinyint(1) NOT NULL DEFAULT 0,
            `site_50m_separation` tinyint(1) NOT NULL DEFAULT 0,
            `site_150m_residential` tinyint(1) NOT NULL DEFAULT 0,
            `airspace_frz_checked` tinyint(1) NOT NULL DEFAULT 0,
            `airspace_restricted_checked` tinyint(1) NOT NULL DEFAULT 0,
            `airspace_notams_reviewed` tinyint(1) NOT NULL DEFAULT 0,
            `airspace_max_altitude_confirmed` tinyint(1) NOT NULL DEFAULT 0,
            `airspace_planned_altitude` float DEFAULT NULL,
            `weather_acceptable` tinyint(1) NOT NULL DEFAULT 0,
            `weather_wind_speed` float DEFAULT NULL,
            `weather_wind_direction` varchar(50) DEFAULT NULL,
            `weather_visibility` float DEFAULT NULL,
            `weather_precipitation` varchar(50) DEFAULT NULL,
            `weather_temperature` float DEFAULT NULL,
            `equip_condition_ok` tinyint(1) NOT NULL DEFAULT 0,
            `equip_battery_adequate` tinyint(1) NOT NULL DEFAULT 0,
            `equip_battery_level` int DEFAULT NULL,
            `equip_propellers_ok` tinyint(1) NOT NULL DEFAULT 0,
            `equip_gps_lock` tinyint(1) NOT NULL DEFAULT 0,
            `equip_remote_ok` tinyint(1) NOT NULL DEFAULT 0,
            `equip_remote_id_active` tinyint(1) NOT NULL DEFAULT 0,
            `imsafe_illness` tinyint(1) NOT NULL DEFAULT 0,
            `imsafe_medication` tinyint(1) NOT NULL DEFAULT 0,
            `imsafe_stress` tinyint(1) NOT NULL DEFAULT 0,
            `imsafe_alcohol` tinyint(1) NOT NULL DEFAULT 0,
            `imsafe_fatigue` tinyint(1) NOT NULL DEFAULT 0,
            `imsafe_eating` tinyint(1) NOT NULL DEFAULT 0,
            `perms_flyer_id_valid` tinyint(1) NOT NULL DEFAULT 0,
            `perms_operator_id_displayed` tinyint(1) NOT NULL DEFAULT 0,
            `perms_insurance_valid` tinyint(1) NOT NULL DEFAULT 0,
            `perms_authorizations_checked` tinyint(1) NOT NULL DEFAULT 0,
            `emergency_landing_site` tinyint(1) NOT NULL DEFAULT 0,
            `emergency_contacts_confirmed` tinyint(1) NOT NULL DEFAULT 0,
            `emergency_contingency_plan` tinyint(1) NOT NULL DEFAULT 0,
            `operational_category` varchar(30) DEFAULT NULL,
            `category_version` int DEFAULT NULL,
            `night_green_light_fitted` tinyint(1) DEFAULT NULL,
            `night_green_light_on` tinyint(1) DEFAULT NULL,
            `night_vlos_maintainable` tinyint(1) DEFAULT NULL,
            `night_orientation_visible` tinyint(1) DEFAULT NULL,
            `a2_distance_confirmed` tinyint(1) DEFAULT NULL,
            `a2_low_speed_active` tinyint(1) DEFAULT NULL,
            `a2_segregation_assessed` tinyint(1) DEFAULT NULL,
            `a3_150m_from_areas` tinyint(1) DEFAULT NULL,
            `a3_50m_from_people` tinyint(1) DEFAULT NULL,
            `a3_50m_from_buildings` tinyint(1) DEFAULT NULL,
            `specific_ops_manual_reviewed` tinyint(1) DEFAULT NULL,
            `specific_insurance_confirmed` tinyint(1) DEFAULT NULL,
            `specific_oa_valid` tinyint(1) DEFAULT NULL,
            `risk_level` varchar(20) NOT NULL DEFAULT 'low',
            `decision` varchar(30) NOT NULL DEFAULT 'pending',
            `mitigation_notes` text DEFAULT NULL,
            `pilot_declaration` tinyint(1) NOT NULL DEFAULT 0,
            `gps_latitude` float DEFAULT NULL,
            `gps_longitude` float DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_id` (`order_id`),
            KEY `pilot_id` (`pilot_id`),
            CONSTRAINT `risk_assessments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
            CONSTRAINT `risk_assessments_ibfk_2` FOREIGN KEY (`pilot_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'pilot_certifications' => "CREATE TABLE IF NOT EXISTS `pilot_certifications` (
            `id` int NOT NULL AUTO_INCREMENT,
            `user_id` int NOT NULL,
            `cert_name` varchar(200) NOT NULL,
            `issuing_body` varchar(200) DEFAULT NULL,
            `cert_number` varchar(100) DEFAULT NULL,
            `issue_date` date DEFAULT NULL,
            `expiry_date` date DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `pilot_certifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'pilot_memberships' => "CREATE TABLE IF NOT EXISTS `pilot_memberships` (
            `id` int NOT NULL AUTO_INCREMENT,
            `user_id` int NOT NULL,
            `org_name` varchar(200) NOT NULL,
            `membership_number` varchar(100) DEFAULT NULL,
            `membership_type` varchar(100) DEFAULT NULL,
            `expiry_date` date DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `pilot_memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'pilot_documents' => "CREATE TABLE IF NOT EXISTS `pilot_documents` (
            `id` int NOT NULL AUTO_INCREMENT,
            `user_id` int NOT NULL,
            `doc_type` varchar(50) DEFAULT NULL,
            `label` varchar(200) NOT NULL,
            `original_filename` varchar(300) NOT NULL,
            `stored_filename` varchar(300) NOT NULL,
            `file_size` int DEFAULT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `expiry_date` date DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `pilot_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

// ─── Requirements check ──────────────────────────────────────────────
function checkRequirements(): array {
    $checks = [];
    $checks['PHP >= 8.1'] = version_compare(PHP_VERSION, '8.1.0', '>=');
    foreach (['mysqli','intl','mbstring','json','xml','gd','curl','zip'] as $ext) {
        $checks["ext-$ext"] = extension_loaded($ext);
    }
    $writable = dirname(__DIR__) . '/writable';
    $checks['writable/ directory'] = is_writable($writable);
    foreach (['cache','logs','session','uploads'] as $dir) {
        $path = $writable . '/' . $dir;
        if (!is_dir($path)) @mkdir($path, 0755, true);
        $checks["writable/$dir/"] = is_dir($path) && is_writable($path);
    }
    return $checks;
}

$allPass = true;
if ($step === 1) {
    $requirements = checkRequirements();
    foreach ($requirements as $ok) { if (!$ok) $allPass = false; }
}

// ─── HTML Output ─────────────────────────────────────────────────────
$steps = ['Requirements', 'Database', 'Branding', 'Admin Account', 'Finalize'];
$detectedURL = detectBaseURL();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install FlyingPlan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .installer { max-width: 600px; width: 100%; }
        .step-bar { display: flex; gap: 4px; margin-bottom: 2rem; }
        .step-bar .sb { flex: 1; height: 4px; border-radius: 2px; background: #495057; }
        .step-bar .sb.done { background: #198754; }
        .step-bar .sb.active { background: #0d6efd; }
        .check-ok { color: #198754; }
        .check-fail { color: #dc3545; }
    </style>
</head>
<body>
<div class="installer px-3">
    <div class="text-center mb-4">
        <h2><i class="bi bi-airplane"></i> FlyingPlan</h2>
        <p class="text-muted">Setup Wizard<?= $step <= 5 ? ' &mdash; Step ' . min($step, 5) . ' of 5' : '' ?></p>
    </div>

    <?php if ($step <= 5): ?>
    <div class="step-bar">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="sb <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>"></div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- ═══ Step 1: Requirements ═══ -->
    <?php if ($step === 1): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-gear"></i> System Requirements</div>
        <div class="card-body">
            <table class="table table-sm mb-3">
                <?php foreach ($requirements as $label => $ok): ?>
                <tr>
                    <td><?= htmlspecialchars($label) ?></td>
                    <td class="text-end">
                        <?php if ($ok): ?>
                            <i class="bi bi-check-circle-fill check-ok"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill check-fail"></i>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php if ($allPass): ?>
                <form method="POST"><input type="hidden" name="step" value="2">
                    <button class="btn btn-primary w-100">Next: Database <i class="bi bi-arrow-right"></i></button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning mb-0">Please fix the failed requirements and <a href="?step=1">reload</a>.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Step 2: Database ═══ -->
    <?php if ($step === 2): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-database"></i> Database Connection</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="setup_db">
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label">Hostname</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Port</label>
                        <input type="number" name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? 'flyingplan') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="db_pass" class="form-control" value="">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="create_db" value="1" class="form-check-input" id="createDb" checked>
                            <label class="form-check-label" for="createDb">Create database if it doesn't exist</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3">Connect & Create Tables <i class="bi bi-arrow-right"></i></button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Step 3: Branding ═══ -->
    <?php if ($step === 3): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-palette"></i> Business Settings</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="save_branding">
                <div class="mb-3">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="business_name" class="form-control" value="FlyingPlan">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" placeholder="you@company.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="tagline" class="form-control" value="Drone Flight Brief">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Primary Color</label>
                        <div class="input-group">
                            <input type="color" name="primary_color" class="form-control form-control-color" value="#0d6efd" id="colorPicker">
                            <input type="text" class="form-control" id="colorText" value="#0d6efd" readonly>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Logo URL</label>
                        <input type="url" name="logo_url" class="form-control" placeholder="https://...">
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="dark_mode" value="1" class="form-check-input" id="darkMode" checked>
                    <label class="form-check-label" for="darkMode">Dark Mode</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Next: Admin Account <i class="bi bi-arrow-right"></i></button>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('colorPicker').addEventListener('input', function() {
        document.getElementById('colorText').value = this.value;
    });
    </script>
    <?php endif; ?>

    <!-- ═══ Step 4: Admin Account ═══ -->
    <?php if ($step === 4): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-person-gear"></i> Admin Account</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create_admin">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="admin_user" class="form-control" value="admin" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="admin_display" class="form-control" value="Administrator" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="admin_email" class="form-control" placeholder="admin@company.com">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="admin_pass" class="form-control" minlength="8" required>
                        <div class="form-text">Minimum 8 characters</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="admin_pass2" class="form-control" minlength="8" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3">Next: Finalize <i class="bi bi-arrow-right"></i></button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Step 5: Finalize ═══ -->
    <?php if ($step === 5): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-check2-square"></i> Finalize Installation</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="finalize">
                <div class="mb-3">
                    <label class="form-label">Site URL</label>
                    <input type="url" name="base_url" class="form-control" value="<?= htmlspecialchars($detectedURL) ?>">
                    <div class="form-text">Auto-detected from your browser. Change if needed (e.g. for a custom domain).</div>
                </div>
                <p class="text-muted small">This will write the <code>.env</code> configuration file and mark the installation as complete.</p>
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-lg"></i> Complete Installation</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Step 6: Done ═══ -->
    <?php if ($step === 6): ?>
    <div class="card border-success">
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
            <h3 class="mt-3">Installation Complete!</h3>
            <p class="text-muted">FlyingPlan is ready to use.</p>
            <a href="/login" class="btn btn-primary btn-lg mb-3"><i class="bi bi-box-arrow-in-right"></i> Go to Login</a>
            <hr>
            <form method="POST" class="mt-2">
                <input type="hidden" name="action" value="delete_installer">
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Delete Installer File</button>
                <div class="form-text mt-1">Recommended for security.</div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <p class="text-center text-muted mt-4 small">FlyingPlan v1.0 &mdash; Drone Flight Planning System</p>
</div>
</body>
</html>
