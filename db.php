<?php
/*
 * ============================================================
 * ESP-SWITCH5A REMOTE - db.php
 * ============================================================
 *
 * Database:
 *     TiDB Cloud
 *
 * SSL/TLS:
 *     REQUIRED
 *
 * Configuration:
 *     config.php
 * ============================================================
 */


/* =========================================================
   LOAD CONFIGURATION
========================================================= */

require_once __DIR__ . "/config.php";


/* =========================================================
   DATABASE CONNECTION
========================================================= */

$conn = mysqli_init();


/* =========================================================
   ENABLE TLS / SSL
========================================================= */

mysqli_ssl_set(
    $conn,
    null,
    null,
    null,
    null,
    null
);


/* =========================================================
   CONNECT TO TiDB CLOUD
========================================================= */

if (
    !$conn->real_connect(
        $db_host,
        $db_user,
        $db_password,
        $db_name,
        (int)$db_port,
        null,
        MYSQLI_CLIENT_SSL
    )
) {

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
