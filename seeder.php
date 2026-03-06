<?php
// Ensure this is run in a secure environment
require_once __DIR__ . '/core/db.php';

$dbClass = new DB();
$db = $dbClass->connect();

$statuses = ['Active', 'Deceased', 'Delisted', 'On-Hold', 'Overdue', 'Under Litigation'];

try {
    // Start transaction for safety
    $db->beginTransaction();

    foreach ($statuses as $status) {
        // Just for visual verification in the terminal
        echo "Updating random members to: $status\n";
    }

    // Assign a random status to every member
    // Using MySQL's ELT and RAND functions for efficient bulk randomization
    $sql = "UPDATE members 
            SET status = ELT(
                FLOOR(RAND() * 6) + 1, 
                'Active', 'Deceased', 'Delisted', 'On-Hold', 'Overdue', 'Under Litigation'
            )";

    $db->exec($sql);
    
    $db->commit();
    echo "Seed completed successfully: All members randomized.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}