<?php
require_once __DIR__ . '/../db.php';

$database = new DB();
$mysqli = $database->getConnection();

// Add is_visible column if it doesn't exist
$check = $mysqli->query("SHOW COLUMNS FROM sites LIKE 'is_visible'");
if ($check->num_rows === 0) {
    echo "Adding is_visible column...\n";
    // Default to 1 (visible) for now, or match status? 
    // Plan said: "populate with initial values (UPDATE sites SET is_visible = status)"
    // Let's default to 1 so we don't hide everything by default if status was 0 but should be visible? 
    // The user said "some clients are disabled (status=0) but we need to see them".
    // So if I set is_visible = status, those invalid clients will be hidden.
    // However, the safe bet is to start with is_visible = 1 for ALL sites, or match status.
    // Let's follow the plan: "UPDATE sites SET is_visible = status". 
    // Wait, if status is 0 (disabled), and we want it visible... we might need to manually toggle them later.
    // But for "legacy clients that are really disabled", status=0 and is_visible=0 is correct.
    // For "clients not monitored but visible", status=0 and is_visible=1 is what we want.
    // Initial state: copy status is safer than making everything visible.
    
    $sql = "ALTER TABLE sites ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER status";
    if ($mysqli->query($sql)) {
        echo "Column added successfully.\n";
        
        // Sync is_visible with status initially
        $mysqli->query("UPDATE sites SET is_visible = status");
        echo "Synced is_visible with current status.\n";
    } else {
        die("Error adding column: " . $mysqli->error . "\n");
    }
} else {
    echo "Column is_visible already exists.\n";
}

echo "Migration completed.\n";
?>
