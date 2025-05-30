<?php
echo "Debug API Status:<br>";
echo "player_groups.php: " . (file_exists('api/player_groups.php') ? '✅' : '❌') . "<br>";
echo "player_content.php: " . (file_exists('api/player_content.php') ? '✅' : '❌') . "<br>";
echo "player_heartbeat.php: " . (file_exists('api/player_heartbeat.php') ? '✅' : '❌') . "<br>";
echo "system_slides.php: " . (file_exists('api/system_slides.php') ? '✅' : '❌') . "<br>";

// Test database
try {
    require_once 'db_config.php';
    $pdo = getDbConnection();
    echo "Database: ✅<br>";
} catch (Exception $e) {
    echo "Database: ❌ " . $e->getMessage() . "<br>";
}
?>