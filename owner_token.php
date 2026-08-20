<?php
/*
============================================================
 ESP-SWITCH5 REMOTE
 OWNER-ONLY DEVICE TOKEN + CONTROLLER STATUS MANAGEMENT
============================================================

Purpose:
    1. Change device_token
    2. Activate / deactivate a controller

IMPORTANT:
    This page is OWNER ONLY.

    Customers must NOT receive:
        owner_token.php

Authentication:
    TOKEN_PASSWORD environment variable

Database:
    TiDB Cloud

Database:
    esp_switch5

Table:
    controllers

Important field:
    active

    active = 1
        Controller ACTIVE

    active = 0
        Controller DEACTIVATED

Timezone:
    Asia/Kolkata
============================================================
*/


/* =========================================================
   CONFIGURATION
========================================================= */

require_once __DIR__ . "/config.php";


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . "/db.php";


/* =========================================================
   SESSION
========================================================= */

session_start();


/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET["logout"])) {

    $_SESSION = [];

    session_destroy();

    header(
        "Location: owner_token.php"
    );

    exit;
}


/* =========================================================
   OWNER LOGIN
========================================================= */

$login_error = "";

if (isset($_POST["owner_login"])) {

    $password =
        $_POST["owner_password"] ?? "";


    if (
        $token_password !== "" &&
        hash_equals(
            $token_password,
            $password
        )
    ) {

        $_SESSION["esp_owner"] = true;

        header(
            "Location: owner_token.php"
        );

        exit;

    } else {

        $login_error =
            "Invalid owner password.";
    }
}


/* =========================================================
   OWNER LOGIN PAGE
========================================================= */

if (
    !isset($_SESSION["esp_owner"]) ||
    $_SESSION["esp_owner"] !== true
) {

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
ESP-SWITCH5 - Owner Login
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    padding: 20px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f2f2f2;
}

.login-box {

    max-width: 450px;

    margin: 80px auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.15);

    text-align: center;
}

h1 {

    margin-top: 0;

    color: #333;
}

.warning {

    background: #fff3cd;

    color: #856404;

    border: 1px solid #ffeeba;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    font-size: 14px;
}

input {

    width: 100%;

    padding: 12px;

    font-size: 16px;

    border: 1px solid #aaa;

    border-radius: 6px;

    margin: 15px 0;
}

button {

    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 6px;

    background: #343a40;

    color: white;

    font-size: 16px;

    cursor: pointer;
}

button:hover {

    opacity: 0.85;
}

.error {

    color: #dc3545;

    margin-bottom: 10px;

    font-weight: bold;
}

.small {

    margin-top: 15px;

    color: #777;

    font-size: 13px;
}

</style>

</head>

<body>

<div class="login-box">

<h1>
ESP-SWITCH5
</h1>

<h2>
OWNER ACCESS
</h2>

<div class="warning">

This page is for owner use only.<br>
Do not give this page or its password to customers.

</div>

<?php

if ($login_error !== "") {

    echo
        '<div class="error">' .
        htmlspecialchars(
            $login_error,
            ENT_QUOTES,
            "UTF-8"
        ) .
        '</div>';
}

?>

<form method="post">

<input
    type="password"
    name="owner_password"
    placeholder="Enter owner password"
    required
    autofocus
>

<button
    type="submit"
    name="owner_login"
>
OWNER LOGIN
</button>

</form>

<div class="small">

ESP-SWITCH5 Owner Management

</div>

</div>

</body>

</html>

<?php

exit;

}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   CHANGE DEVICE TOKEN
========================================================= */

