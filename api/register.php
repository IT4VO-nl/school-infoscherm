<?php
require_once '../db_config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Validatie - alle velden verplicht zoals gewenst
$required = ['org_name', 'org_phone', 'org_email', 'org_address', 
             'admin_name', 'admin_phone', 'admin_email', 'admin_password'];

foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Field {$field} is required"]);
        exit;
    }
}

// Geen free mail providers
$email_domain = strtolower(substr(strrchr($data['org_email'], '@'), 1));
$blocked_domains = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com'];
if (in_array($email_domain, $blocked_domains)) {
    http_response_code(400);
    echo json_encode(['error' => 'Business email address required']);
    exit;
}

function generateOrgId() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();
    
    // Genereer unieke organisation_id (4-hex)
    $org_slug = generateOrgId();
    while (true) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM organisations WHERE organisation_id = :slug');
        $stmt->execute([':slug' => $org_slug]);
        if ($stmt->fetchColumn() == 0) break;
        $org_slug = generateOrgId();
    }
    
    // Maak organisatie aan
    $org_verification_token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('
        INSERT INTO organisations 
        (organisation_id, name, phone, email, address, brin_code, 
         verification_token, verification_expires)
        VALUES 
        (:org_id, :name, :phone, :email, :address, :brin, :token, 
         DATE_ADD(NOW(), INTERVAL 24 HOUR))
    ');
    $stmt->execute([
        ':org_id' => $org_slug,
        ':name' => $data['org_name'],
        ':phone' => $data['org_phone'],
        ':email' => $data['org_email'],
        ':address' => $data['org_address'],
        ':brin' => $data['brin_code'] ?? null,
        ':token' => $org_verification_token
    ]);
    $organisation_id = $pdo->lastInsertId();
    
    // Maak admin account aan
    $admin_verification_token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('
        INSERT INTO admins 
        (organisation_id, name, phone, email, password_hash, 
         verification_token, verification_expires)
        VALUES 
        (:org_id, :name, :phone, :email, :hash, :token, 
         DATE_ADD(NOW(), INTERVAL 24 HOUR))
    ');
    $stmt->execute([
        ':org_id' => $organisation_id,
        ':name' => $data['admin_name'],
        ':phone' => $data['admin_phone'],
        ':email' => $data['admin_email'],
        ':hash' => password_hash($data['admin_password'], PASSWORD_DEFAULT),
        ':token' => $admin_verification_token
    ]);
    
    // Maak standaard player group aan
    $stmt = $pdo->prepare('
        INSERT INTO player_groups (organisation_id, name, description, is_default)
        VALUES (:org_id, :name, :desc, 1)
    ');
    $stmt->execute([
        ':org_id' => $organisation_id,
        ':name' => $data['org_name'],
        ':desc' => 'Alle schermen van ' . $data['org_name']
    ]);
    
    $pdo->commit();
    
    // Stuur verificatie email
    $verify_url = "<your verification URL here>" . $admin_verification_token;
    
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = '<your mailserver here>';
    $mail->SMTPAuth = true;
    $mail->Username = '<your email address here>';
    $mail->Password = '<your password here>';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = <your mailserver port here>;
    
    $mail->setFrom('php_mailer@it4vo.nl', 'IT4VO School Infoscherm');
    $mail->addAddress($data['admin_email'], $data['admin_name']);
    
    $mail->isHTML(false);
    $mail->Subject = 'Bevestig je School Infoscherm account';
    $mail->Body = "Hallo {$data['admin_name']},\n\n";
    $mail->Body .= "Welkom bij IT4VO School Infoscherm!\n\n";
    $mail->Body .= "Klik op onderstaande link om je account te activeren:\n";
    $mail->Body .= $verify_url . "\n\n";
    $mail->Body .= "Je organisatie ID is: {$org_slug}\n\n";
    $mail->Body .= "Met vriendelijke groet,\nIT4VO Team";
    
    $mail->send();
    
    header('Location: register_success.php?org_id=' . urlencode($org_slug) . '&email=' . urlencode($data['admin_email']));
    exit;
    
} catch (Exception $e) {
    $pdo->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>