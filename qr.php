<?php

$controller_id = isset($_GET['controller_id'])
    ? trim($_GET['controller_id'])
    : '';

if ($controller_id === '') {
    die('Controller ID missing.');
}

if (!preg_match('/^[A-Za-z0-9_-]+$/', $controller_id)) {
    die('Invalid Controller ID.');
}

$base_url = 'https://esp-switch5b-remote.onrender.com/c/';

$controller_url = $base_url . rawurlencode($controller_id);

$qr_url =
    'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' .
    urlencode($controller_url);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Controller QR Code</title>

<style>
body {
    font-family: Arial, sans-serif;
    text-align: center;
    margin-top: 40px;
}

.box {
    display: inline-block;
    padding: 25px;
    border: 1px solid #ccc;
    border-radius: 12px;
}

h2 {
    margin-bottom: 20px;
}

img {
    width: 300px;
    height: 300px;
}

.url {
    margin-top: 15px;
    word-break: break-all;
    font-size: 16px;
}

button {
    padding: 10px 20px;
    margin-top: 20px;
    cursor: pointer;
}
</style>

</head>

<body>

<div class="box">

<h2>Controller QR Code</h2>

<p>
<strong>
<?= htmlspecialchars($controller_id) ?>
</strong>
</p>

<img
    src="<?= htmlspecialchars($qr_url) ?>"
    alt="QR Code"
>

<div class="url">

<a
    href="<?= htmlspecialchars($controller_url) ?>"
    target="_blank"
>
<?= htmlspecialchars($controller_url) ?>
</a>

</div>

<button onclick="window.print()">
Print QR Code
</button>

</div>

</body>
</html>
