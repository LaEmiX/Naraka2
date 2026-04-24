<?php

declare(strict_types=1);

session_start();

$pdo = require __DIR__ . '/config/database.php';

$errors = [];
$login = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($login === '') {
            $errors[] = 'Inserisci username o email.';
        }

        if ($password === '') {
            $errors[] = 'Inserisci la password.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare("
                SELECT id_user, username, email, password_hash, role, is_active
                FROM users
                WHERE username = :login_username
                OR email = :login_email
                LIMIT 1
            ");

            $stmt->execute([
                'login_username' => $login,
                'login_email' => $login,
            ]);

            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'Credenziali non valide.';
            } elseif (!password_verify($password, (string) $user['password_hash'])) {
                $errors[] = 'Credenziali non valide.';
            } elseif ((int) $user['is_active'] !== 1) {
                $errors[] = 'Account disattivato.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id_user' => (int) $user['id_user'],
                    'username' => (string) $user['username'],
                    'email' => (string) $user['email'],
                    'role' => (string) $user['role'],
                ];

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET last_login_at = CURRENT_TIMESTAMP
                    WHERE id_user = :id_user
                ");

                $stmt->execute([
                    'id_user' => (int) $user['id_user'],
                ]);

                header('Location: /index.php');
                exit;
            }
        }
    }
} catch (Throwable $e) {
    $errors[] = 'Errore interno durante il login: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Naraka</title>
</head>
<body>

<h1>Login Naraka</h1>

<?php if ($errors) { ?>
    <ul>
        <?php foreach ($errors as $error) { ?>
            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php } ?>
    </ul>
<?php } ?>

<form method="post" action="/login.php">
    <p>
        <label for="login">Username o email</label><br>
        <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" required>
    </p>

    <button type="submit">Accedi</button>
</form>

<p><a href="/register.php">Non hai un account? Registrati</a></p>
<p><a href="/index.php">Torna alla home</a></p>

</body>
</html>

<?php
// by LaEmiX