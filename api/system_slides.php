<?php
// api/system_slides.php - System slides voor alle players
require_once '../db_config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();

try {
    // Get active system slides
    $stmt = $pdo->prepare('
        SELECT id, type, title, content, priority, target_unverified, target_verified
        FROM system_slides 
        WHERE is_active = 1
        ORDER BY priority DESC, created_at DESC
    ');
    $stmt->execute();
    $system_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for player consumption
    $formatted_slides = array_map(function($slide) {
        return [
            'id' => 'system_' . $slide['id'],
            'type' => $slide['type'],
            'title' => $slide['title'],
            'content' => $slide['content'],
            'display_duration' => min(10, max(5, $slide['priority'] * 2)), // 5-10 seconds based on priority
            'priority' => $slide['priority']
        ];
    }, $system_slides);
    
    echo json_encode($formatted_slides);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
?>