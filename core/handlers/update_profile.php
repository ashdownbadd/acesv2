<?php
session_start();
require_once '../db.php';
$pdo = (new DB())->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['member_id'])) {
    $mid = $_SESSION['member_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // 1. Update Username
    $stmt = $pdo->prepare("UPDATE members SET username = ? WHERE id = ?");
    $stmt->execute([$username, $mid]);

    // 2. Handle Password Change
    if (!empty($password)) {
        if ($password === $confirm) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE members SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $mid]);
        }
    }

    header("Location: ../../views/member/profile.php?success=1");
    exit();
}