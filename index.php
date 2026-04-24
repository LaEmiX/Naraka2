<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';

$pdo = require __DIR__ . '/config/database.php';
$currentLand = require __DIR__ . '/config/land.php';

$landSlug = $currentLand['slug'];
$landName = htmlspecialchars($currentLand['name'], ENT_QUOTES, 'UTF-8');

$user = $_SESSION['user'];

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naraka</title>
</head>
<body>

<h1>Naraka</h1>

<p>Land attiva: <?php echo $landName; ?></p>

<p>Benvenuto, <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>

<p>
    <a href="/characters.php">Entra nel gioco</a>
</p>

<p>
    <a href="/logout.php">Logout</a>
</p>

<br>

<?php if ($landSlug === 'city') { ?>

    <a href="/switch_land.php?land=echoes">Vai a Echoes</a>

<?php } else { ?>

    <a href="/switch_land.php?land=city">Torna a City</a>

<?php } ?>

</body>
</html>

<?php
// by LaEmiX
