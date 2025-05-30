<?php
session_start();
require_once 'db_config.php';

// Check authentication
if (empty($_SESSION['admin_id']) || empty($_SESSION['organisation_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDbConnection();

// Get organisation info
$stmt = $pdo->prepare('SELECT name, organisation_id, is_verified FROM organisations WHERE id = :org_id');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$org_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Get player groups for assignment
$stmt = $pdo->prepare('SELECT id, name, description FROM player_groups WHERE organisation_id = :org_id ORDER BY name');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players Beheren - <?= htmlspecialchars($org_info['name']) ?></title>
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
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .breadcrumb a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 2em;
            color: #333;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }

        .btn-primary {
            background: #007cba;
            color: white;
        }

        .btn-primary:hover {
            background: #005a8b;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .info-card .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .info-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #007cba;
        }

        .info-card .label {
            color: #666;
            margin-top: 5px;
        }

        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .player-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .player-card:hover {
            transform: translateY(-2px);
        }

        .player-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .player-id {
            font-family: monospace;
            background: #007cba;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1.1em;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .status-online { color: #28a745; }
        .status-recent { color: #ffc107; }
        .status-offline { color: #dc3545; }
        .status-never { color: #6c757d; }

        .player-content {
            padding: 20px;
        }

        .player-name {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .player-info {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .player-groups {
            margin-bottom: 15px;
        }

        .group-tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin: 2px;
        }

        .player-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 20px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
        }

        .form-control:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.25);
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .empty-state .icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .claiming-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .claiming-section h3 {
            color: #007cba;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .claim-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .claim-form .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                justify-content: center;
            }

            .players-grid {
                grid-template-columns: 1fr;
            }

            .info-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .claim-form {
                flex-direction: column;
            }

            .claim-form .form-group {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="breadcrumb">
                <a href="dashboard.php">🏠 Dashboard</a>
                <span>→</span>
                <span>📺 Players</span>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Terug</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">📺 Players Beheren</h1>
            <div class="header-actions">
                <button class="btn btn-success" onclick="generatePlayer()">
                    🎲 Genereer Player ID
                </button>
                <button class="btn btn-primary" onclick="refreshPlayers()">
                    🔄 Ververs Status
                </button>
            </div>
        </div>

        <!-- Player Statistics -->
        <div class="info-cards" id="playerStats">
            <!-- Stats worden hier geladen -->
        </div>

        <!-- Claiming Section -->
        <div class="claiming-section">
            <h3>➕ Player Claimen</h3>
            <p style="margin-bottom: 20px; color: #666;">
                Voer een Player ID in om het scherm toe te voegen aan je organisatie, 
                of genereer een nieuwe Player ID die je kunt gebruiken op een scherm.
            </p>
            <div class="claim-form">
                <div class="form-group">
                    <label for="claimPlayerId">Player ID</label>
                    <input type="text" id="claimPlayerId" class="form-control" 
                           placeholder="bijv. A1B2" style="text-transform: uppercase;" maxlength="4">
                </div>
                <div class="form-group">
                    <label for="claimPlayerName">Scherm Naam</label>
                    <input type="text" id="claimPlayerName" class="form-control" 
                           placeholder="bijv. Aula Hoofdingang">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary" onclick="claimPlayer()">
                        🎯 Claimen
                    </button>
                </div>
            </div>
        </div>

        <!-- Players Grid -->
        <div class="players-grid" id="playersContainer">
            <!-- Players worden hier geladen -->
        </div>
    </div>

    <!-- Edit Player Modal -->
    <div id="playerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Player Bewerken</h2>
                <span class="close" onclick="closePlayerModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="playerForm">
                    <input type="hidden" id="playerId" name="id">
                    
                    <div class="form-group">
                        <label for="playerName">Scherm Naam</label>
                        <input type="text" id="playerName" name="name" class="form-control" 
                               placeholder="bijv. Aula Hoofdingang, Kantine, Gang Verdieping 2">
                    </div>

                    <?php if (!empty($groups)): ?>
                    <div class="form-group">
                        <label>Groepen</label>
                        <div class="checkbox-group">
                            <?php foreach ($groups as $group): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="groups[]" 
                                           value="<?= $group['id'] ?>" 
                                           id="group_<?= $group['id'] ?>">
                                    <label for="group_<?= $group['id'] ?>">
                                        <?= htmlspecialchars($group['name']) ?>
                                        <?php if ($group['description']): ?>
                                            <small style="color: #666;"> - <?= htmlspecialchars($group['description']) ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="button" class="btn btn-secondary" onclick="closePlayerModal()">Annuleren</button>
                        <button type="submit" class="btn btn-success">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let players = [];

        // Load players on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPlayers();
        });

        async function loadPlayers() {
            try {
                const response = await fetch('api/players.php');
                const data = await response.json();
                
                if (response.ok) {
                    players = data;
                    renderPlayers();
                    updateStats();
                } else {
                    console.error('Error loading players:', data.error);
                    showError('Fout bij laden van players: ' + data.error);
                }
            } catch (error) {
                console.error('Network error:', error);
                showError('Netwerkfout bij laden van players');
            }
        }

        function updateStats() {
            const total = players.length;
            const online = players.filter(p => getPlayerStatus(p.last_seen) === 'online').length;
            const recent = players.filter(p => getPlayerStatus(p.last_seen) === 'recent').length;
            const offline = players.filter(p => getPlayerStatus(p.last_seen) === 'offline').length;

            document.getElementById('playerStats').innerHTML = `
                <div class="info-card">
                    <div class="icon">📺</div>
                    <div class="number">${total}</div>
                    <div class="label">Totaal Players</div>
                </div>
                <div class="info-card">
                    <div class="icon">🟢</div>
                    <div class="number">${online}</div>
                    <div class="label">Online Nu</div>
                </div>
                <div class="info-card">
                    <div class="icon">🟡</div>
                    <div class="number">${recent}</div>
                    <div class="label">Recent Actief</div>
                </div>
                <div class="info-card">
                    <div class="icon">🔴</div>
                    <div class="number">${offline}</div>
                    <div class="label">Offline</div>
                </div>
            `;
        }

        function renderPlayers() {
            const container = document.getElementById('playersContainer');
            
            if (players.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">📺</div>
                        <h3>Nog geen players</h3>
                        <p>Genereer een Player ID of claim een bestaande player</p>
                        <button class="btn btn-success" onclick="generatePlayer()">🎲 Eerste Player Genereren</button>
                    </div>
                `;
                return;
            }

            container.innerHTML = players.map(player => {
                const status = getPlayerStatus(player.last_seen);
                const statusClass = `status-${status}`;
                const statusIcon = getStatusIcon(status);
                const statusLabel = getStatusLabel(status);

                return `
                    <div class="player-card">
                        <div class="player-header">
                            <div class="player-id">${player.player_id}</div>
                            <div class="status-indicator ${statusClass}">
                                ${statusIcon} ${statusLabel}
                            </div>
                        </div>
                        <div class="player-content">
                            <div class="player-name">${player.name || 'Geen naam ingesteld'}</div>
                            <div class="player-info">
                                ${player.last_seen ? 
                                    `Laatst gezien: ${formatDate(player.last_seen)}` : 
                                    'Nog nooit online geweest'
                                }
                            </div>
                            ${player.groups ? `
                                <div class="player-groups">
                                    ${player.groups.split(', ').map(group => 
                                        `<span class="group-tag">${group}</span>`
                                    ).join('')}
                                </div>
                            ` : '<div class="player-groups"><span class="group-tag">Geen groepen</span></div>'}
                            <div class="player-actions">
                                <button class="btn btn-primary btn-sm" onclick="editPlayer(${player.id})">
                                    ✏️ Bewerken
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deletePlayer(${player.id})">
                                    🗑️ Verwijderen
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function getPlayerStatus(lastSeen) {
            if (!lastSeen) return 'never';
            
            const now = new Date();
            const lastSeenDate = new Date(lastSeen);
            const diffMinutes = (now - lastSeenDate) / (1000 * 60);
            
            if (diffMinutes <= 5) return 'online';
            if (diffMinutes <= 30) return 'recent';
            return 'offline';
        }

        function getStatusIcon(status) {
            const icons = {
                'online': '🟢',
                'recent': '🟡',
                'offline': '🔴',
                'never': '⚫'
            };
            return icons[status] || '❓';
        }

        function getStatusLabel(status) {
            const labels = {
                'online': 'Online',
                'recent': 'Recent',
                'offline': 'Offline',
                'never': 'Nooit online'
            };
            return labels[status] || 'Onbekend';
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('nl-NL');
        }

        async function generatePlayer() {
            try {
                const response = await fetch('api/players.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'generate'
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showSuccess(`Nieuwe Player ID gegenereerd: ${result.player_id}`);
                    document.getElementById('claimPlayerId').value = result.player_id;
                    loadPlayers();
                } else {
                    showError('Fout bij genereren: ' + result.error);
                }
            } catch (error) {
                showError('Netwerkfout bij genereren');
            }
        }

        async function claimPlayer() {
            const playerId = document.getElementById('claimPlayerId').value.trim().toUpperCase();
            const playerName = document.getElementById('claimPlayerName').value.trim();

            if (!playerId) {
                showError('Voer een Player ID in');
                return;
            }

            if (playerId.length !== 4) {
                showError('Player ID moet 4 karakters zijn');
                return;
            }

            try {
                const response = await fetch('api/players.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'claim',
                        player_id: playerId,
                        name: playerName || 'Nieuw scherm'
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showSuccess(`Player ${playerId} succesvol geclaimed!`);
                    document.getElementById('claimPlayerId').value = '';
                    document.getElementById('claimPlayerName').value = '';
                    loadPlayers();
                } else {
                    showError('Fout bij claimen: ' + result.error);
                }
            } catch (error) {
                showError('Netwerkfout bij claimen');
            }
        }

        function editPlayer(playerId) {
            const player = players.find(p => p.id == playerId);
            if (!player) return;

            document.getElementById('playerId').value = player.id;
            document.getElementById('playerName').value = player.name || '';

            // Reset group checkboxes
            document.querySelectorAll('input[name="groups[]"]').forEach(cb => {
                cb.checked = false;
            });

            // Check current groups
            if (player.groups) {
                const currentGroups = player.groups.split(', ');
                document.querySelectorAll('input[name="groups[]"]').forEach(cb => {
                    const label = cb.nextElementSibling.textContent.trim();
                    if (currentGroups.some(group => label.startsWith(group))) {
                        cb.checked = true;
                    }
                });
            }

            document.getElementById('playerModal').style.display = 'block';
        }

        function closePlayerModal() {
            document.getElementById('playerModal').style.display = 'none';
        }

        async function deletePlayer(playerId) {
            const player = players.find(p => p.id == playerId);
            if (!player) return;

            if (!confirm(`Weet je zeker dat je player "${player.name || player.player_id}" wilt verwijderen?`)) {
                return;
            }

            try {
                const response = await fetch(`api/players.php?id=${playerId}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    showSuccess('Player verwijderd');
                    loadPlayers();
                } else {
                    const data = await response.json();
                    showError('Fout bij verwijderen: ' + data.error);
                }
            } catch (error) {
                showError('Netwerkfout bij verwijderen');
            }
        }

        function refreshPlayers() {
            loadPlayers();
            showSuccess('Player status vernieuwd');
        }

        // Form submission
        document.getElementById('playerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Handle multiple checkboxes for groups
            const selectedGroups = Array.from(document.querySelectorAll('input[name="groups[]"]:checked'))
                                       .map(cb => cb.value);
            data.groups_ids = selectedGroups;

            try {
                const response = await fetch('api/players.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    showSuccess('Player bijgewerkt');
                    closePlayerModal();
                    loadPlayers();
                } else {
                    showError('Fout bij opslaan: ' + result.error);
                }
            } catch (error) {
                showError('Netwerkfout bij opslaan');
            }
        });

        // Auto-uppercase player ID input
        document.getElementById('claimPlayerId').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        function showSuccess(message) {
            alert('✅ ' + message);
        }

        function showError(message) {
            alert('❌ ' + message);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('playerModal');
            if (event.target == modal) {
                closePlayerModal();
            }
        }
    </script>
</body>
</html>