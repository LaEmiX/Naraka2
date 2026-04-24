<?php

declare(strict_types=1);

session_start();

$pdo = require __DIR__ . '/config/database.php';
$currentLand = require __DIR__ . '/config/land.php';

$landSlug = htmlspecialchars($currentLand['slug'], ENT_QUOTES, 'UTF-8');
$landName = htmlspecialchars($currentLand['name'], ENT_QUOTES, 'UTF-8');

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

<?php if ($landSlug === 'city') { ?>

    <a href="/switch_land.php?land=echoes">Vai a Echoes</a>

<?php } else { ?>

    <a href="/switch_land.php?land=city">Torna a City</a>

<?php } ?>

</body>
</html>

<?php
// by LaEmiX