<?php
// api/verify_school.php - School Verificatie API
require_once '../includes/SchoolVerifier.php';
header('Content-Type: application/json; charset=utf-8');

$verifier = new SchoolVerifier();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $action = $_GET['action'] ?? 'verify';
    
    switch ($action) {
        case 'verify':
            // Verificeer school op BRIN
            $brin = $_GET['brin'] ?? '';
            if (!$brin) {
                http_response_code(400);
                echo json_encode(['error' => 'BRIN code required']);
                exit;
            }
            
            $result = $verifier->verifyByBrin($brin);
            echo json_encode($result);
            break;
            
        case 'search':
            // Zoek scholen op naam
            $name = $_GET['name'] ?? '';
            if (!$name) {
                http_response_code(400);
                echo json_encode(['error' => 'School name required']);
                exit;
            }
            
            $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
            $result = $verifier->searchByName($name, $limit);
            echo json_encode($result);
            break;
            
        case 'stats':
            // Database statistieken
            $stats = $verifier->getStats();
            echo json_encode($stats);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Volledige verificatie
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON data required']);
        exit;
    }
    
    $brin = $data['brin'] ?? '';
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? null;
    
    if (!$brin || !$name || !$email) {
        http_response_code(400);
        echo json_encode(['error' => 'BRIN, name and email are required']);
        exit;
    }
    
    $result = $verifier->fullVerification($brin, $name, $email, $address);
    echo json_encode($result);
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>