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

// Auth check voor geverifieerde organisatie
if (empty($_SESSION['admin_id']) || empty($_SESSION['organisation_id'])) {
    respond(['error' => 'Authentication required'], 401);
}

// Check of organisatie geverifieerd is
$stmt = $pdo->prepare('SELECT is_verified FROM organisations WHERE id = :org_id');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$is_verified = $stmt->fetchColumn();

if (!$is_verified) {
    respond(['error' => 'Organisation not verified. Please verify your email first.'], 403);
}

$org_id = $_SESSION['organisation_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Haal slides op met group info
        $stmt = $pdo->prepare('
            SELECT s.*, 
                   GROUP_CONCAT(pg.name SEPARATOR ", ") as target_groups
            FROM slides s
            LEFT JOIN slide_group_map sgm ON sgm.slide_id = s.id
            LEFT JOIN player_groups pg ON pg.id = sgm.group_id
            WHERE s.organisation_id = :org_id
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ');
        $stmt->execute([':org_id' => $org_id]);
        $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Voeg computed fields toe
        foreach ($slides as &$slide) {
            $slide['is_published'] = (
                $slide['is_active'] && 
                ($slide['publish_start'] === null || $slide['publish_start'] <= date('Y-m-d H:i:s')) &&
                ($slide['publish_end'] === null || $slide['publish_end'] >= date('Y-m-d H:i:s') || $slide['is_continuous'])
            );
        }
        
        respond($slides);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        // Validatie
        if (empty($data['type']) || !in_array($data['type'], ['news', 'absence', 'roster'])) {
            respond(['error' => 'Valid type required (news, absence, roster)'], 400);
        }

        $pdo->beginTransaction();
        
        try {
            if (!empty($data['id'])) {
                // Update bestaande slide
                $stmt = $pdo->prepare('
                    UPDATE slides 
                    SET name = :name, title = :title, content = :content, 
                        subtitle = :subtitle, type = :type, display_duration = :duration,
                        is_active = :active, publish_start = :pub_start, 
                        publish_end = :pub_end, is_continuous = :continuous
                    WHERE id = :id AND organisation_id = :org_id
                ');
                $stmt->execute([
                    ':name' => $data['name'] ?? date('Y-m-d H:i'),
                    ':title' => $data['title'] ?? '',
                    ':content' => $data['content'] ?? '',
                    ':subtitle' => $data['subtitle'] ?? '',
                    ':type' => $data['type'],
                    ':duration' => $data['display_duration'] ?? 5,
                    ':active' => $data['is_active'] ?? true,
                    ':pub_start' => $data['publish_start'] ?? null,
                    ':pub_end' => $data['publish_end'] ?? null,
                    ':continuous' => $data['is_continuous'] ?? false,
                    ':id' => $data['id'],
                    ':org_id' => $org_id
                ]);
                $slide_id = $data['id'];
            } else {
                // Nieuwe slide
                $stmt = $pdo->prepare('
                    INSERT INTO slides 
                    (organisation_id, name, title, content, subtitle, type, 
                     display_duration, is_active, publish_start, publish_end, is_continuous)
                    VALUES 
                    (:org_id, :name, :title, :content, :subtitle, :type, 
                     :duration, :active, :pub_start, :pub_end, :continuous)
                ');
                $stmt->execute([
                    ':org_id' => $org_id,
                    ':name' => $data['name'] ?? date('Y-m-d H:i'),
                    ':title' => $data['title'] ?? '',
                    ':content' => $data['content'] ?? '',
                    ':subtitle' => $data['subtitle'] ?? '',
                    ':type' => $data['type'],
                    ':duration' => $data['display_duration'] ?? 5,
                    ':active' => $data['is_active'] ?? true,
                    ':pub_start' => $data['publish_start'] ?? null,
                    ':pub_end' => $data['publish_end'] ?? null,
                    ':continuous' => $data['is_continuous'] ?? false
                ]);
                $slide_id = $pdo->lastInsertId();
            }
            
            // Update group targeting
            $pdo->prepare('DELETE FROM slide_group_map WHERE slide_id = :slide_id')
                ->execute([':slide_id' => $slide_id]);
                
            if (!empty($data['target_groups'])) {
                $stmt = $pdo->prepare('
                    INSERT INTO slide_group_map (slide_id, group_id) 
                    VALUES (:slide_id, :group_id)
                ');
                foreach ($data['target_groups'] as $group_id) {
                    $stmt->execute([':slide_id' => $slide_id, ':group_id' => $group_id]);
                }
            }
            
            $pdo->commit();
            respond(['id' => $slide_id, 'success' => true]);
            
        } catch (Exception $e) {
            $pdo->rollback();
            respond(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond(['error' => 'ID required'], 400);
        
        $stmt = $pdo->prepare('
            DELETE FROM slides 
            WHERE id = :id AND organisation_id = :org_id
        ');
        $stmt->execute([':id' => $id, ':org_id' => $org_id]);
        respond(['deleted' => (int)$id]);
        break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}
?>