if (isset($_POST["change_token"])) {

    $controller_id =
        trim(
            $_POST["controller_id"] ?? ""
        );

    $new_token =
        trim(
            $_POST["new_token"] ?? ""
        );


    /* -----------------------------------------------------
       VALIDATE CONTROLLER ID
    ----------------------------------------------------- */

    if ($controller_id === "") {

        $message =
            "Controller ID is required.";

        $message_type =
            "error";
    }


    /* -----------------------------------------------------
       VALIDATE TOKEN
    ----------------------------------------------------- */

    elseif ($new_token === "") {

        $message =
            "New Device Token is required.";

        $message_type =
            "error";
    }


    elseif (
        !preg_match(
            '/^[A-Za-z0-9_-]{8,100}$/',
            $new_token
        )
    ) {

        $message =
            "Invalid Device Token. " .
            "Use 8-100 characters containing " .
            "letters, numbers, hyphen or underscore.";

        $message_type =
            "error";
    }


    else {

        /* -------------------------------------------------
           VERIFY CONTROLLER EXISTS
        ------------------------------------------------- */

        $stmt =
            $conn->prepare("
                SELECT
                    controller_id
                FROM controllers
                WHERE controller_id = ?
                LIMIT 1
            ");


        if (!$stmt) {

            $message =
                "Controller query preparation failed.";

            $message_type =
                "error";

        } else {

            $stmt->bind_param(
                "s",
                $controller_id
            );


            if (!$stmt->execute()) {

                $message =
                    "Controller verification failed.";

                $message_type =
                    "error";

                $stmt->close();

            } else {

                $result =
                    $stmt->get_result();


                if ($result->num_rows === 0) {

                    $message =
                        "Controller not found.";

                    $message_type =
                        "error";

                    $stmt->close();

                } else {

                    $stmt->close();


                    /* -------------------------------------
                       UPDATE DEVICE TOKEN
                    ------------------------------------- */

                    $update =
                        $conn->prepare("
                            UPDATE controllers
                            SET device_token = ?
                            WHERE controller_id = ?
                        ");


                    if (!$update) {

                        $message =
                            "Token update preparation failed.";

                        $message_type =
                            "error";

                    } else {

                        $update->bind_param(
                            "ss",
                            $new_token,
                            $controller_id
                        );


                        if ($update->execute()) {

                            $message =
                                "Device Token changed successfully " .
                                "for controller " .
                                $controller_id .
                                ".";

                            $message_type =
                                "success";

                        } else {

                            $message =
                                "Device Token update failed.";

                            $message_type =
                                "error";
                        }


                        $update->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   ACTIVATE / DEACTIVATE CONTROLLER
========================================================= */

if (isset($_POST["change_status"])) {

    $controller_id =
        trim(
            $_POST["controller_id"] ?? ""
        );

    $new_status =
        isset($_POST["new_status"])
            ? (int)$_POST["new_status"]
            : -1;


    /* -----------------------------------------------------
       VALIDATE CONTROLLER
    ----------------------------------------------------- */

    if ($controller_id === "") {

        $message =
            "Controller ID is required.";

        $message_type =
            "error";
    }


    /* -----------------------------------------------------
       VALIDATE STATUS
    ----------------------------------------------------- */

    elseif (
        $new_status !== 0 &&
        $new_status !== 1
    ) {

        $message =
            "Invalid controller status.";

        $message_type =
            "error";
    }


    else {

        /*
         * Get current controller information.
         */

        $stmt =
            $conn->prepare("
                SELECT
                    controller_id,
                    device_token,
                    active
                FROM controllers
                WHERE controller_id = ?
                LIMIT 1
            ");


        if (!$stmt) {

            $message =
                "Controller status query failed.";

            $message_type =
                "error";

        } else {

            $stmt->bind_param(
                "s",
                $controller_id
            );


            if (!$stmt->execute()) {

                $message =
                    "Controller status query failed.";

                $message_type =
                    "error";

                $stmt->close();

            } else {

                $result =
                    $stmt->get_result();


                if ($result->num_rows === 0) {

                    $message =
                        "Controller not found.";

                    $message_type =
                        "error";

                    $stmt->close();

                } else {

                    $controller =
                        $result->fetch_assoc();

                    $stmt->close();


                    /* -------------------------------------
                       UPDATE ACTIVE FIELD
                    ------------------------------------- */

                    $update =
                        $conn->prepare("
                            UPDATE controllers
                            SET active = ?
                            WHERE controller_id = ?
                        ");


                    if (!$update) {

                        $message =
                            "Status update preparation failed.";

                        $message_type =
                            "error";

                    } else {

                        $update->bind_param(
                            "is",
                            $new_status,
                            $controller_id
                        );


                        if ($update->execute()) {

                            if ($new_status === 1) {

                                $message =
                                    "Controller " .
                                    $controller_id .
                                    " has been ACTIVATED.";

                            } else {

                                $message =
                                    "Controller " .
                                    $controller_id .
                                    " has been DEACTIVATED.";
                            }

                            $message_type =
                                "success";

                        } else {

                            $message =
                                "Controller status update failed.";

                            $message_type =
                                "error";
                        }


                        $update->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   READ CONTROLLERS
========================================================= */

$controllers = [];


$result =
    $conn->query("
        SELECT
            controller_id,
            customer_name,
            device_token,
            active
        FROM controllers
        ORDER BY controller_id
    ");


if ($result) {

    while (
        $row =
            $result->fetch_assoc()
    ) {

        $controllers[] =
            $row;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
ESP-SWITCH5 - Owner Management
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    padding: 20px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f2f2f2;

    color: #222;
}

.container {

    max-width: 800px;

    margin: 40px auto;

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,0.15);
}

.header {

    text-align: center;

    margin-bottom: 25px;
}

.header h1 {

    margin: 0;

    color: #333;
}

.subtitle {

    color: #666;

    margin-top: 5px;
}

.owner-warning {

    background: #fff3cd;

    border: 1px solid #ffeeba;

    color: #856404;

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    text-align: center;

    font-weight: bold;
}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

    text-align: center;

    font-weight: bold;
}

.success {

    background: #d4edda;

    color: #155724;
}

.error {

    background: #f8d7da;

    color: #721c24;
}


/* =========================================================
   CONTROLLER STATUS SUMMARY
========================================================= */

.status-box {

    background: #f8f9fa;

    border: 1px solid #ddd;

    border-radius: 10px;

    padding: 20px;

    margin-bottom: 25px;
}

.status-box h2 {

    margin-top: 0;

    margin-bottom: 15px;

    text-align: center;
}

.current-status {

    text-align: center;

    font-size: 20px;

    font-weight: bold;

    margin-bottom: 15px;
}

.active-status {

    color: #198754;
}

.inactive-status {

    color: #dc3545;
}


/* =========================================================
   FORM BOX
========================================================= */

.form-box {

    background: #f8f9fa;

    border: 1px solid #ddd;

    border-radius: 10px;

    padding: 20px;

    margin-bottom: 25px;
}

.form-box h2 {

    margin-top: 0;

    color: #333;

    font-size: 20px;
}

label {

    display: block;

    font-weight: bold;

    margin-bottom: 8px;
}

select,
input[type="text"] {

    width: 100%;

    padding: 12px;

    font-size: 16px;

    border: 1px solid #aaa;

    border-radius: 6px;

    margin-bottom: 18px;
}


/* =========================================================
   BUTTONS
========================================================= */

.change-button {

    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 6px;

    background: #dc3545;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

.change-button:hover {

    opacity: 0.85;
}

.activate-button {

    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 6px;

    background: #198754;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

.deactivate-button {

    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 6px;

    background: #dc3545;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

.activate-button:hover,
.deactivate-button:hover,
.change-button:hover {

    opacity: 0.85;
}


/* =========================================================
   NOTE
========================================================= */

.note {

    margin-top: 20px;

    padding: 15px;

    background: #eef6ff;

    border: 1px solid #b8d8f5;

    border-radius: 8px;

    font-size: 14px;

    line-height: 1.5;
}

.logout {

    display: block;

    text-align: center;

    margin-top: 20px;

    color: #555;

    text-decoration: none;
}

.logout:hover {

    text-decoration: underline;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

    body {

        padding: 10px;
    }

    .container {

        padding: 18px;

        margin-top: 15px;
    }
}

</style>

</head>


<body>

<div class="container">


<div class="header">

<h1>
ESP-SWITCH5
</h1>

<div class="subtitle">
OWNER DEVICE & CONTROLLER MANAGEMENT
</div>

</div>


<div class="owner-warning">

OWNER ONLY — DO NOT GIVE THIS PAGE TO CUSTOMERS

</div>


<?php

if ($message !== "") {

?>

<div
    class="message
    <?php
        echo $message_type === "success"
            ? "success"
            : "error";
    ?>"
>

<?php

echo htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>

<?php

}

?>


<!-- ======================================================
     CONTROLLER STATUS
======================================================= -->

<div class="status-box">

<h2>
Controller Activation
</h2>


<form method="post">

<label for="status_controller_id">
Select Controller
</label>


<select
    name="controller_id"
    id="status_controller_id"
    required
    onchange="showCurrentStatus()"
>

<option value="">
-- Select Controller --
</option>


<?php

foreach (
    $controllers as $controller
) {

?>

<option
    value="<?php
        echo htmlspecialchars(
            $controller["controller_id"],
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"

    data-active="<?php
        echo (int)$controller["active"];
    ?>"
>

<?php

echo htmlspecialchars(
    $controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);


if (
    !empty(
        $controller["customer_name"]
    )
) {

    echo
        " - " .
        htmlspecialchars(
            $controller["customer_name"],
            ENT_QUOTES,
            "UTF-8"
        );
}

?>

</option>

<?php

}

?>

</select>


<div
    id="currentStatus"
    class="current-status"
>
Select a controller.
</div>


<div id="statusButtonArea">

</div>


<input
    type="hidden"
    name="new_status"
    id="new_status"
    value=""
>

<input
    type="hidden"
    name="change_status"
    value="1"
>

</form>

</div>


<!-- ======================================================
     DEVICE TOKEN MANAGEMENT
======================================================= -->

<div class="form-box">

<h2>
Device Token Management
</h2>


<form method="post">


<label for="token_controller_id">

Select Controller

</label>


<select
    name="controller_id"
    id="token_controller_id"
    required
>

<option value="">
-- Select Controller --
</option>


<?php

foreach (
    $controllers as $controller
) {

?>

<option
    value="<?php
        echo htmlspecialchars(
            $controller["controller_id"],
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
>

<?php

echo htmlspecialchars(
    $controller["controller_id"],
    ENT_QUOTES,
    "UTF-8"
);


if (
    !empty(
        $controller["customer_name"]
    )
) {

    echo
        " - " .
        htmlspecialchars(
            $controller["customer_name"],
            ENT_QUOTES,
            "UTF-8"
        );
}

?>

</option>

<?php

}

?>

</select>


<label for="new_token">

Enter New Device Token

</label>


<input
    type="text"
    name="new_token"
    id="new_token"
    placeholder="Example: ESP0001-TOKEN-2026-RAVI1"
    maxlength="100"
    required
    autocomplete="off"
>


<button
    type="submit"
    name="change_token"
    class="change-button"
    onclick="
        return confirm(
            'Are you sure you want to change the Device Token?'
        );
    "
>

CHANGE DEVICE TOKEN

</button>

</form>

</div>


<!-- ======================================================
     IMPORTANT INFORMATION
======================================================= -->

<div class="note">

<strong>Controller Activation:</strong><br><br>

<strong>ACTIVE</strong> means the controller is authorized
to communicate with the remote server.<br><br>

<strong>DEACTIVATED</strong> means the controller is
temporarily disconnected from the customer's service.

When a controller is deactivated:

<ul>

<li>
The server rejects normal ESP8266 communication.
</li>

<li>
The controller cannot receive new D1-D8 commands.
</li>

<li>
The existing device token remains unchanged.
</li>

</ul>

When the controller is activated again, the same
controller ID and device token can be used again.

<br><br>

<strong>Device Token:</strong><br><br>

Changing the Device Token replaces the existing
<strong>controllers.device_token</strong> value.

The ESP8266 must then be programmed with exactly
the same new token.

</div>


<a
    href="owner_token.php?logout=1"
    class="logout"
>
Owner Logout
</a>


</div>


<script>

/* =========================================================
   DISPLAY CURRENT STATUS
========================================================= */

function showCurrentStatus()
{

    const select =
        document.getElementById(
            "status_controller_id"
        );

    const status =
        document.getElementById(
            "currentStatus"
        );

    const buttonArea =
        document.getElementById(
            "statusButtonArea"
        );


    if (
        !select ||
        !status ||
        !buttonArea
    ) {

        return;
    }


    const selectedOption =
        select.options[
            select.selectedIndex
        ];


    if (
        !selectedOption ||
        !selectedOption.value
    ) {

        status.innerHTML =
            "Select a controller.";

        status.className =
            "current-status";

        buttonArea.innerHTML =
            "";

        return;
    }


    const active =
        selectedOption.getAttribute(
            "data-active"
        );


    if (active === "1")
    {

        status.innerHTML =
            "● ACTIVE";

        status.className =
            "current-status active-status";


        buttonArea.innerHTML =

            '<button ' +

            'type="button" ' +

            'class="deactivate-button" ' +

            'onclick="deactivateController()">' +

            'DEACTIVATE CONTROLLER' +

            '</button>';

    }
    else
    {

        status.innerHTML =
            "● DEACTIVATED";

        status.className =
            "current-status inactive-status";


        buttonArea.innerHTML =

            '<button ' +

            'type="button" ' +

            'class="activate-button" ' +

            'onclick="activateController()">' +

            'ACTIVATE CONTROLLER' +

            '</button>';
    }
}


/* =========================================================
   ACTIVATE
========================================================= */

function activateController()
{

    const select =
        document.getElementById(
            "status_controller_id"
        );

    const selected =
        select.options[
            select.selectedIndex
        ];

    if (
        !selected ||
        !selected.value
    ) {

        return;
    }


    const controller =
        selected.value;


    const answer =
        confirm(
            "Activate controller " +
            controller +
            "?"
        );


    if (!answer)
    {
        return;
    }


    document.getElementById(
        "new_status"
    ).value = "1";


    select.form.submit();
}


/* =========================================================
   DEACTIVATE
========================================================= */

function deactivateController()
{

    const select =
        document.getElementById(
            "status_controller_id"
        );

    const selected =
        select.options[
            select.selectedIndex
        ];

    if (
        !selected ||
        !selected.value
    ) {

        return;
    }


    const controller =
        selected.value;


    const answer =
        confirm(
            "Deactivate controller " +
            controller +
            "?\n\n" +
            "The customer's controller will " +
            "temporarily lose remote service."
        );


    if (!answer)
    {
        return;
    }


    document.getElementById(
        "new_status"
    ).value = "0";


    select.form.submit();
}


/* =========================================================
   INITIAL STATUS
========================================================= */

showCurrentStatus();

</script>


</body>

</html>
