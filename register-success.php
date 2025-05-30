<?php
// Alleen toegankelijk via URL parameters van succesvolle registratie
$org_id = $_GET['org_id'] ?? '';
$email = $_GET['email'] ?? '';

if (!$org_id) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registratie Succesvol - School Infoscherm</title>
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
        .success-container {
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
        h1 {
            color: #28a745;
            margin-bottom: 20px;
            font-size: 2em;
        }
        .org-id {
            background: #f8f9fa;
            border: 2px solid #007cba;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 1.2em;
            font-weight: bold;
            color: #007cba;
        }
        .steps {
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .steps h3 {
            color: #333;
            margin-top: 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 8px;
            line-height: 1.5;
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
        .email-highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 3px 6px;
            font-weight: bold;
            color: #856404;
        }
        .footer-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✓</div>
        
        <h1>Registratie Succesvol!</h1>
        
        <p><strong>Welkom bij IT4VO School Infoscherm!</strong></p>
        <p>Je organisatie is succesvol geregistreerd.</p>

        <div class="org-id">
            Organisatie ID: <strong><?= htmlspecialchars($org_id) ?></strong>
        </div>

        <div class="steps">
            <h3>📧 Volgende Stappen:</h3>
            <ol>
                <li><strong>Check je email</strong> <?php if($email): ?>(<span class="email-highlight"><?= htmlspecialchars($email) ?></span>)<?php endif; ?></li>
                <li><strong>Klik op de verificatielink</strong> in de email</li>
                <li><strong>Log in</strong> met je nieuwe account</li>
                <li><strong>Voeg je eerste players toe</strong> en begin met slides maken</li>
            </ol>
        </div>

        <div class="buttons">
            <a href="login.php" class="btn btn-primary">Naar Inloggen</a>
            <a href="index.php" class="btn btn-secondary">Terug naar Home</a>
        </div>

        <div class="footer-info">
            <p><strong>Geen email ontvangen?</strong><br>
            Check je spam folder of neem contact op via <a href="mailto:support@it4vo.nl">support@it4vo.nl</a></p>
            
            <p><strong>Hulp nodig?</strong><br>
            Bekijk de <a href="#" target="_blank">handleiding</a> of neem contact met ons op.</p>
        </div>
    </div>

    <script>
        // Auto-refresh indicator als ze te lang wachten
        setTimeout(function() {
            const container = document.querySelector('.success-container');
            const reminder = document.createElement('div');
            reminder.style.cssText = 'background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin-top: 20px;';
            reminder.innerHTML = '<strong>💡 Tip:</strong> Emails kunnen soms 1-2 minuten duren. Check ook je spam folder!';
            container.appendChild(reminder);
        }, 30000); // Na 30 seconden
    </script>
</body>
</html>