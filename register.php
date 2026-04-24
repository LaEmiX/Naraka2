<?php

declare(strict_types=1);

session_start();

$pdo = require __DIR__ . '/config/database.php';

$errors = [];
$success = false;

$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '') {
        $errors[] = 'Il nome utente è obbligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Il nome utente deve contenere solo lettere, numeri e underscore, da 3 a 50 caratteri.';
    }

    if ($email === '') {
        $errors[] = 'L’email è obbligatoria.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L’email non è valida.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'La password deve contenere almeno 8 caratteri.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Le password non coincidono.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            SELECT id_user
            FROM users
            WHERE username = :username
            OR email = :email
            LIMIT 1
        ");

        $stmt->execute([
            'username' => $username,
            'email' => $email,
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'Nome utente o email già registrati.';
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash)
            VALUES (:username, :email, :password_hash)
        ");

        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        $success = true;
        $username = '';
        $email = '';
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Naraka</title>
</head>
<body>

<h1>Registrazione Naraka</h1>

<?php if ($success) { ?>
    <p>Registrazione completata. Ora puoi accedere.</p>
    <p><a href="/login.php">Vai al login</a></p>
<?php } ?>

<?php if ($errors) { ?>
    <ul>
        <?php foreach ($errors as $error) { ?>
            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php } ?>
    </ul>
<?php } ?>

<form method="post" action="/register.php">
    <p>
        <label for="username">Nome utente</label><br>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" required>
    </p>

    <p>
        <label for="password_confirm">Conferma password</label><br>
        <input type="password" id="password_confirm" name="password_confirm" required>
    </p>

    <button type="submit">Registrati</button>
</form>

<p><a href="/index.php">Torna alla home</a></p>

</body>
</html>

<?php
// by LaEmiX