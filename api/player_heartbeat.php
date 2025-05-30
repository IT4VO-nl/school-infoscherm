<?php
// api/player_heartbeat.php - Player heartbeat API
require_once '../db_config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$pdo = getDbConnection();
$data = json_decode(file_get_contents('php://input'), true);
$player_id = $data['player_id'] ?? '';

if (!$player_id) {
    http_response_code(400);
    echo json_encode(['error' => 'player_id required']);
    exit;
}

try {
    // Update player last_seen timestamp
    $stmt = $pdo->prepare('
        UPDATE players 
        SET last_seen = NOW() 
        WHERE player_id = :player_id AND organisation_id IS NOT NULL
    ');
    $stmt->execute([':player_id' => $player_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'player_id' => $player_id
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Player not found or not claimed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>