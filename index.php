<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';

$pdo = require __DIR__ . '/config/database.php';
$currentLand = require __DIR__ . '/config/land.php';

$user = $_SESSION['user'];

/**
 * Recupera personaggio per questa land
 */
$stmt = $pdo->prepare("
    SELECT *
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");

$stmt->execute([
    'id_user' => $user['id_user'],
    'id_land' => $currentLand['id_land'],
]);

$character = $stmt->fetch();

/**
 * Se NON esiste → creazione automatica
 */
if (!$character) {

    $defaultName = $user['username'];

    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $defaultName));

    $stmt = $pdo->prepare("
        INSERT INTO characters (id_user, id_land, name, slug)
        VALUES (:id_user, :id_land, :name, :slug)
    ");

    $stmt->execute([
        'id_user' => $user['id_user'],
        'id_land' => $currentLand['id_land'],
        'name' => $defaultName,
        'slug' => $slug,
    ]);

    $stmt = $pdo->prepare("
        SELECT *
        FROM characters
        WHERE id_user = :id_user
        AND id_land = :id_land
        LIMIT 1
    ");

    $stmt->execute([
        'id_user' => $user['id_user'],
        'id_land' => $currentLand['id_land'],
    ]);

    $character = $stmt->fetch();
}

/**
 * Salva in sessione (temporaneo, poi lo togliamo)
 */
$_SESSION['character'] = [
    'id_character' => (int) $character['id_character'],
    'name' => (string) $character['name'],
];

$landSlug = $currentLand['slug'];
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

<h1><?php echo $landName; ?></h1>

<p>Personaggio: <?php echo htmlspecialchars($character['name'], ENT_QUOTES, 'UTF-8'); ?></p>

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
