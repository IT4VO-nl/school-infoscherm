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
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groepen Beheren - <?= htmlspecialchars($org_info['name']) ?></title>
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

        .info-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .info-section h3 {
            color: #007cba;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .group-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            position: relative;
        }

        .group-card:hover {
            transform: translateY(-2px);
        }

        .group-card.default-group {
            border: 2px solid #007cba;
        }

        .default-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #007cba;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .group-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .group-name {
            font-size: 1.4em;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .group-description {
            color: #666;
            line-height: 1.5;
        }

        .group-content {
            padding: 20px;
        }

        .group-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
            color: #007cba;
        }

        .stat-label {
            font-size: 0.9em;
            color: #666;
        }

        .players-list {
            margin-bottom: 20px;
        }

        .players-list h4 {
            margin-bottom: 10px;
            color: #333;
            font-size: 1.1em;
        }

        .player-tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.85em;
            margin: 2px;
            font-weight: 500;
        }

        .player-tag.online {
            background: #d4edda;
            color: #155724;
        }

        .group-actions {
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
            max-width: 600px;
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

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item label {
            margin-bottom: 0;
            font-weight: normal;
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

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .groups-grid {
                grid-template-columns: 1fr;
            }

            .group-stats {
                flex-direction: column;
                gap: 15px;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
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
                <span>👥 Groepen</span>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Terug</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">👥 Groepen Beheren</h1>
            <button class="btn btn-primary" onclick="openGroupModal()">
                ➕ Nieuwe Groep
            </button>
        </div>

        <div class="info-section">
            <h3>💡 Over Groepen</h3>
            <p style="color: #666; line-height: 1.6;">
                Groepen helpen je om slides gericht te tonen aan specifieke schermen. Bijvoorbeeld:
                <strong>"Begane Grond"</strong> voor lobby schermen, <strong>"Klas 1A"</strong> voor klaslokalen, 
                of <strong>"Docentenkamer"</strong> voor personeelsruimtes. Elke organisatie heeft automatisch 
                een hoofdgroep met alle schermen.
            </p>
        </div>

        <div class="groups-grid" id="groupsContainer">
            <!-- Groups worden hier geladen -->
        </div>
    </div>

    <!-- Group Modal -->
    <div id="groupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nieuwe Groep</h2>
                <span class="close" onclick="closeGroupModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="groupForm">
                    <input type="hidden" id="groupId" name="id">
                    
                    <div class="form-group">
                        <label for="groupName">Groep Naam *</label>
                        <input type="text" id="groupName" name="name" class="form-control" 
                               placeholder="bijv. Begane Grond, Klas 1A, Docentenkamer" required>
                    </div>

                    <div class="form-group">
                        <label for="groupDescription">Omschrijving</label>
                        <textarea id="groupDescription" name="description" class="form-control" 
                                  placeholder="Optionele omschrijving van deze groep..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Players toewijzen aan deze groep</label>
                        <div class="checkbox-group" id="playersCheckboxes">
                            <!-- Players worden hier geladen -->
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="button" class="btn btn-secondary" onclick="closeGroupModal()">Annuleren</button>
                        <button type="submit" class="btn btn-success">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let groups = [];
        let players = [];

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadGroups();
            loadPlayers();
        });

        async function loadGroups() {
            try {
                const response = await fetch('api/player_groups.php');
                const data = await response.json();
                
                if (response.ok) {
                    groups = data;
                    renderGroups();
                } else {
                    console.error('Error loading groups:', data.error);
                    showError('Fout bij laden van groepen: ' + data.error);
                }
            } catch (error) {
                console.error('Network error:', error);
                showError('Netwerkfout bij laden van groepen');
            }
        }

        async function loadPlayers() {
            try {
                const response = await fetch('api/players.php');
                const data = await response.json();
                
                if (response.ok) {
                    players = data;
                } else {
                    console.error('Error loading players:', data.error);
                }
            } catch (error) {
                console.error('Network error loading players:', error);
            }
        }

        function renderGroups() {
            const container = document.getElementById('groupsContainer');
            
            if (groups.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">👥</div>
                        <h3>Nog geen groepen</h3>
                        <p>Maak je eerste groep om players te organiseren</p>
                        <button class="btn btn-primary" onclick="openGroupModal()">➕ Eerste Groep Maken</button>
                    </div>
                `;
                return;
            }

            container.innerHTML = groups.map(group => {
                const playerCount = group.players ? group.players.length : 0;
                const onlineCount = group.players ? 
                    group.players.filter(p => getPlayerStatus(p.last_seen) === 'online').length : 0;
                const slideCount = group.slide_count || 0;

                return `
                    <div class="group-card ${group.is_default ? 'default-group' : ''}">
                        ${group.is_default ? '<div class="default-badge">Hoofdgroep</div>' : ''}
                        
                        <div class="group-header">
                            <div class="group-name">${group.name}</div>
                            ${group.description ? `<div class="group-description">${group.description}</div>` : ''}
                        </div>
                        
                        <div class="group-content">
                            <div class="group-stats">
                                <div class="stat-item">
                                    <div class="stat-number">${playerCount}</div>
                                    <div class="stat-label">Players</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">${onlineCount}</div>
                                    <div class="stat-label">Online</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">${slideCount}</div>
                                    <div class="stat-label">Slides</div>
                                </div>
                            </div>

                            ${group.players && group.players.length > 0 ? `
                                <div class="players-list">
                                    <h4>📺 Players in deze groep:</h4>
                                    <div>
                                        ${group.players.map(player => `
                                            <span class="player-tag ${getPlayerStatus(player.last_seen) === 'online' ? 'online' : ''}">
                                                ${player.name || player.player_id}
                                            </span>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : `
                                <div class="players-list">
                                    <h4>📺 Geen players toegewezen</h4>
                                    <p style="color: #666; font-size: 0.9em;">Voeg players toe om deze groep te gebruiken</p>
                                </div>
                            `}

                            <div class="group-actions">
                                <button class="btn btn-primary btn-sm" onclick="editGroup(${group.id})">
                                    ✏️ Bewerken
                                </button>
                                ${!group.is_default ? `
                                    <button class="btn btn-danger btn-sm" onclick="deleteGroup(${group.id})">
                                        🗑️ Verwijderen
                                    </button>
                                ` : ''}
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

        function openGroupModal(groupId = null) {
            const modal = document.getElementById('groupModal');
            const form = document.getElementById('groupForm');
            const title = document.getElementById('modalTitle');

            // Populate players checkboxes
            populatePlayersCheckboxes();

            if (groupId) {
                const group = groups.find(g => g.id == groupId);
                if (group) {
                    title.textContent = 'Groep Bewerken';
                    populateGroupForm(group);
                }
            } else {
                title.textContent = 'Nieuwe Groep';
                form.reset();
                document.getElementById('groupId').value = '';
            }

            modal.style.display = 'block';
        }

        function closeGroupModal() {
            document.getElementById('groupModal').style.display = 'none';
        }

        function populatePlayersCheckboxes() {
            const container = document.getElementById('playersCheckboxes');
            
            if (players.length === 0) {
                container.innerHTML = '<p style="color: #666;">Geen players beschikbaar. Voeg eerst players toe.</p>';
                return;
            }

            container.innerHTML = players.map(player => `
                <div class="checkbox-item">
                    <input type="checkbox" name="players[]" value="${player.id}" id="player_${player.id}">
                    <label for="player_${player.id}">
                        ${player.name || player.player_id}
                        <small style="color: #666;">(${player.player_id})</small>
                    </label>
                </div>
            `).join('');
        }

        function populateGroupForm(group) {
            document.getElementById('groupId').value = group.id;
            document.getElementById('groupName').value = group.name;
            document.getElementById('groupDescription').value = group.description || '';

            // Reset checkboxes
            document.querySelectorAll('input[name="players[]"]').forEach(cb => {
                cb.checked = false;
            });

            // Check current players
            if (group.players) {
                group.players.forEach(player => {
                    const checkbox = document.getElementById(`player_${player.id}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        }

        function editGroup(groupId) {
            openGroupModal(groupId);
        }

        async function deleteGroup(groupId) {
            const group = groups.find(g => g.id == groupId);
            if (!group) return;

            if (group.is_default) {
                showError('De hoofdgroep kan niet worden verwijderd');
                return;
            }

            if (!confirm(`Weet je zeker dat je groep "${group.name}" wilt verwijderen?`)) {
                return;
            }

            try {
                const response = await fetch(`api/player_groups.php?id=${groupId}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    showSuccess('Groep verwijderd');
                    loadGroups();
                } else {
                    const data = await response.json();
                    showError('Fout bij verwijderen: ' + data.error);
                }
            } catch (error) {
                showError('Netwerkfout bij verwijderen');
            }
        }

        // Form submission
        document.getElementById('groupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Handle multiple checkboxes for players
            const selectedPlayers = Array.from(document.querySelectorAll('input[name="players[]"]:checked'))
                                       .map(cb => cb.value);
            data.players = selectedPlayers;

            try {
                const response = await fetch('api/player_groups.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    showSuccess(data.id ? 'Groep bijgewerkt' : 'Groep aangemaakt');
                    closeGroupModal();
                    loadGroups();
                } else {
                    showError('Fout bij opslaan: ' + result.error);
                }
            } catch (error) {
                showError('Netwerkfout bij opslaan');
            }
        });

        function showSuccess(message) {
            alert('✅ ' + message);
        }

        function showError(message) {
            alert('❌ ' + message);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('groupModal');
            if (event.target == modal) {
                closeGroupModal();
            }
        }
    </script>
</body>
</html>