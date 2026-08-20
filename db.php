<?php
/*
 * ============================================================
 * ESP-SWITCH5 REMOTE - db.php
 * ============================================================
 *
 * Database:
 *     TiDB Cloud
 *
 * Configuration:
 *     config.php
 *
 * Credentials:
 *     Render Environment Variables
 * ============================================================
 */


/* =========================================================
   LOAD CONFIGURATION
========================================================= */

require_once __DIR__ . "/config.php";


/* =========================================================
   DATABASE CONNECTION
========================================================= */

$conn = new mysqli(
    $db_host,
    $db_user,
    $db_password,
    $db_name,
    (int)$db_port
);


/* =========================================================
   CONNECTION ERROR
========================================================= */

if ($conn->connect_error) {

    if (DEBUG_MODE) {

        die(
            "Database connection failed: " .
            $conn->connect_error
        );

    } else {

        die(
            "Database connection failed."
        );
    }
}


/* =========================================================
   UTF-8
========================================================= */

$conn->set_charset("utf8mb4");

?>