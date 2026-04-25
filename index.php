<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';

$pdo = require __DIR__ . '/config/database.php';
$currentLand = require __DIR__ . '/config/land.php';

$user = $_SESSION['user'];

$stmt = $pdo->prepare("
    SELECT l.slug, c.id_character
    FROM lands l
    LEFT JOIN characters c
        ON c.id_land = l.id_land
        AND c.id_user = :id_user
    WHERE l.slug IN ('city', 'echoes')
");

$stmt->execute([
    'id_user' => (int) $user['id_user'],
]);

$landsCheck = $stmt->fetchAll();

$hasCity = false;
$hasEchoes = false;

foreach ($landsCheck as $row) {
    if ($row['slug'] === 'city' && $row['id_character']) {
        $hasCity = true;
    }

    if ($row['slug'] === 'echoes' && $row['id_character']) {
        $hasEchoes = true;
    }
}

if (!$hasCity) {
    header('Location: /onboarding.php?step=city');
    exit;
}

if (!$hasEchoes) {
    header('Location: /onboarding.php?step=echoes');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_character, name
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");

$stmt->execute([
    'id_user' => (int) $user['id_user'],
    'id_land' => (int) $currentLand['id_land'],
]);

$character = $stmt->fetch();

if (!$character) {
    $_SESSION['current_land'] = 'city';

    header('Location: /index.php');
    exit;
}

$_SESSION['character'] = [
    'id_character' => (int) $character['id_character'],
    'name' => (string) $character['name'],
];

$landSlug = (string) $currentLand['slug'];
$landName = htmlspecialchars((string) $currentLand['name'], ENT_QUOTES, 'UTF-8');
$characterName = htmlspecialchars((string) $character['name'], ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naraka</title>
</head>
<body>

<h1><?php echo $landName; ?></h1>

<p>Utente: <?php echo $username; ?></p>
<p>Personaggio: <?php echo $characterName; ?></p>

<?php if ($landSlug === 'city') { ?>

    <p><a href="/switch_land.php?land=echoes">Vai a Echoes</a></p>

<?php } else { ?>

    <p><a href="/switch_land.php?land=city">Torna a City</a></p>

<?php } ?>

<p><a href="/logout.php">Logout</a></p>

</body>
</html>

<?php
// by LaEmiX
