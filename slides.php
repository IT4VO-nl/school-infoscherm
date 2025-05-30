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

if (!$org_info['is_verified']) {
    header('Location: dashboard.php');
    exit;
}

// Get player groups for targeting
$stmt = $pdo->prepare('SELECT id, name, description FROM player_groups WHERE organisation_id = :org_id ORDER BY name');
$stmt->execute([':org_id' => $_SESSION['organisation_id']]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slides Beheren - <?= htmlspecialchars($org_info['name']) ?></title>
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9em;
        }

        .slides-container {
            display: grid;
            gap: 20px;
        }

        .slide-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .slide-card:hover {
            transform: translateY(-2px);
        }

        .slide-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .slide-type {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .type-news { background: #d4edda; color: #155724; }
        .type-absence { background: #f8d7da; color: #721c24; }
        .type-roster { background: #d1ecf1; color: #0c5460; }

        .slide-content {
            padding: 20px;
        }

        .slide-title {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .slide-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .slide-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
            color: #888;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .slide-actions {
            display: flex;
            gap: 10px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }

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
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
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

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .slide-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .slide-actions {
                justify-content: center;
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
                <span>📝 Slides</span>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">← Terug</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">📝 Slides Beheren</h1>
            <button class="btn btn-primary" onclick="openSlideModal()">
                ➕ Nieuwe Slide
            </button>
        </div>

        <div class="slides-container" id="slidesContainer">
            <!-- Slides worden hier geladen via JavaScript -->
        </div>
    </div>

    <!-- Slide Modal -->
    <div id="slideModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nieuwe Slide</h2>
                <span class="close" onclick="closeSlideModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="slideForm">
                    <input type="hidden" id="slideId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="slideName">Slide Naam</label>
                            <input type="text" id="slideName" name="name" class="form-control" 
                                   placeholder="bijv. Nieuws Week 23">
                        </div>
                        
                        <div class="form-group">
                            <label for="slideType">Type *</label>
                            <select id="slideType" name="type" class="form-control" required>
                                <option value="">Selecteer type...</option>
                                <option value="news">📰 Nieuws</option>
                                <option value="absence">🏥 Ziekte/Verlof</option>
                                <option value="roster">📅 Roosterwijziging</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="slideTitle">Titel</label>
                        <input type="text" id="slideTitle" name="title" class="form-control" 
                               placeholder="Hoofdtitel van de slide">
                    </div>

                    <div class="form-group">
                        <label for="slideSubtitle">Ondertitel</label>
                        <input type="text" id="slideSubtitle" name="subtitle" class="form-control" 
                               placeholder="Datum of korte omschrijving">
                    </div>

                    <div class="form-group">
                        <label for="slideContent">Inhoud</label>
                        <textarea id="slideContent" name="content" class="form-control" 
                                  placeholder="Hoofdinhoud van de slide..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="displayDuration">Weergavetijd (seconden)</label>
                            <input type="number" id="displayDuration" name="display_duration" 
                                   class="form-control" value="5" min="1" max="60">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="isActive" name="is_active" checked>
                                Actieve slide
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="publishStart">Publiceren vanaf</label>
                            <input type="datetime-local" id="publishStart" name="publish_start" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="publishEnd">Publiceren tot</label>
                            <input type="datetime-local" id="publishEnd" name="publish_end" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="isContinuous" name="is_continuous">
                            Doorlopend (negeert einddatum)
                        </label>
                    </div>

                    <?php if (!empty($groups)): ?>
                    <div class="form-group">
                        <label>Tonen aan groepen (leeg = alle groepen)</label>
                        <div class="checkbox-group">
                            <?php foreach ($groups as $group): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="target_groups[]" 
                                           value="<?= $group['id'] ?>" 
                                           id="group_<?= $group['id'] ?>">
                                    <label for="group_<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="button" class="btn btn-secondary" onclick="closeSlideModal()">Annuleren</button>
                        <button type="submit" class="btn btn-success">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let slides = [];

        // Load slides on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSlides();
        });

        async function loadSlides() {
            try {
                const response = await fetch('api/slides.php');
                const data = await response.json();
                
                if (response.ok) {
                    slides = data;
                    renderSlides();
                } else {
                    console.error('Error loading slides:', data.error);
                    showError('Fout bij laden van slides: ' + data.error);
                }
            } catch (error) {
                console.error('Network error:', error);
                showError('Netwerkfout bij laden van slides');
            }
        }

        function renderSlides() {
            const container = document.getElementById('slidesContainer');
            
            if (slides.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">📄</div>
                        <h3>Nog geen slides</h3>
                        <p>Maak je eerste slide om te beginnen</p>
                        <button class="btn btn-primary" onclick="openSlideModal()">➕ Eerste Slide Maken</button>
                    </div>
                `;
                return;
            }

            container.innerHTML = slides.map(slide => `
                <div class="slide-card">
                    <div class="slide-header">
                        <div>
                            <span class="slide-type type-${slide.type}">
                                ${getTypeLabel(slide.type)}
                            </span>
                            <span class="status-badge ${slide.is_active == 1 ? 'status-active' : 'status-inactive'}">
                                ${slide.is_active == 1 ? '✅ Actief' : '❌ Inactief'}
                            </span>
                        </div>
                        <div class="slide-actions">
                            <button class="btn btn-primary btn-sm" onclick="editSlide(${slide.id})">
                                ✏️ Bewerken
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteSlide(${slide.id})">
                                🗑️ Verwijderen
                            </button>
                        </div>
                    </div>
                    <div class="slide-content">
                        <div class="slide-title">${slide.title || slide.name || 'Geen titel'}</div>
                        ${slide.subtitle ? `<div style="color: #666; margin-bottom: 10px;">${slide.subtitle}</div>` : ''}
                        <div class="slide-text">${slide.content || 'Geen inhoud'}</div>
                        <div class="slide-meta">
                            <span>
                                Weergavetijd: ${slide.display_duration}s
                                ${slide.target_groups ? ` | Groepen: ${slide.target_groups}` : ' | Alle groepen'}
                            </span>
                            <span>Gewijzigd: ${formatDate(slide.updated_at)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function getTypeLabel(type) {
            const labels = {
                'news': '📰 Nieuws',
                'absence': '🏥 Ziekte/Verlof',
                'roster': '📅 Roosterwijziging'
            };
            return labels[type] || type;
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('nl-NL');
        }

        function openSlideModal(slideId = null) {
            const modal = document.getElementById('slideModal');
            const form = document.getElementById('slideForm');
            const title = document.getElementById('modalTitle');

            if (slideId) {
                const slide = slides.find(s => s.id == slideId);
                if (slide) {
                    title.textContent = 'Slide Bewerken';
                    populateForm(slide);
                }
            } else {
                title.textContent = 'Nieuwe Slide';
                form.reset();
                document.getElementById('slideId').value = '';
                document.getElementById('isActive').checked = true;
                document.getElementById('displayDuration').value = 5;
            }

            modal.style.display = 'block';
        }

        function closeSlideModal() {
            document.getElementById('slideModal').style.display = 'none';
        }

        function editSlide(slideId) {
            openSlideModal(slideId);
        }

        function populateForm(slide) {
            document.getElementById('slideId').value = slide.id;
            document.getElementById('slideName').value = slide.name || '';
            document.getElementById('slideType').value = slide.type;
            document.getElementById('slideTitle').value = slide.title || '';
            document.getElementById('slideSubtitle').value = slide.subtitle || '';
            document.getElementById('slideContent').value = slide.content || '';
            document.getElementById('displayDuration').value = slide.display_duration;
            document.getElementById('isActive').checked = slide.is_active == 1;
            document.getElementById('publishStart').value = slide.publish_start ? slide.publish_start.replace(' ', 'T') : '';
            document.getElementById('publishEnd').value = slide.publish_end ? slide.publish_end.replace(' ', 'T') : '';
            document.getElementById('isContinuous').checked = slide.is_continuous == 1;
        }

        async function deleteSlide(slideId) {
            if (!confirm('Weet je zeker dat je deze slide wilt verwijderen?')) {
                return;
            }

            try {
                const response = await fetch(`api/slides.php?id=${slideId}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    showSuccess('Slide verwijderd');
                    loadSlides();
                } else {
                    const data = await response.json();
                    showError('Fout bij verwijderen: ' + data.error);
                }
            } catch (error) {
                showError('Netwerkfout bij verwijderen');
            }
        }

        // Form submission
        document.getElementById('slideForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Handle checkboxes
            data.is_active = document.getElementById('isActive').checked;
            data.is_continuous = document.getElementById('isContinuous').checked;
            
            // Handle multiple checkboxes for groups
            const selectedGroups = Array.from(document.querySelectorAll('input[name="target_groups[]"]:checked'))
                                       .map(cb => cb.value);
            data.target_groups = selectedGroups;

            try {
                const response = await fetch('api/slides.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    showSuccess(data.id ? 'Slide bijgewerkt' : 'Slide aangemaakt');
                    closeSlideModal();
                    loadSlides();
                } else {
                    showError('Fout bij opslaan: ' + result.error);
                }
            } catch (error) {
                showError('Netwerkfout bij opslaan');
            }
        });

        function showSuccess(message) {
            // Simple success notification - you can enhance this
            alert('✅ ' + message);
        }

        function showError(message) {
            // Simple error notification - you can enhance this
            alert('❌ ' + message);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('slideModal');
            if (event.target == modal) {
                closeSlideModal();
            }
        }
    </script>
</body>
</html>