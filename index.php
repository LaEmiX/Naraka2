<?php

declare(strict_types=1);

session_start();

/* ================= BLOCCO ACCESSO ================= */

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$pdo = require __DIR__ . '/config/database.php';

$user = $_SESSION['user'];

/* ================= RECUPERO LAND ================= */

$currentLand = $_SESSION['current_land'] ?? 'city';

$stmt = $pdo->prepare("
    SELECT id_land, slug, name
    FROM lands
    WHERE slug = :slug
    LIMIT 1
");

$stmt->execute(['slug' => $currentLand]);

$land = $stmt->fetch();

if (!$land) {
    $_SESSION['current_land'] = 'city';
    header('Location: /index.php');
    exit;
}

$idLand = (int)$land['id_land'];

/* ================= CONTROLLO PERSONAGGI ================= */

$stmt = $pdo->prepare("
    SELECT id_land
    FROM characters
    WHERE id_user = :id_user
");

$stmt->execute([
    'id_user' => (int)$user['id_user']
]);

$lands = $stmt->fetchAll(PDO::FETCH_COLUMN);

$hasCity = in_array(1, $lands);
$hasEcho = in_array(2, $lands);

/* ================= REDIRECT CORRETTO ================= */

if (!$hasCity) {
    header('Location: /onboarding_city.php');
    exit;
}

if (!$hasEcho) {
    header('Location: /onboarding_echoes.php');
    exit;
}

/* ================= PERSONAGGIO ================= */

$stmt = $pdo->prepare("
    SELECT id_character, name
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");

$stmt->execute([
    'id_user' => (int)$user['id_user'],
    'id_land' => $idLand
]);

$character = $stmt->fetch();

if (!$character) {
    $_SESSION['current_land'] = 'city';
    header('Location: /index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Naraka</title>
<link rel="stylesheet" href="/themes/auth.css">
</head>
<body>

<div class="auth-page">

    <div class="auth-panel">

        <h1 class="auth-title">NARAKA</h1>

        <p class="auth-subtitle">
            Land: <strong><?= htmlspecialchars($land['name']) ?></strong>
        </p>

        <p class="auth-subtitle">
            Personaggio: <strong><?= htmlspecialchars($character['name']) ?></strong>
        </p>

        <div class="auth-links">
            <a href="/switch_land.php">Cambia Land</a>
            <a href="/logout.php">Logout</a>
        </div>

    </div>

</div>

</body>
</html>

<?php
// by LaEmiX