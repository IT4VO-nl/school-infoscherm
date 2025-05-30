<?php
require_once 'db_config.php';

$pdo = getDbConnection();
$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (!$token) {
    $error = 'Geen verificatie token gevonden.';
} else {
    try {
        $pdo->beginTransaction();
        
        // Zoek admin met deze token die nog niet verlopen is
        $stmt = $pdo->prepare('
            SELECT a.id, a.organisation_id, a.name, a.email,
                   o.name as org_name, o.organisation_id as org_slug
            FROM admins a
            JOIN organisations o ON o.id = a.organisation_id
            WHERE a.verification_token = :token 
            AND a.verification_expires > NOW()
            AND a.is_verified = 0
            LIMIT 1
        ');
        $stmt->execute([':token' => $token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            $error = 'Verificatie link is ongeldig of verlopen.';
        } else {
            // Activeer admin account
            $stmt = $pdo->prepare('
                UPDATE admins 
                SET is_verified = 1, 
                    verification_token = NULL, 
                    verification_expires = NULL
                WHERE id = :admin_id
            ');
            $stmt->execute([':admin_id' => $admin['id']]);
            
            // Activeer organisatie account
            $stmt = $pdo->prepare('
                UPDATE organisations 
                SET is_verified = 1,
                    verification_token = NULL,
                    verification_expires = NULL
                WHERE id = :org_id
            ');
            $stmt->execute([':org_id' => $admin['organisation_id']]);
            
            $pdo->commit();
            $success = true;
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Er is een fout opgetreden bij de verificatie.';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $success ? 'Account Geverifieerd' : 'Verificatie Fout' ?> - School Infoscherm</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #007cba 0%, #005a8b 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verify-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
            color: white;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
            color: white;
        }
        
        h1.success { color: #28a745; }
        h1.error { color: #dc3545; }
        
        .org-info {
            background: #f8f9fa;
            border: 2px solid #007cba;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .org-info h3 {
            color: #007cba;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .org-info p {
            margin: 8px 0;
            color: #333;
        }
        
        .org-info .org-id {
            font-family: monospace;
            font-weight: bold;
            color: #007cba;
            font-size: 1.1em;
        }
        
        .buttons {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 5px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a8b;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .next-steps {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: left;
        }
        
        .next-steps h3 {
            margin-top: 0;
            color: #155724;
        }
        
        .next-steps ol {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <?php if ($success): ?>
            <div class="success-icon">✓</div>
            <h1 class="success">Account Geverifieerd!</h1>
            
            <p><strong>Welkom bij IT4VO School Infoscherm!</strong></p>
            <p>Je account is succesvol geverifieerd en geactiveerd.</p>
            
            <?php if (isset($admin)): ?>
            <div class="org-info">
                <h3>📋 Account Details</h3>
                <p><strong>Organisatie:</strong> <?= htmlspecialchars($admin['org_name']) ?></p>
                <p><strong>Organisatie ID:</strong> <span class="org-id"><?= htmlspecialchars($admin['org_slug']) ?></span></p>
                <p><strong>Admin:</strong> <?= htmlspecialchars($admin['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?></p>
            </div>
            <?php endif; ?>
            
            <div class="next-steps">
                <h3>🚀 Volgende Stappen</h3>
                <ol>
                    <li><strong>Log in</strong> met je email en wachtwoord</li>
                    <li><strong>Ga naar je dashboard</strong> om slides te maken</li>
                    <li><strong>Claim je eerste player</strong> door naar de player URL te gaan</li>
                    <li><strong>Begin met content</strong> - voeg nieuws, afwezigheid of roosterwijzigingen toe</li>
                </ol>
            </div>
            
            <div class="buttons">
                <a href="login.php" class="btn btn-primary">🔐 Nu Inloggen</a>
                <a href="index.php" class="btn btn-secondary">🏠 Naar Home</a>
            </div>
            
        <?php else: ?>
            <div class="error-icon">✕</div>
            <h1 class="error">Verificatie Mislukt</h1>
            
            <p><strong>Er is een probleem opgetreden:</strong></p>
            <p style="color: #dc3545; font-size: 1.1em;"><?= htmlspecialchars($error) ?></p>
            
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: left;">
                <h3>🔧 Mogelijke Oplossingen</h3>
                <ul>
                    <li><strong>Link verlopen:</strong> Verificatie links zijn 24 uur geldig</li>
                    <li><strong>Al geverifieerd:</strong> Probeer in te loggen</li>
                    <li><strong>Verkeerde link:</strong> Check of de volledige URL correct is</li>
                    <li><strong>Hulp nodig:</strong> Neem contact op via WhatsApp</li>
                </ul>
            </div>
            
            <div class="buttons">
                <a href="login.php" class="btn btn-primary">🔐 Probeer In te Loggen</a>
                <a href="register_org.php" class="btn btn-secondary">📝 Opnieuw Registreren</a>
                <a href="https://wa.me/31624645787" class="btn btn-secondary" target="_blank">💬 WhatsApp Hulp</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>