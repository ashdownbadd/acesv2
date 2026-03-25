<?php
require_once 'core/db.php';

try {
    $pdo = (new DB())->connect();

    // The plain text password we want everyone to have
    $plainPassword = "password";

    // Create the secure hash
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    echo "Starting password reset seeder...<br>";

    // Update every single row in the members table
    $sql = "UPDATE members SET password = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$hashedPassword])) {
        $count = $stmt->rowCount();
        echo "Successfully updated <strong>{$count}</strong> members.<br>";
        echo "All member passwords are now set to: <strong>password</strong>";
    } else {
        echo "Failed to update members.";
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}