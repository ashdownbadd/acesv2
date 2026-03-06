<?php
require_once 'db.php';
$db = (new DB())->connect();

// Data Pools
$firstNames  = ['James', 'Mary', 'Robert', 'Patricia', 'John', 'Jennifer', 'Michael', 'Linda'];
$middleNames = ['Alexander', 'Bernardo', 'Catherine', 'Dominic', 'Evangeline', 'Fernando', 'Gabriel'];
$lastNames   = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
$statuses    = ['Active', 'Active', 'Inactive', 'Overdue'];
$civStatus   = ['Single', 'Married', 'Widowed', 'Separated'];

try {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE members");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    $db->beginTransaction();

    for ($i = 1; $i <= 100; $i++) {
        $fName = $firstNames[array_rand($firstNames)];
        $mName = $middleNames[array_rand($middleNames)];
        $lName = $lastNames[array_rand($lastNames)];
        
        $sql = "INSERT INTO members (
            member_id, membership_type, username, password, first_name, middle_name, last_name, 
            email, profile_picture, prefix, suffix, birthdate, approval_date, 
            balance, status, is_mgs, remarks, role_id, phone_number, telephone_number, 
            civil_status, address
        ) VALUES (
            :mid, :type, :user, :pass, :fname, :mname, :lname, 
            :email, :pic, :pre, :suf, :bday, :appr, 
            :bal, :status, :mgs, :rem, :role, :phone, :tel, 
            :civ, :addr
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':mid'    => $i, // Stored as INT 1, 2, 3...
            ':type'   => ($i <= 50) ? 'Regular' : 'Associate',
            ':user'   => strtolower($fName . $i),
            ':pass'   => password_hash('member123', PASSWORD_DEFAULT),
            ':fname'  => $fName,
            ':mname'  => $mName, // Full middle name
            ':lname'  => $lName,
            ':email'  => strtolower($fName . "." . $lName . $i . "@example.com"),
            ':pic'    => 'default.png',
            ':pre'    => ($i % 10 == 0) ? 'Rev.' : '',
            ':suf'    => ($i % 15 == 0) ? 'Jr.' : '',
            ':bday'   => rand(1970, 2005) . "-" . rand(1, 12) . "-" . rand(1, 28),
            ':appr'   => date('Y-m-d H:i:s'),
            ':bal'    => rand(1000, 50000),
            ':status' => $statuses[array_rand($statuses)],
            ':mgs'    => rand(0, 1),
            ':rem'    => 'System generated member profile.',
            ':role'   => 2,
            ':phone'  => '09' . rand(100000000, 999999999),
            ':tel'    => '8' . rand(1000000, 9999999),
            ':civ'    => $civStatus[array_rand($civStatus)],
            ':addr'   => $i . " Main St, Barangay " . rand(1, 20) . ", Metro Manila"
        ]);
    }

    $db->commit();
    echo "Successfully seeded 100 detailed members.";
} catch (Exception $e) {
    $db->rollBack();
    die("Error: " . $e->getMessage());
}