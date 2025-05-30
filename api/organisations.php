<?php
session_start();
require_once '../db_config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();

// Helper function
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Auth check - admin moet ingelogd zijn
if (empty($_SESSION['admin_id']) || empty($_SESSION['organisation_id'])) {
    respond(['error' => 'Authentication required'], 401);
}

$org_id = $_SESSION['organisation_id']; // Dit is nu de int ID
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Haal organisatie info op
        $stmt = $pdo->prepare('
            SELECT id, organisation_id as slug, name, phone, email, address, 
                   brin_code, logo, is_verified, created_at
            FROM organisations 
            WHERE id = :org_id
        ');
        $stmt->execute([':org_id' => $org_id]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$org) respond(['error' => 'Organisation not found'], 404);
        respond($org);
        break;

    case 'PUT':
    case 'POST':
        // Update organisatie gegevens
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $stmt = $pdo->prepare('
            UPDATE organisations 
            SET name = :name, phone = :phone, email = :email, 
                address = :address, brin_code = :brin
            WHERE id = :org_id
        ');
        
        $result = $stmt->execute([
            ':name' => $data['name'] ?? '',
            ':phone' => $data['phone'] ?? '',
            ':email' => $data['email'] ?? '',
            ':address' => $data['address'] ?? '',
            ':brin' => $data['brin_code'] ?? '',
            ':org_id' => $org_id
        ]);
        
        respond(['success' => $result]);
        break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}
?>