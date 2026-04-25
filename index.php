<?php

declare(strict_types=1);

session_start();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['user'])) {
    header('Location: /app.php');
    exit;
}

$pdo = require __DIR__ . '/config/database.php';

$user = $_SESSION['user'];

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
$landCssFile = $landSlug === 'echoes' ? 'echoes.css' : 'city.css';

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

if (!$hasCity) {
    header('Location: /onboarding_city.php');
    exit;
}

if (!$hasEchoes) {
    header('Location: /onboarding_echoes.php');
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
    'id_land' => $idLand,
]);

$character = $stmt->fetch();

if (!$character) {
    $_SESSION['current_land'] = 'city';
    header('Location: /index.php');
    exit;
}

$locations = [
    'piazza' => 'Piazza Centrale',
    'bar' => 'Bar',
    'vicolo' => 'Vicolo',
];

if (!isset($_SESSION['active_location']) || !is_array($_SESSION['active_location'])) {
    $_SESSION['active_location'] = [];
}

if (isset($_GET['map']) && $_GET['map'] === '1') {
    unset($_SESSION['active_location'][$landSlug]);
    header('Location: /index.php');
    exit;
}

$requestedLocation = isset($_GET['location']) ? (string) $_GET['location'] : '';

if ($requestedLocation !== '' && array_key_exists($requestedLocation, $locations)) {
    $_SESSION['active_location'][$landSlug] = $requestedLocation;
}

$savedLocation = isset($_SESSION['active_location'][$landSlug])
    ? (string) $_SESSION['active_location'][$landSlug]
    : '';

$activeLocationKey = array_key_exists($savedLocation, $locations) ? $savedLocation : null;
$activeLocationName = $activeLocationKey !== null ? $locations[$activeLocationKey] : '';

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
<link rel="stylesheet" href="/themes/<?php echo e($landCssFile); ?>?v=land-02">
</head>
<body>

<div class="game-shell">

    <header class="game-topbar">

        <div class="game-brand">
            <span class="game-brand-main">NARAKA</span>
            <span class="game-brand-sub"><?php echo e((string) $land['name']); ?></span>
        </div>

        <nav class="game-nav" aria-label="Menu principale">
            <a class="game-nav-link" href="/switch_land.php?land=<?php echo e($switchLandSlug); ?>">
                <?php echo e($switchLandLabel); ?>
            </a>
            <a class="game-nav-link game-nav-link-danger" href="/logout.php">Logout</a>
        </nav>

    </header>

    <main class="game-layout">

        <section class="game-main-area">

            <?php if ($activeLocationKey === null) { ?>

                <div class="game-map-panel">

                    <div class="game-section-heading">
                        <h1>Mappa</h1>
                        <p>Seleziona una location per entrare nella chat.</p>
                    </div>

                    <div class="game-map">

                        <?php foreach ($locations as $locationKey => $locationName) { ?>
                            <a class="game-location-card" href="/index.php?location=<?php echo e($locationKey); ?>">
                                <span class="game-location-marker"></span>
                                <span class="game-location-name"><?php echo e($locationName); ?></span>
                                <span class="game-location-enter">Entra</span>
                            </a>
                        <?php } ?>

                    </div>

                </div>

            <?php } else { ?>

                <div class="game-chat-panel">

                    <div class="game-section-heading game-chat-heading">
                        <div>
                            <h1><?php echo e($activeLocationName); ?></h1>
                            <p>Chat location — placeholder fase 1.</p>
                        </div>

                        <a class="game-back-map" href="/index.php?map=1">Torna alla mappa</a>
                    </div>

                    <div class="game-chat-box" aria-label="Messaggi chat">

                        <div class="game-message game-message-system">
                            <span class="game-message-author">Sistema</span>
                            <p>Sei entrato in <?php echo e($activeLocationName); ?>.</p>
                        </div>

                        <div class="game-message">
                            <span class="game-message-author"><?php echo e((string) $character['name']); ?></span>
                            <p>La chat è pronta. Il backend messaggi verrà collegato nella prossima fase.</p>
                        </div>

                    </div>

                    <form class="game-chat-form" action="/index.php?location=<?php echo e($activeLocationKey); ?>" method="post">

                        <label class="game-chat-label" for="chat_message">Messaggio</label>

                        <textarea
                            id="chat_message"
                            name="chat_message"
                            class="game-chat-textarea"
                            rows="4"
                            placeholder="Scrivi il tuo messaggio..."
                        ></textarea>

                        <button class="game-chat-submit" type="button">Invia</button>

                    </form>

                </div>

            <?php } ?>

        </section>

        <aside class="game-sidebar">

            <section class="game-side-card">
                <h2>Personaggio</h2>

                <div class="game-side-row">
                    <span>Nome</span>
                    <strong><?php echo e((string) $character['name']); ?></strong>
                </div>

                <div class="game-side-row">
                    <span>Land</span>
                    <strong><?php echo e((string) $land['name']); ?></strong>
                </div>
            </section>

            <section class="game-side-card">
                <h2>Presenti</h2>

                <div class="game-present-placeholder">
                    Nessun sistema presenti collegato.
                </div>
            </section>

        </aside>

    </main>

</div>

</body>
</html>

<?php
// by LaEmiX