<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db_config.php';
$pdo = getDbConnection();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare('
            SELECT a.id, a.organisation_id, a.password_hash, a.is_verified,
                   o.is_verified as org_verified, o.name as org_name
            FROM admins a
            JOIN organisations o ON o.id = a.organisation_id 
            WHERE a.email = :email
            LIMIT 1
        ');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_verified']) {
                $error = 'Je account is nog niet geverifieerd. Check je email.';
            } else {
                // Inloggen gelukt
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['organisation_id'] = $user['organisation_id'];
                $_SESSION['org_name'] = $user['org_name'];
                $_SESSION['org_verified'] = $user['org_verified'];
                
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Ongeldig e-mail of wachtwoord';
        }
    } else {
        $error = 'Vul alle velden in';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - School Infoscherm</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
    input { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px; }
    button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
    button:hover { background: #005a8b; }
    .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .links { text-align: center; margin-top: 20px; }
    .links a { color: #007cba; text-decoration: none; margin: 0 10px; }
  </style>
</head>
<body>
  <h1>Inloggen</h1>
  
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  
  <form method="post">
    <label for="email">E-mailadres:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    
    <label for="password">Wachtwoord:</label>
    <input type="password" id="password" name="password" required>
    
    <button type="submit">Inloggen</button>
  </form>
  
  <div class="links">
    <a href="index.php">← Terug naar home</a> | 
    <a href="register_org.php">Organisatie registreren</a>
  </div>
</body>
</html>