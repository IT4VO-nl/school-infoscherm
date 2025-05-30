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

function generatePlayerId() {
    // Genereer 4-hex ID
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Verschillende endpoints
        if (isset($_GET['unclaimed'])) {
            // Lijst unclaimed players (voor claiming process)
            $stmt = $pdo->prepare('
                SELECT id, player_id, name, created_at
                FROM players 
                WHERE organisation_id IS NULL
                ORDER BY created_at DESC
            ');
            $stmt->execute();
            respond($stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } elseif (!empty($_SESSION['organisation_id'])) {
            // Lijst players van ingelogde organisatie
            $org_id = $_SESSION['organisation_id'];
            $stmt = $pdo->prepare('
                SELECT p.id, p.player_id, p.name, p.last_seen,
                       GROUP_CONCAT(pg.name SEPARATOR ", ") as groups
                FROM players p
                LEFT JOIN player_group_map pgm ON pgm.player_id = p.id
                LEFT JOIN player_groups pg ON pg.id = pgm.group_id
                WHERE p.organisation_id = :org_id
                GROUP BY p.id
                ORDER BY COALESCE(p.name, p.player_id)
            ');
            $stmt->execute([':org_id' => $org_id]);
            respond($stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } else {
            respond(['error' => 'Authentication required'], 401);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        if (isset($data['action']) && $data['action'] === 'generate') {
            // Genereer nieuwe unclaimed player
            $player_id = generatePlayerId();
            
            // Check voor duplicaten
            while (true) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM players WHERE player_id = :pid');
                $stmt->execute([':pid' => $player_id]);
                if ($stmt->fetchColumn() == 0) break;
                $player_id = generatePlayerId();
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO players (player_id, name, created_at)
                VALUES (:player_id, :name, NOW())
            ');
            $stmt->execute([
                ':player_id' => $player_id,
                ':name' => 'Wacht op registratie'
            ]);
            
            respond(['player_id' => $player_id, 'id' => $pdo->lastInsertId()]);
            
        } elseif (isset($data['action']) && $data['action'] === 'claim') {
            // Claim een player voor organisatie
            if (empty($_SESSION['organisation_id'])) {
                respond(['error' => 'Authentication required'], 401);
            }
            
            $org_id = $_SESSION['organisation_id'];
            $player_id = $data['player_id'] ?? '';
            
            if (!$player_id) respond(['error' => 'player_id required'], 400);
            
            $stmt = $pdo->prepare('
                UPDATE players 
                SET organisation_id = :org_id, name = :name
                WHERE player_id = :player_id AND organisation_id IS NULL
            ');
            $result = $stmt->execute([
                ':org_id' => $org_id,
                ':player_id' => $player_id,
                ':name' => $data['name'] ?? 'Nieuw scherm'
            ]);
            
            if ($stmt->rowCount() === 0) {
                respond(['error' => 'Player not found or already claimed'], 404);
            }
            
            respond(['success' => true, 'claimed' => $player_id]);
            
        } else {
            // Update bestaande player
            if (empty($_SESSION['organisation_id'])) {
                respond(['error' => 'Authentication required'], 401);
            }
            
            $org_id = $_SESSION['organisation_id'];
            $stmt = $pdo->prepare('
                UPDATE players 
                SET name = :name
                WHERE id = :id AND organisation_id = :org_id
            ');
            $stmt->execute([
                ':name' => $data['name'] ?? '',
                ':id' => $data['id'],
                ':org_id' => $org_id
            ]);
            
            respond(['success' => true]);
        }
        break;

    case 'DELETE':
        if (empty($_SESSION['organisation_id'])) {
            respond(['error' => 'Authentication required'], 401);
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) respond(['error' => 'ID required'], 400);
        
        $org_id = $_SESSION['organisation_id'];
        $stmt = $pdo->prepare('
            DELETE FROM players 
            WHERE id = :id AND organisation_id = :org_id
        ');
        $stmt->execute([':id' => $id, ':org_id' => $org_id]);
        respond(['deleted' => (int)$id]);
        break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}
?>