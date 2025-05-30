<?php
// api/player_groups.php - Player Groups CRUD API
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
        try {
            // Get groups with player info and slide count
            $stmt = $pdo->prepare('
                SELECT 
                    pg.id, pg.name, pg.description, pg.is_default,
                    COUNT(DISTINCT pgm.player_id) as player_count,
                    COUNT(DISTINCT sgm.slide_id) as slide_count
                FROM player_groups pg
                LEFT JOIN player_group_map pgm ON pgm.group_id = pg.id
                LEFT JOIN slide_group_map sgm ON sgm.group_id = pg.id
                WHERE pg.organisation_id = :org_id
                GROUP BY pg.id, pg.name, pg.description, pg.is_default
                ORDER BY pg.is_default DESC, pg.name ASC
            ');
            $stmt->execute([':org_id' => $org_id]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get players for each group
            foreach ($groups as &$group) {
                $stmt = $pdo->prepare('
                    SELECT p.id, p.player_id, p.name, p.last_seen
                    FROM players p
                    JOIN player_group_map pgm ON pgm.player_id = p.id
                    WHERE pgm.group_id = :group_id
                    ORDER BY p.name, p.player_id
                ');
                $stmt->execute([':group_id' => $group['id']]);
                $group['players'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            respond($groups);
        } catch (Exception $e) {
            respond(['error' => 'Database error'], 500);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) respond(['error' => 'JSON required'], 400);
        
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $players = $data['players'] ?? [];
        $group_id = $data['id'] ?? null;
        
        if (!$name) respond(['error' => 'Name required'], 400);
        
        try {
            $pdo->beginTransaction();
            
            if ($group_id) {
                // Update existing group
                $stmt = $pdo->prepare('
                    UPDATE player_groups 
                    SET name = :name, description = :description
                    WHERE id = :id AND organisation_id = :org_id AND is_default = 0
                ');
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':id' => $group_id,
                    ':org_id' => $org_id
                ]);
                
                if ($stmt->rowCount() === 0) {
                    $pdo->rollback();
                    respond(['error' => 'Group not found or cannot be modified'], 404);
                }
            } else {
                // Create new group
                $stmt = $pdo->prepare('
                    INSERT INTO player_groups (organisation_id, name, description, is_default)
                    VALUES (:org_id, :name, :description, 0)
                ');
                $stmt->execute([
                    ':org_id' => $org_id,
                    ':name' => $name,
                    ':description' => $description
                ]);
                $group_id = $pdo->lastInsertId();
            }
            
            // Update player assignments
            $stmt = $pdo->prepare('DELETE FROM player_group_map WHERE group_id = :group_id');
            $stmt->execute([':group_id' => $group_id]);
            
            if (!empty($players)) {
                $stmt = $pdo->prepare('
                    INSERT INTO player_group_map (player_id, group_id)
                    SELECT p.id, :group_id 
                    FROM players p 
                    WHERE p.id = :player_id AND p.organisation_id = :org_id
                ');
                
                foreach ($players as $player_id) {
                    $stmt->execute([
                        ':player_id' => (int)$player_id,
                        ':group_id' => $group_id,
                        ':org_id' => $org_id
                    ]);
                }
            }
            
            $pdo->commit();
            respond(['id' => $group_id, 'success' => true]);
            
        } catch (Exception $e) {
            $pdo->rollback();
            respond(['error' => 'Database error'], 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) respond(['error' => 'ID required'], 400);
        
        try {
            // Don't allow deleting default group
            $stmt = $pdo->prepare('
                SELECT is_default FROM player_groups 
                WHERE id = :id AND organisation_id = :org_id
            ');
            $stmt->execute([':id' => $id, ':org_id' => $org_id]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$group) {
                respond(['error' => 'Group not found'], 404);
            }
            
            if ($group['is_default']) {
                respond(['error' => 'Cannot delete default group'], 400);
            }
            
            // Delete group (cascading will handle mappings)
            $stmt = $pdo->prepare('
                DELETE FROM player_groups 
                WHERE id = :id AND organisation_id = :org_id AND is_default = 0
            ');
            $stmt->execute([':id' => $id, ':org_id' => $org_id]);
            
            respond(['deleted' => (int)$id]);
            
        } catch (Exception $e) {
            respond(['error' => 'Database error'], 500);
        }
        break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}
?>