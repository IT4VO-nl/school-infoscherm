<?php
// player.php - PWA Player Interface
require_once 'db_config.php';

$pdo = getDbConnection();
$player_id = $_GET['id'] ?? null;
$claimed = false;
$org_name = '';

if ($player_id) {
    // Check if player is claimed
    $stmt = $pdo->prepare('
        SELECT p.id, p.name, p.organisation_id, o.name as org_name, o.logo
        FROM players p
        LEFT JOIN organisations o ON o.id = p.organisation_id
        WHERE p.player_id = :player_id
    ');
    $stmt->execute([':player_id' => $player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($player && $player['organisation_id']) {
        $claimed = true;
        $org_name = $player['org_name'];
        
        // Update last seen
        $pdo->prepare('UPDATE players SET last_seen = NOW() WHERE player_id = :player_id')
           ->execute([':player_id' => $player_id]);
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $claimed ? htmlspecialchars($org_name) . ' - Infoscherm' : 'IT4VO Infoscherm Player' ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#007cba">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="IT4VO Player">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="player-manifest.json">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="icons/icon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            color: white;
            overflow: hidden;
            cursor: none; /* Hide cursor on TV screens */
        }

        .fullscreen-container {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        /* Unclaimed Player Screen */
        .setup-screen {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(135deg, #007cba 0%, #005a8b 100%);
            text-align: center;
            padding: 40px;
        }

        .setup-content {
            max-width: 800px;
        }

        .setup-content h1 {
            font-size: 4em;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .setup-content .player-id {
            font-size: 8em;
            font-family: monospace;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            padding: 30px 60px;
            border-radius: 20px;
            margin: 40px 0;
            letter-spacing: 10px;
        }

        .setup-content .instructions {
            font-size: 2em;
            line-height: 1.6;
            opacity: 0.9;
        }

        .setup-content .url {
            font-size: 1.8em;
            font-family: monospace;
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
        }

        /* Claimed Player Screen */
        .player-screen {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }

        .header-bar {
            background: rgba(0, 124, 186, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        .org-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .org-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            background: white;
            border-radius: 8px;
            padding: 5px;
        }

        .org-name {
            font-size: 2em;
            font-weight: 600;
        }

        .player-info {
            text-align: right;
            opacity: 0.9;
        }

        .player-name {
            font-size: 1.2em;
            margin-bottom: 5px;
        }

        .player-id-small {
            font-family: monospace;
            font-size: 1em;
            opacity: 0.8;
        }

        .slides-container {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            padding: 60px;
        }

        .slide.active {
            opacity: 1;
        }

        .slide.news {
            background: linear-gradient(135deg, #007cba 0%, #005a8b 100%);
        }

        .slide.absence {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .slide.roster {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        }

        .slide-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .slide-type {
            font-size: 2em;
            opacity: 0.8;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .slide-title {
            font-size: 5em;
            font-weight: 300;
            margin-bottom: 30px;
            line-height: 1.2;
        }

        .slide-subtitle {
            font-size: 2.5em;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .slide-text {
            font-size: 2em;
            line-height: 1.6;
            max-width: 80%;
            margin: 0 auto;
        }

        .system-slides {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .system-slides .slide-title {
            color: #212529;
        }

        .footer-bar {
            background: rgba(0,0,0,0.8);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2em;
        }

        .slide-progress {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .progress-bar {
            width: 200px;
            height: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #007cba;
            width: 0%;
            transition: width 0.1s linear;
        }

        .timestamp {
            opacity: 0.8;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #007cba;
        }

        .loading .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 80px;
            height: 80px;
            animation: spin 1s linear infinite;
            margin-bottom: 30px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-screen {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #dc3545;
            text-align: center;
            padding: 40px;
        }

        .error-content h1 {
            font-size: 3em;
            margin-bottom: 20px;
        }

        .error-content p {
            font-size: 1.5em;
            opacity: 0.9;
        }

        /* TV-optimized styles */
        @media (min-width: 1200px) {
            .slide-title {
                font-size: 6em;
            }
            
            .slide-text {
                font-size: 2.5em;
            }
        }

        /* Mobile fallback */
        @media (max-width: 768px) {
            body {
                cursor: auto;
            }
            
            .setup-content .player-id {
                font-size: 4em;
                padding: 20px 30px;
                letter-spacing: 5px;
            }
            
            .setup-content h1 {
                font-size: 2.5em;
            }
            
            .setup-content .instructions {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
    <div class="fullscreen-container">
        <?php if (!$player_id): ?>
            <!-- No Player ID provided -->
            <div class="setup-screen">
                <div class="setup-content">
                    <h1>🚀 IT4VO School Infoscherm</h1>
                    <p class="instructions">
                        Ga naar onderstaande URL om een Player ID te genereren en dit scherm te registreren:
                    </p>
                    <div class="url">IT4VO.nl/school-infoscherm</div>
                </div>
            </div>
            
        <?php elseif (!$claimed): ?>
            <!-- Player ID provided but not claimed -->
            <div class="setup-screen">
                <div class="setup-content">
                    <h1>📺 Scherm Registreren</h1>
                    <div class="player-id"><?= htmlspecialchars($player_id) ?></div>
                    <p class="instructions">
                        Ga naar <strong>IT4VO.nl/school-infoscherm</strong><br>
                        en gebruik deze code om dit scherm te registreren bij je organisatie.
                    </p>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Claimed player - show content -->
            <div class="player-screen">
                <div class="header-bar">
                    <div class="org-info">
                        <?php if ($player['logo']): ?>
                            <img src="<?= htmlspecialchars($player['logo']) ?>" alt="Logo" class="org-logo">
                        <?php endif; ?>
                        <div class="org-name"><?= htmlspecialchars($org_name) ?></div>
                    </div>
                    <div class="player-info">
                        <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                        <div class="player-id-small"><?= htmlspecialchars($player_id) ?></div>
                    </div>
                </div>
                
                <div class="slides-container" id="slidesContainer">
                    <div class="loading">
                        <div>
                            <div class="spinner"></div>
                            <div>Slides laden...</div>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bar">
                    <div class="slide-progress">
                        <span id="slideCounter">1 / 1</span>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>
                    <div class="timestamp" id="timestamp"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($claimed): ?>
    <script>
        class SlidePlayer {
            constructor(playerId) {
                this.playerId = playerId;
                this.slides = [];
                this.systemSlides = [];
                this.currentSlideIndex = 0;
                this.slideTimer = null;
                this.progressTimer = null;
                this.refreshInterval = null;
                this.heartbeatInterval = null;
                
                this.init();
            }
            
            async init() {
                this.updateTimestamp();
                this.startHeartbeat();
                this.startRefreshTimer();
                await this.loadSlides();
                this.startSlideshow();
                
                // Update timestamp every second
                setInterval(() => this.updateTimestamp(), 1000);
            }
            
            async loadSlides() {
                try {
                    // Load organisation slides
                    const slidesResponse = await fetch(`api/player_content.php?player_id=${this.playerId}`);
                    if (slidesResponse.ok) {
                        const slidesData = await slidesResponse.json();
                        this.slides = slidesData.slides || [];
                    }
                    
                    // Load system slides
                    const systemResponse = await fetch(`api/system_slides.php`);
                    if (systemResponse.ok) {
                        const systemData = await systemResponse.json();
                        this.systemSlides = systemData || [];
                    }
                    
                    this.renderSlides();
                } catch (error) {
                    console.error('Error loading slides:', error);
                    this.showError('Fout bij laden van slides');
                }
            }
            
            renderSlides() {
                const container = document.getElementById('slidesContainer');
                
                if (this.slides.length === 0 && this.systemSlides.length === 0) {
                    container.innerHTML = `
                        <div class="slide active">
                            <div class="slide-content">
                                <div class="slide-type">Welkom</div>
                                <div class="slide-title">IT4VO School Infoscherm</div>
                                <div class="slide-text">
                                    Dit scherm is succesvol geregistreerd!<br>
                                    Voeg slides toe via het admin panel om content te tonen.
                                </div>
                            </div>
                        </div>
                    `;
                    return;
                }
                
                const allSlides = [...this.systemSlides, ...this.slides];
                
                container.innerHTML = allSlides.map((slide, index) => {
                    const isSystem = this.systemSlides.includes(slide);
                    const slideClass = isSystem ? 'system-slides' : slide.type;
                    
                    return `
                        <div class="slide ${slideClass} ${index === 0 ? 'active' : ''}" data-duration="${slide.display_duration || 5}">
                            <div class="slide-content">
                                <div class="slide-type">${this.getTypeLabel(slide.type, isSystem)}</div>
                                ${slide.title ? `<div class="slide-title">${slide.title}</div>` : ''}
                                ${slide.subtitle ? `<div class="slide-subtitle">${slide.subtitle}</div>` : ''}
                                <div class="slide-text">${slide.content || ''}</div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                // Update counter
                document.getElementById('slideCounter').textContent = 
                    `1 / ${allSlides.length}`;
            }
            
            getTypeLabel(type, isSystem) {
                if (isSystem) return '🔔 Systeem';
                
                const labels = {
                    'news': '📰 Nieuws',
                    'absence': '🏥 Ziekte/Verlof', 
                    'roster': '📅 Roosterwijziging'
                };
                return labels[type] || type;
            }
            
            startSlideshow() {
                const slides = document.querySelectorAll('.slide');
                if (slides.length <= 1) return;
                
                this.showSlide(0);
            }
            
            showSlide(index) {
                const slides = document.querySelectorAll('.slide');
                if (slides.length === 0) return;
                
                // Hide all slides
                slides.forEach(slide => slide.classList.remove('active'));
                
                // Show current slide
                const currentSlide = slides[index];
                currentSlide.classList.add('active');
                
                // Update counter
                document.getElementById('slideCounter').textContent = 
                    `${index + 1} / ${slides.length}`;
                
                // Get duration
                const duration = parseInt(currentSlide.dataset.duration) || 5;
                
                // Start progress bar
                this.startProgress(duration);
                
                // Set timer for next slide
                this.slideTimer = setTimeout(() => {
                    this.currentSlideIndex = (index + 1) % slides.length;
                    this.showSlide(this.currentSlideIndex);
                }, duration * 1000);
            }
            
            startProgress(duration) {
                const progressFill = document.getElementById('progressFill');
                let progress = 0;
                const increment = 100 / (duration * 10); // Update every 100ms
                
                progressFill.style.width = '0%';
                
                this.progressTimer = setInterval(() => {
                    progress += increment;
                    progressFill.style.width = Math.min(progress, 100) + '%';
                    
                    if (progress >= 100) {
                        clearInterval(this.progressTimer);
                    }
                }, 100);
            }
            
            startHeartbeat() {
                // Send heartbeat every 2 minutes
                this.heartbeatInterval = setInterval(async () => {
                    try {
                        await fetch(`api/player_heartbeat.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                player_id: this.playerId
                            })
                        });
                    } catch (error) {
                        console.error('Heartbeat failed:', error);
                    }
                }, 120000);
            }
            
            startRefreshTimer() {
                // Refresh content every 5 minutes
                this.refreshInterval = setInterval(async () => {
                    await this.loadSlides();
                    if (document.querySelectorAll('.slide').length > 0) {
                        this.startSlideshow();
                    }
                }, 300000);
            }
            
            updateTimestamp() {
                const now = new Date();
                const timeString = now.toLocaleString('nl-NL', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('timestamp').textContent = timeString;
            }
            
            showError(message) {
                document.getElementById('slidesContainer').innerHTML = `
                    <div class="error-screen">
                        <div class="error-content">
                            <h1>⚠️ Fout</h1>
                            <p>${message}</p>
                        </div>
                    </div>
                `;
            }
        }
        
        // Initialize player
        const player = new SlidePlayer('<?= htmlspecialchars($player_id) ?>');
        
        // Register service worker for PWA
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('player-sw.js')
                .then(registration => {
                    console.log('SW registered:', registration);
                })
                .catch(error => {
                    console.log('SW registration failed:', error);
                });
        }
        
        // Prevent context menu and selection
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
        
        // Handle visibility change (screen wake/sleep)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                // Screen became visible - refresh content
                player.loadSlides();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>