<?php
// includes/header.php - Header component voor alle pagina's
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_logged_in = isset($_SESSION['admin_id']);
?>
<header class="main-header">
    <div class="header-container">
        <div class="header-brand">
            <a href="index.php" class="brand-link">
                <div class="brand-icon">🏫</div>
                <div class="brand-text">
                    <div class="brand-name">IT4VO</div>
                    <div class="brand-subtitle">School Infoscherm</div>
                </div>
            </a>
        </div>
        
        <nav class="header-nav">
            <?php if ($is_logged_in): ?>
                <!-- Ingelogde gebruiker menu -->
                <div class="nav-group">
                    <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                        📊 Dashboard
                    </a>
                    <a href="slides.php" class="nav-link <?= $current_page === 'slides' ? 'active' : '' ?>">
                        📝 Slides
                    </a>
                    <a href="players.php" class="nav-link <?= $current_page === 'players' ? 'active' : '' ?>">
                        📺 Players
                    </a>
                    <a href="groups.php" class="nav-link <?= $current_page === 'groups' ? 'active' : '' ?>">
                        👥 Groepen
                    </a>
                </div>
                
                <div class="nav-group">
                    <a href="donate.php" class="nav-link nav-donate">
                        💝 Doneer
                    </a>
                    <a href="logout.php" class="nav-link nav-logout">
                        🚪 Uitloggen
                    </a>
                </div>
            <?php else: ?>
                <!-- Publieke menu -->
                <div class="nav-group">
                    <a href="index.php" class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>">
                        🏠 Home
                    </a>
                    <a href="donate.php" class="nav-link nav-donate">
                        💝 Doneer
                    </a>
                </div>
                
                <div class="nav-group">
                    <a href="register_org.php" class="nav-btn nav-btn-primary">
                        🚀 Registreren
                    </a>
                    <a href="login.php" class="nav-btn nav-btn-secondary">
                        🔐 Inloggen
                    </a>
                </div>
            <?php endif; ?>
        </nav>
        
        <!-- Mobile menu toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    
    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobileMenu">
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" class="mobile-nav-link">📊 Dashboard</a>
            <a href="slides.php" class="mobile-nav-link">📝 Slides</a>
            <a href="players.php" class="mobile-nav-link">📺 Players</a>
            <a href="groups.php" class="mobile-nav-link">👥 Groepen</a>
            <div class="mobile-nav-divider"></div>
            <a href="donate.php" class="mobile-nav-link">💝 Doneer</a>
            <a href="logout.php" class="mobile-nav-link">🚪 Uitloggen</a>
        <?php else: ?>
            <a href="index.php" class="mobile-nav-link">🏠 Home</a>
            <a href="donate.php" class="mobile-nav-link">💝 Doneer</a>
            <div class="mobile-nav-divider"></div>
            <a href="register_org.php" class="mobile-nav-link mobile-nav-primary">🚀 Registreren</a>
            <a href="login.php" class="mobile-nav-link">🔐 Inloggen</a>
        <?php endif; ?>
    </div>
</header>

<style>
.main-header {
    background: linear-gradient(135deg, #007cba 0%, #005a8b 100%);
    box-shadow: 0 2px 15px rgba(0, 124, 186, 0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 3px solid rgba(255, 255, 255, 0.1);
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 80px;
}

.header-brand .brand-link {
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    color: white;
    transition: transform 0.3s ease;
}

.header-brand .brand-link:hover {
    transform: translateY(-2px);
}

.brand-icon {
    font-size: 2.5em;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

.brand-name {
    font-size: 1.8em;
    font-weight: bold;
    letter-spacing: 1px;
}

.brand-subtitle {
    font-size: 0.9em;
    opacity: 0.9;
    font-weight: 300;
}

.header-nav {
    display: flex;
    align-items: center;
    gap: 30px;
}

.nav-group {
    display: flex;
    align-items: center;
    gap: 15px;
}

.nav-link {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.95em;
}

.nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 600;
}

.nav-donate {
    background: linear-gradient(45deg, #ff6b6b, #ee5a52) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.nav-donate:hover {
    background: linear-gradient(45deg, #ff5252, #e53935) !important;
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}

.nav-logout {
    opacity: 0.8;
}

.nav-logout:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    opacity: 1;
}

.nav-btn {
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95em;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.nav-btn-primary {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
}

.nav-btn-primary:hover {
    background: linear-gradient(45deg, #218838, #1abc9c);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.nav-btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.nav-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
}

.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.mobile-menu-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background: white;
    margin: 3px 0;
    border-radius: 2px;
    transition: 0.3s;
}

.mobile-menu {
    display: none;
    background: #005a8b;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.mobile-nav-link {
    display: block;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: background 0.3s ease;
}

.mobile-nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.mobile-nav-primary {
    background: linear-gradient(45deg, #28a745, #20c997);
    font-weight: 600;
}

.mobile-nav-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
    margin: 10px 20px;
}

@media (max-width: 768px) {
    .header-container {
        height: 70px;
        padding: 0 15px;
    }
    
    .brand-name {
        font-size: 1.4em;
    }
    
    .brand-subtitle {
        font-size: 0.8em;
    }
    
    .header-nav {
        display: none;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .mobile-menu.show {
        display: block;
    }
}

@media (max-width: 480px) {
    .header-container {
        height: 60px;
    }
    
    .brand-icon {
        font-size: 2em;
    }
    
    .brand-name {
        font-size: 1.2em;
    }
}
</style>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    menu.classList.toggle('show');
    toggle.classList.toggle('active');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('mobileMenu');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (!toggle.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.remove('show');
        toggle.classList.remove('active');
    }
});
</script>