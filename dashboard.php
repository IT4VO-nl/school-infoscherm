<?php
session_start();
require_once 'db_config.php';

// Check authentication
if (empty($_SESSION['admin_id']) || empty($_SESSION['organisation_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDbConnection();

// Get organisation and admin info
$stmt = $pdo->prepare('
    SELECT o.name as org_name, o.organisation_id as org_slug, o.is_verified as org_verified,
           o.phone as org_phone, o.email as org_email, o.brin_code,
           a.name as admin_name, a.email as admin_email
    FROM organisations o
    JOIN admins a ON a.organisation_id = o.id
    WHERE o.id = :org_id AND a.id = :admin_id
');
$stmt->execute([
    ':org_id' => $_SESSION['organisation_id'],
    ':admin_id' => $_SESSION['admin_id']
]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get statistics
$stats = [];

// Count players
$stmt = $pdo->prepare('SELECT COUNT(*) as total, 
                             SUM(CASE WHEN last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 ELSE 0 END) as online
                      FROM players WHERE organisation_id = :org_id');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$stats['players'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Count slides
$stmt = $pdo->prepare('SELECT COUNT(*) as total,
                             SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
                      FROM slides WHERE organisation_id = :org_id');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$stats['slides'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Count groups
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM player_groups WHERE organisation_id = :org_id');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$stats['groups'] = $stmt->fetchColumn();

// Recent slides
$stmt = $pdo->prepare('
    SELECT s.*, 
           CASE s.type 
               WHEN "news" THEN "📰 Nieuws"
               WHEN "absence" THEN "🏥 Ziekte/Verlof" 
               WHEN "roster" THEN "📅 Roosterwijziging"
           END as type_label
    FROM slides s
    WHERE s.organisation_id = :org_id
    ORDER BY s.updated_at DESC
    LIMIT 5
');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$recent_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent players
$stmt = $pdo->prepare('
    SELECT p.*, 
           CASE 
               WHEN p.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN "🟢 Online"
               WHEN p.last_seen > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN "🟡 Recent"
               ELSE "🔴 Offline"
           END as status_label
    FROM players p
    WHERE p.organisation_id = :org_id
    ORDER BY p.last_seen DESC
    LIMIT 5
');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$recent_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($info['org_name']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #007cba 0%, #005a8b 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .org-info h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .org-info .org-id {
            font-family: monospace;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .user-info {
            text-align: right;
        }

        .user-info .admin-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .verification-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007cba;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 10px;
        }

        .stat-detail {
            font-size: 0.9em;
            color: #888;
        }

        .actions-section {
            margin-bottom: 30px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            background: white;
            border: 2px solid #007cba;
            color: #007cba;
            padding: 20px;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
            display: block;
        }

        .action-btn:hover {
            background: #007cba;
            color: white;
            transform: translateY(-2px);
        }

        .action-btn .icon {
            font-size: 2em;
            display: block;
            margin-bottom: 10px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .section-header h2 {
            color: #333;
            font-size: 1.3em;
        }

        .section-content {
            padding: 20px;
        }

        .item-list {
            list-style: none;
        }

        .item-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-list li:last-child {
            border-bottom: none;
        }

        .item-title {
            font-weight: 600;
            color: #333;
        }

        .item-meta {
            font-size: 0.9em;
            color: #666;
        }

        .item-status {
            font-size: 0.9em;
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            color: #888;
            padding: 40px 20px;
        }

        .empty-state .icon {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .user-info {
                text-align: center;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="org-info">
                <h1><?= htmlspecialchars($info['org_name']) ?></h1>
                <div class="org-id">ID: <?= htmlspecialchars($info['org_slug']) ?></div>
            </div>
            <div class="user-info">
                <div class="admin-name">👋 <?= htmlspecialchars($info['admin_name']) ?></div>
                <a href="logout.php" class="logout-btn">Uitloggen</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!$info['org_verified']): ?>
        <div class="verification-warning">
            <strong>⚠️ Account niet volledig geverifieerd</strong><br>
            Voltooi de verificatie om alle functies te kunnen gebruiken.
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📺</div>
                <div class="stat-number"><?= $stats['players']['total'] ?></div>
                <div class="stat-label">Players</div>
                <div class="stat-detail"><?= $stats['players']['online'] ?> online nu</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-number"><?= $stats['slides']['total'] ?></div>
                <div class="stat-label">Slides</div>
                <div class="stat-detail"><?= $stats['slides']['active'] ?> actief</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?= $stats['groups'] ?></div>
                <div class="stat-label">Groepen</div>
                <div class="stat-detail">Scherm groepen</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="actions-section">
            <h2 style="margin-bottom: 20px; color: #333;">⚡ Snelle Acties</h2>
            <div class="actions-grid">
                <a href="slides.php" class="action-btn">
                    <span class="icon">📝</span>
                    Slides Beheren
                </a>
                <a href="players.php" class="action-btn">
                    <span class="icon">📺</span>
                    Players Beheren
                </a>
                <a href="player.php" class="action-btn">
                    <span class="icon">➕</span>
                    Player Toevoegen
                </a>
                <a href="groups.php" class="action-btn">
                    <span class="icon">👥</span>
                    Groepen Beheren
                </a>
            </div>
        </div>

        <!-- Content Overview -->
        <div class="content-grid">
            <!-- Recent Slides -->
            <div class="content-section">
                <div class="section-header">
                    <h2>📝 Recente Slides</h2>
                </div>
                <div class="section-content">
                    <?php if (empty($recent_slides)): ?>
                        <div class="empty-state">
                            <div class="icon">📄</div>
                            <p>Nog geen slides aangemaakt</p>
                            <p><a href="slides.php">Maak je eerste slide</a></p>
                        </div>
                    <?php else: ?>
                        <ul class="item-list">
                            <?php foreach ($recent_slides as $slide): ?>
                                <li>
                                    <div>
                                        <div class="item-title">
                                            <?= $slide['type_label'] ?>
                                            <?php if ($slide['title']): ?>
                                                - <?= htmlspecialchars($slide['title']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-meta">
                                            <?= date('d-m-Y H:i', strtotime($slide['updated_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="item-status" style="background: <?= $slide['is_active'] ? '#d4edda' : '#f8d7da' ?>">
                                        <?= $slide['is_active'] ? '✅ Actief' : '❌ Inactief' ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Players -->
            <div class="content-section">
                <div class="section-header">
                    <h2>📺 Players Status</h2>
                </div>
                <div class="section-content">
                    <?php if (empty($recent_players)): ?>
                        <div class="empty-state">
                            <div class="icon">📺</div>
                            <p>Nog geen players geregistreerd</p>
                            <p><a href="player.php">Voeg je eerste player toe</a></p>
                        </div>
                    <?php else: ?>
                        <ul class="item-list">
                            <?php foreach ($recent_players as $player): ?>
                                <li>
                                    <div>
                                        <div class="item-title">
                                            <?= htmlspecialchars($player['name'] ?: $player['player_id']) ?>
                                        </div>
                                        <div class="item-meta">
                                            ID: <?= htmlspecialchars($player['player_id']) ?>
                                            <?php if ($player['last_seen']): ?>
                                                | <?= date('d-m H:i', strtotime($player['last_seen'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="item-status">
                                        <?= $player['status_label'] ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh elke 30 seconden voor player status
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>