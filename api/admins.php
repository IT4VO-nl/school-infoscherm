<?php
session_start();
require_once '../db_config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Auth check
if (empty($_SESSION['admin_id']) || empty($_SESSION['organisation_id'])) {
    respond(['error' => 'Authentication required'], 401);
}

$org_id = $_SESSION['organisation_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lijst alle admins van deze organisatie
        $stmt = $pdo->prepare('
            SELECT id, name, phone, email, is_verified, created_at
            FROM admins 
            WHERE organisation_id = :org_id 
            ORDER BY name
        ');
        $stmt->execute([':org_id' => $org_id]);
        respond($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        // Create/Update admin
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
            respond(['error' => 'Name, email and phone required'], 400);
        }

        if (!empty($data['id'])) {
            // Update bestaande admin
            $stmt = $pdo->prepare('
                UPDATE admins 
                SET name = :name, phone = :phone, email = :email
                WHERE id = :id AND organisation_id = :org_id
            ');
            $stmt->execute([
                ':name' => $data['name'],
                ':phone' => $data['phone'], 
                ':email' => $data['email'],
                ':id' => $data['id'],
                ':org_id' => $org_id
            ]);
            respond(['id' => $data['id']]);
        } else {
            // Nieuwe admin (zonder wachtwoord - via uitnodiging)
            $stmt = $pdo->prepare('
                INSERT INTO admins (organisation_id, name, phone, email, password_hash)
                VALUES (:org_id, :name, :phone, :email, "")
            ');
            $stmt->execute([
                ':org_id' => $org_id,
                ':name' => $data['name'],
                ':phone' => $data['phone'],
                ':email' => $data['email']
            ]);
            respond(['id' => $pdo->lastInsertId()]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond(['error' => 'ID required'], 400);
        
        $stmt = $pdo->prepare('
            DELETE FROM admins 
            WHERE id = :id AND organisation_id = :org_id
        ');
        $stmt->execute([':id' => $id, ':org_id' => $org_id]);
        respond(['deleted' => (int)$id]);
        break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}
?>