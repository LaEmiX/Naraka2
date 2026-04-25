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

$currentLand = (string) ($_SESSION['current_land'] ?? 'city');

$stmt = $pdo->prepare("
    SELECT id_land, slug, name
    FROM lands
    WHERE slug = :slug
    LIMIT 1
");

$stmt->execute([
    'slug' => $currentLand,
]);

$land = $stmt->fetch();

if (!$land) {
    $_SESSION['current_land'] = 'city';
    header('Location: /index.php');
    exit;
}

$idLand = (int) $land['id_land'];
$landSlug = (string) $land['slug'];

/* ================= CONTROLLO PERSONAGGI ================= */

$stmt = $pdo->prepare("
    SELECT l.slug
    FROM characters c
    JOIN lands l ON l.id_land = c.id_land
    WHERE c.id_user = :id_user
");

$stmt->execute([
    'id_user' => (int) $user['id_user'],
]);

$userLands = $stmt->fetchAll(PDO::FETCH_COLUMN);

$hasCity = in_array('city', $userLands, true);
$hasEchoes = in_array('echoes', $userLands, true);

/* ================= REDIRECT ONBOARDING ================= */

if (!$hasCity) {
    header('Location: /onboarding_city.php');
    exit;
}

if (!$hasEchoes) {
    header('Location: /onboarding_echoes.php');
    exit;
}

/* ================= PERSONAGGIO ATTIVO ================= */

$stmt = $pdo->prepare("
    SELECT id_character, name
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");

$stmt->execute([
    'id_user' => (int) $user['id_user'],
    'id_land' => $idLand,
]);

$character = $stmt->fetch();

if (!$character) {
    $_SESSION['current_land'] = 'city';
    header('Location: /index.php');
    exit;
}

/* ================= SWITCH LINK ================= */

if ($landSlug === 'city') {
    $switchLandSlug = 'echoes';
    $switchLandLabel = 'Vai a Echoes';
} else {
    $switchLandSlug = 'city';
    $switchLandLabel = 'Torna a City';
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
            Land: <strong><?php echo htmlspecialchars((string) $land['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </p>

        <p class="auth-subtitle">
            Personaggio: <strong><?php echo htmlspecialchars((string) $character['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </p>

        <div class="auth-links">
            <a href="/switch_land.php?land=<?php echo htmlspecialchars($switchLandSlug, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($switchLandLabel, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a href="/logout.php">Logout</a>
        </div>

    </div>

</div>

</body>
</html>

<?php
// by LaEmiX
