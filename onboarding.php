<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';

$pdo = require __DIR__ . '/config/database.php';

$user = $_SESSION['user'];
$errors = [];
$fieldError = '';

$step = (string) ($_GET['step'] ?? 'city');

$allowedSteps = ['city', 'echoes', 'stats_city', 'stats_echoes', 'confirm'];

if (!in_array($step, $allowedSteps, true)) {
    header('Location: /onboarding.php?step=city');
    exit;
}

if (!isset($_SESSION['onboarding']) || !is_array($_SESSION['onboarding'])) {
    $_SESSION['onboarding'] = [];
}

function naraka_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim((string) $slug, '-');

    if ($slug === '') {
        $slug = 'personaggio';
    }

    return $slug;
}

function naraka_get_land(PDO $pdo, string $slug): array
{
    $stmt = $pdo->prepare("
        SELECT id_land, name, slug
        FROM lands
        WHERE slug = :slug
        AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute([
        'slug' => $slug,
    ]);

    $land = $stmt->fetch();

    if (!$land) {
        die('Errore: land non trovata.');
    }

    return $land;
}

function naraka_character_exists_for_user_land(PDO $pdo, int $idUser, int $idLand): bool
{
    $stmt = $pdo->prepare("
        SELECT id_character
        FROM characters
        WHERE id_user = :id_user
        AND id_land = :id_land
        LIMIT 1
    ");

    $stmt->execute([
        'id_user' => $idUser,
        'id_land' => $idLand,
    ]);

    return (bool) $stmt->fetch();
}

function naraka_character_name_exists(PDO $pdo, string $name, int $idLand): bool
{
    $stmt = $pdo->prepare("
        SELECT id_character
        FROM characters
        WHERE LOWER(name) = LOWER(:name)
        AND id_land = :id_land
        LIMIT 1
    ");

    $stmt->execute([
        'name' => $name,
        'id_land' => $idLand,
    ]);

    return (bool) $stmt->fetch();
}

function naraka_get_stats(PDO $pdo, string $groupSlug): array
{
    $stmt = $pdo->prepare("
        SELECT s.id_stat, s.slug, s.name, g.points_total, g.min_value, g.max_value, g.max_stats_at_cap
        FROM stats s
        JOIN stat_groups g ON g.id_stat_group = s.id_stat_group
        WHERE g.slug = :slug
        AND s.is_active = 1
        ORDER BY s.sort_order ASC
    ");

    $stmt->execute([
        'slug' => $groupSlug,
    ]);

    return $stmt->fetchAll();
}

function naraka_validate_name(string $name): string
{
    if ($name === '') {
        return 'Il nome del personaggio è obbligatorio.';
    }

    if (strlen($name) < 3 || strlen($name) > 50) {
        return 'Il nome deve essere lungo da 3 a 50 caratteri.';
    }

    if (!preg_match('/^[\p{L}0-9 _\'-]+$/u', $name)) {
        return 'Il nome può contenere lettere, numeri, spazi, apostrofi e trattini.';
    }

    return '';
}

function naraka_validate_stats(array $submittedStats, array $availableStats): array
{
    if (!$availableStats) {
        return [
            'valid' => false,
            'message' => 'Statistiche non configurate.',
            'values' => [],
        ];
    }

    $pointsTotal = (int) $availableStats[0]['points_total'];
    $minValue = (int) $availableStats[0]['min_value'];
    $maxValue = (int) $availableStats[0]['max_value'];
    $maxStatsAtCap = (int) $availableStats[0]['max_stats_at_cap'];

    $values = [];
    $total = 0;
    $statsAtCap = 0;

    foreach ($availableStats as $stat) {
        $idStat = (int) $stat['id_stat'];
        $value = isset($submittedStats[$idStat]) ? (int) $submittedStats[$idStat] : $minValue;

        if ($value < $minValue || $value > $maxValue) {
            return [
                'valid' => false,
                'message' => 'Distribuzione punti non valida.',
                'values' => [],
            ];
        }

        if ($value === $maxValue) {
            $statsAtCap++;
        }

        $values[$idStat] = $value;
        $total += $value;
    }

    if ($total !== $pointsTotal) {
        return [
            'valid' => false,
            'message' => 'Devi distribuire esattamente ' . $pointsTotal . ' punti.',
            'values' => [],
        ];
    }

    if ($statsAtCap > $maxStatsAtCap) {
        return [
            'valid' => false,
            'message' => 'Puoi avere una sola caratteristica a 5.',
            'values' => [],
        ];
    }

    return [
        'valid' => true,
        'message' => '',
        'values' => $values,
    ];
}

function naraka_render_stats_form(array $stats, string $step, array $savedValues, array $errors): void
{
    $pointsTotal = isset($stats[0]) ? (int) $stats[0]['points_total'] : 18;
    $minValue = isset($stats[0]) ? (int) $stats[0]['min_value'] : 1;
    $maxValue = isset($stats[0]) ? (int) $stats[0]['max_value'] : 5;
    $maxStatsAtCap = isset($stats[0]) ? (int) $stats[0]['max_stats_at_cap'] : 1;

    ?>
    <div class="auth-panel">
        <h1 class="auth-title"><?php echo $step === 'stats_city' ? 'CITY' : 'ECHOES'; ?></h1>
        <p class="auth-subtitle">
            Distribuisci <?php echo $pointsTotal; ?> punti. Ogni caratteristica parte da <?php echo $minValue; ?>.
            Puoi avere una sola caratteristica a <?php echo $maxValue; ?>.
        </p>

        <?php if ($errors) { ?>
            <div class="auth-errors">
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <div class="auth-stats-counter">
            Punti rimasti: <strong id="pointsRemaining">0</strong>
        </div>

        <p id="capWarning" class="auth-field-error" hidden>Puoi avere una sola caratteristica a <?php echo $maxValue; ?>.</p>

        <form method="post" action="/onboarding.php?step=<?php echo htmlspecialchars($step, ENT_QUOTES, 'UTF-8'); ?>" id="statsForm">
            <?php foreach ($stats as $stat) {
                $idStat = (int) $stat['id_stat'];
                $value = isset($savedValues[$idStat]) ? (int) $savedValues[$idStat] : $minValue;
                ?>
                <div class="auth-stat-row">
                    <div>
                        <?php echo htmlspecialchars((string) $stat['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="auth-stat-controls">
                        <button type="button" class="auth-stat-btn minus">−</button>

                        <span class="auth-stat-value" data-value-display="<?php echo $idStat; ?>"><?php echo $value; ?></span>

                        <button type="button" class="auth-stat-btn plus">+</button>

                        <input
                            type="hidden"
                            name="stats[<?php echo $idStat; ?>]"
                            value="<?php echo $value; ?>"
                            class="stat-input"
                            data-id="<?php echo $idStat; ?>"
                            data-min="<?php echo $minValue; ?>"
                            data-max="<?php echo $maxValue; ?>"
                        >
                    </div>
                </div>
            <?php } ?>

            <button type="submit" class="auth-button" id="continueBtn">Continua</button>
        </form>
    </div>

    <script>
        const pointsTotal = <?php echo $pointsTotal; ?>;
        const maxValue = <?php echo $maxValue; ?>;
        const maxStatsAtCap = <?php echo $maxStatsAtCap; ?>;

        const form = document.getElementById('statsForm');
        const inputs = Array.from(document.querySelectorAll('.stat-input'));
        const pointsRemaining = document.getElementById('pointsRemaining');
        const continueBtn = document.getElementById('continueBtn');
        const capWarning = document.getElementById('capWarning');

        function refreshStats() {
            let total = 0;
            let atCap = 0;

            inputs.forEach((input) => {
                const value = parseInt(input.value, 10);
                const id = input.dataset.id;

                total += value;

                if (value === maxValue) {
                    atCap++;
                }

                const display = document.querySelector('[data-value-display="' + id + '"]');

                if (display) {
                    display.textContent = value;
                }
            });

            const remaining = pointsTotal - total;
            pointsRemaining.textContent = remaining;

            const isValid = remaining === 0 && atCap <= maxStatsAtCap;
            continueBtn.disabled = !isValid;
            capWarning.hidden = atCap <= maxStatsAtCap;

            document.querySelectorAll('.auth-stat-row').forEach((row) => {
                const input = row.querySelector('.stat-input');
                const minus = row.querySelector('.minus');
                const plus = row.querySelector('.plus');

                const value = parseInt(input.value, 10);
                const min = parseInt(input.dataset.min, 10);
                const max = parseInt(input.dataset.max, 10);

                minus.disabled = value <= min;

                const wouldExceedPoints = remaining <= 0;
                const wouldBreakCap = value === max - 1 && atCap >= maxStatsAtCap;

                plus.disabled = value >= max || wouldExceedPoints || wouldBreakCap;
            });
        }

        document.querySelectorAll('.auth-stat-row').forEach((row) => {
            const input = row.querySelector('.stat-input');
            const minus = row.querySelector('.minus');
            const plus = row.querySelector('.plus');

            minus.addEventListener('click', () => {
                const min = parseInt(input.dataset.min, 10);
                const value = parseInt(input.value, 10);

                if (value > min) {
                    input.value = String(value - 1);
                    refreshStats();
                }
            });

            plus.addEventListener('click', () => {
                const max = parseInt(input.dataset.max, 10);
                const value = parseInt(input.value, 10);

                if (value < max) {
                    input.value = String(value + 1);
                    refreshStats();
                }
            });
        });

        form.addEventListener('submit', (event) => {
            if (continueBtn.disabled) {
                event.preventDefault();
            }
        });

        refreshStats();
    </script>
    <?php
}

$cityLand = naraka_get_land($pdo, 'city');
$echoesLand = naraka_get_land($pdo, 'echoes');

$idCity = (int) $cityLand['id_land'];
$idEchoes = (int) $echoesLand['id_land'];

$hasCity = naraka_character_exists_for_user_land($pdo, (int) $user['id_user'], $idCity);
$hasEchoes = naraka_character_exists_for_user_land($pdo, (int) $user['id_user'], $idEchoes);

if ($hasCity && $hasEchoes) {
    $_SESSION['current_land'] = 'city';
    unset($_SESSION['onboarding']);
    header('Location: /index.php');
    exit;
}

$statsCity = naraka_get_stats($pdo, 'city');
$statsEchoes = naraka_get_stats($pdo, 'echoes');

if ($step === 'echoes' && !isset($_SESSION['onboarding']['city']) && !$hasCity) {
    header('Location: /onboarding.php?step=city');
    exit;
}

if ($step === 'stats_city' && !isset($_SESSION['onboarding']['echoes']) && !$hasEchoes) {
    header('Location: /onboarding.php?step=echoes');
    exit;
}

if ($step === 'stats_echoes' && !isset($_SESSION['onboarding']['stats_city']) && !$hasCity) {
    header('Location: /onboarding.php?step=stats_city');
    exit;
}

if ($step === 'confirm') {
    if (!isset($_SESSION['onboarding']['city']) && !$hasCity) {
        header('Location: /onboarding.php?step=city');
        exit;
    }

    if (!isset($_SESSION['onboarding']['echoes']) && !$hasEchoes) {
        header('Location: /onboarding.php?step=echoes');
        exit;
    }

    if (!isset($_SESSION['onboarding']['stats_city']) && !$hasCity) {
        header('Location: /onboarding.php?step=stats_city');
        exit;
    }

    if (!isset($_SESSION['onboarding']['stats_echoes']) && !$hasEchoes) {
        header('Location: /onboarding.php?step=stats_echoes');
        exit;
    }
}

if (isset($_GET['change']) && $_GET['change'] === 'city') {
    unset($_SESSION['onboarding']['city'], $_SESSION['onboarding']['stats_city']);
    header('Location: /onboarding.php?step=city');
    exit;
}

if (isset($_GET['change']) && $_GET['change'] === 'echoes') {
    unset($_SESSION['onboarding']['echoes'], $_SESSION['onboarding']['stats_echoes']);
    header('Location: /onboarding.php?step=echoes');
    exit;
}

if (isset($_GET['change']) && $_GET['change'] === 'stats_city') {
    unset($_SESSION['onboarding']['stats_city']);
    header('Location: /onboarding.php?step=stats_city');
    exit;
}

if (isset($_GET['change']) && $_GET['change'] === 'stats_echoes') {
    unset($_SESSION['onboarding']['stats_echoes']);
    header('Location: /onboarding.php?step=stats_echoes');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'city') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $fieldError = naraka_validate_name($name);

        if ($fieldError === '' && naraka_character_name_exists($pdo, $name, $idCity)) {
            $fieldError = 'Questo nome è già occupato.';
        }

        if ($fieldError === '') {
            $_SESSION['onboarding']['city'] = $name;
            header('Location: /onboarding.php?step=echoes');
            exit;
        }
    }

    if ($step === 'echoes') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $fieldError = naraka_validate_name($name);

        if ($fieldError === '' && naraka_character_name_exists($pdo, $name, $idEchoes)) {
            $fieldError = 'Questo nome è già occupato.';
        }

        if ($fieldError === '') {
            $_SESSION['onboarding']['echoes'] = $name;
            header('Location: /onboarding.php?step=stats_city');
            exit;
        }
    }

    if ($step === 'stats_city') {
        $result = naraka_validate_stats((array) ($_POST['stats'] ?? []), $statsCity);

        if (!$result['valid']) {
            $errors[] = $result['message'];
        } else {
            $_SESSION['onboarding']['stats_city'] = $result['values'];
            header('Location: /onboarding.php?step=stats_echoes');
            exit;
        }
    }

    if ($step === 'stats_echoes') {
        $result = naraka_validate_stats((array) ($_POST['stats'] ?? []), $statsEchoes);

        if (!$result['valid']) {
            $errors[] = $result['message'];
        } else {
            $_SESSION['onboarding']['stats_echoes'] = $result['values'];
            header('Location: /onboarding.php?step=confirm');
            exit;
        }
    }

    if ($step === 'confirm') {
        $cityName = (string) ($_SESSION['onboarding']['city'] ?? '');
        $echoesName = (string) ($_SESSION['onboarding']['echoes'] ?? '');

        try {
            $pdo->beginTransaction();

            if (!$hasCity) {
                $stmt = $pdo->prepare("
                    INSERT INTO characters (id_user, id_land, name, slug)
                    VALUES (:id_user, :id_land, :name, :slug)
                ");

                $stmt->execute([
                    'id_user' => (int) $user['id_user'],
                    'id_land' => $idCity,
                    'name' => $cityName,
                    'slug' => naraka_slug($cityName),
                ]);

                $idCityCharacter = (int) $pdo->lastInsertId();

                $stmtStats = $pdo->prepare("
                    INSERT INTO character_stats (id_character, id_stat, value)
                    VALUES (:id_character, :id_stat, :value)
                ");

                foreach ($_SESSION['onboarding']['stats_city'] as $idStat => $value) {
                    $stmtStats->execute([
                        'id_character' => $idCityCharacter,
                        'id_stat' => (int) $idStat,
                        'value' => (int) $value,
                    ]);
                }
            }

            if (!$hasEchoes) {
                $stmt = $pdo->prepare("
                    INSERT INTO characters (id_user, id_land, name, slug)
                    VALUES (:id_user, :id_land, :name, :slug)
                ");

                $stmt->execute([
                    'id_user' => (int) $user['id_user'],
                    'id_land' => $idEchoes,
                    'name' => $echoesName,
                    'slug' => naraka_slug($echoesName),
                ]);

                $idEchoesCharacter = (int) $pdo->lastInsertId();

                $stmtStats = $pdo->prepare("
                    INSERT INTO character_stats (id_character, id_stat, value)
                    VALUES (:id_character, :id_stat, :value)
                ");

                foreach ($_SESSION['onboarding']['stats_echoes'] as $idStat => $value) {
                    $stmtStats->execute([
                        'id_character' => $idEchoesCharacter,
                        'id_stat' => (int) $idStat,
                        'value' => (int) $value,
                    ]);
                }
            }

            $pdo->commit();

            unset($_SESSION['onboarding'], $_SESSION['character']);
            $_SESSION['current_land'] = 'city';

            header('Location: /index.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Errore durante la creazione dei personaggi. Controlla che i nomi non siano già stati usati.';
        }
    }
}

$cityValue = (string) ($_SESSION['onboarding']['city'] ?? '');
$echoesValue = (string) ($_SESSION['onboarding']['echoes'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($step === 'city' || $step === 'echoes')) {
    $postedName = trim((string) ($_POST['name'] ?? ''));

    if ($step === 'city') {
        $cityValue = $postedName;
    }

    if ($step === 'echoes') {
        $echoesValue = $postedName;
    }
}

$statsCityValues = (array) ($_SESSION['onboarding']['stats_city'] ?? []);
$statsEchoesValues = (array) ($_SESSION['onboarding']['stats_echoes'] ?? []);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creazione personaggi - Naraka</title>
    <link rel="stylesheet" href="/themes/auth.css">
</head>
<body>

<?php if ($step === 'city') { ?>

    <div class="auth-panel">
        <h1 class="auth-title">CITY</h1>
        <p class="auth-subtitle">Scegli il nome del personaggio che userai nella land City.</p>

        <form method="post" action="/onboarding.php?step=city">
            <div class="auth-field">
                <label for="name">Nome personaggio City</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8'); ?>" required>

                <?php if ($fieldError !== '') { ?>
                    <p class="auth-field-error"><?php echo htmlspecialchars($fieldError, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
            </div>

            <button type="submit" class="auth-button">Continua</button>
        </form>

        <div class="auth-links">
            <a href="/logout.php">Logout</a>
        </div>
    </div>

<?php } elseif ($step === 'echoes') { ?>

    <div class="auth-panel">
        <h1 class="auth-title">ECHOES</h1>
        <p class="auth-subtitle">Scegli il nome del personaggio che userai nella land Echoes.</p>

        <form method="post" action="/onboarding.php?step=echoes">
            <div class="auth-field">
                <label for="name">Nome personaggio Echoes</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($echoesValue, ENT_QUOTES, 'UTF-8'); ?>" required>

                <?php if ($fieldError !== '') { ?>
                    <p class="auth-field-error"><?php echo htmlspecialchars($fieldError, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
            </div>

            <button type="submit" class="auth-button">Continua</button>
        </form>

        <div class="auth-links">
            <a href="/onboarding.php?change=city">Cambia nome City</a>
            <a href="/logout.php">Logout</a>
        </div>
    </div>

<?php } elseif ($step === 'stats_city') { ?>

    <?php naraka_render_stats_form($statsCity, 'stats_city', $statsCityValues, $errors); ?>

<?php } elseif ($step === 'stats_echoes') { ?>

    <?php naraka_render_stats_form($statsEchoes, 'stats_echoes', $statsEchoesValues, $errors); ?>

<?php } elseif ($step === 'confirm') { ?>

    <div class="auth-panel">
        <h1 class="auth-title">CONFIRM</h1>
        <p class="auth-subtitle">Controlla nomi e caratteristiche. Dopo la conferma entrerai direttamente in City.</p>

        <?php if ($errors) { ?>
            <div class="auth-errors">
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <div class="auth-summary">
            <div class="auth-summary-card">
                <h2>City</h2>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8'); ?></p>

                <ul>
                    <?php foreach ($statsCity as $stat) {
                        $idStat = (int) $stat['id_stat'];
                        $value = (int) ($_SESSION['onboarding']['stats_city'][$idStat] ?? 1);
                        ?>
                        <li><?php echo htmlspecialchars((string) $stat['name'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $value; ?></li>
                    <?php } ?>
                </ul>

                <div class="auth-links">
                    <a href="/onboarding.php?change=city">Cambia nome City</a>
                    <a href="/onboarding.php?change=stats_city">Cambia statistiche City</a>
                </div>
            </div>

            <div class="auth-summary-card">
                <h2>Echoes</h2>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($echoesValue, ENT_QUOTES, 'UTF-8'); ?></p>

                <ul>
                    <?php foreach ($statsEchoes as $stat) {
                        $idStat = (int) $stat['id_stat'];
                        $value = (int) ($_SESSION['onboarding']['stats_echoes'][$idStat] ?? 1);
                        ?>
                        <li><?php echo htmlspecialchars((string) $stat['name'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $value; ?></li>
                    <?php } ?>
                </ul>

                <div class="auth-links">
                    <a href="/onboarding.php?change=echoes">Cambia nome Echoes</a>
                    <a href="/onboarding.php?change=stats_echoes">Cambia statistiche Echoes</a>
                </div>
            </div>
        </div>

        <form method="post" action="/onboarding.php?step=confirm">
            <button type="submit" class="auth-button">Conferma definitiva</button>
        </form>

        <div class="auth-links">
            <a href="/logout.php">Logout</a>
        </div>
    </div>

<?php } ?>

</body>
</html>

<?php
// by LaEmiX