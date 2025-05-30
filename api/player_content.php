<?php
// api/player_content.php - Content API voor players
require_once '../db_config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();
$player_id = $_GET['player_id'] ?? '';

if (!$player_id) {
    http_response_code(400);
    echo json_encode(['error' => 'player_id required']);
    exit;
}

try {
    // Get player info and verify it's claimed
    $stmt = $pdo->prepare('
        SELECT p.id, p.organisation_id, p.name, o.name as org_name, o.is_verified
        FROM players p
        JOIN organisations o ON o.id = p.organisation_id
        WHERE p.player_id = :player_id AND p.organisation_id IS NOT NULL
    ');
    $stmt->execute([':player_id' => $player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        echo json_encode(['error' => 'Player not found or not claimed', 'slides' => []]);
        exit;
    }
    
    // If organisation not verified, return limited content
    if (!$player['is_verified']) {
        echo json_encode([
            'slides' => [],
            'message' => 'Organisation not verified'
        ]);
        exit;
    }
    
    // Get player groups
    $stmt = $pdo->prepare('
        SELECT group_id 
        FROM player_group_map 
        WHERE player_id = :player_id
    ');
    $stmt->execute([':player_id' => $player['id']]);
    $player_groups = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // If no specific groups, get default group
    if (empty($player_groups)) {
        $stmt = $pdo->prepare('
            SELECT id FROM player_groups 
            WHERE organisation_id = :org_id AND is_default = 1
        ');
        $stmt->execute([':org_id' => $player['organisation_id']]);
        $default_group = $stmt->fetchColumn();
        if ($default_group) {
            $player_groups = [$default_group];
        }
    }
    
    // Get slides for this player's groups
    $slides = [];
    if (!empty($player_groups)) {
        $placeholders = str_repeat('?,', count($player_groups) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.id, s.type, s.name, s.title, s.content, s.subtitle, 
                   s.display_duration, s.is_active, s.publish_start, s.publish_end, s.is_continuous
            FROM slides s
            LEFT JOIN slide_group_map sgm ON sgm.slide_id = s.id
            WHERE s.organisation_id = ? 
            AND s.is_active = 1
            AND (
                sgm.group_id IN ($placeholders) 
                OR s.id NOT IN (
                    SELECT slide_id FROM slide_group_map 
                    WHERE slide_id = s.id
                )
            )
            AND (
                s.publish_start IS NULL 
                OR s.publish_start <= NOW()
            )
            AND (
                s.publish_end IS NULL 
                OR s.publish_end >= NOW() 
                OR s.is_continuous = 1
            )
            ORDER BY s.created_at DESC
        ");
        
        $params = array_merge([$player['organisation_id']], $player_groups);
        $stmt->execute($params);
        $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Format slides for player
    $formatted_slides = array_map(function($slide) {
        return [
            'id' => $slide['id'],
            'type' => $slide['type'],
            'title' => $slide['title'],
            'subtitle' => $slide['subtitle'],
            'content' => $slide['content'],
            'display_duration' => (int)$slide['display_duration']
        ];
    }, $slides);
    
    echo json_encode([
        'slides' => $formatted_slides,
        'player' => [
            'id' => $player['id'],
            'name' => $player['name'],
            'organisation' => $player['org_name']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'slides' => []]);
}
?>