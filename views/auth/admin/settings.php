<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user = [
    'name' => $_SESSION['name'] ?? 'Admin',
    'role' => ucfirst($_SESSION['role'] ?? 'Staff'),
    'initials' => strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1) . substr(strrchr($_SESSION['name'] ?? ' ', ' '), 1, 1))
];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>


<div class="l-app">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="l-container">
        <main class="l-app__body">
            <div class="c-settings-header" style="margin-bottom: 24px;">
                <a href="?page=dashboard" class="c-back-link">← Back to Dashboard</a>
                <h1 class="c-settings-title">Settings</h1>
            </div>

            <form action="?action=update_profile" method="POST" enctype="multipart/form-data" class="c-settings-grid">
                <div class="c-bento-card">
                    <div class="c-settings-section">
                        <label class="c-settings-label">Profile Picture</label>
                        <div class="c-avatar-upload" id="sav-ring">
                            <img src="assets/img/admin-avatar.jpg" alt="Profile" id="settings-av" class="c-avatar-upload__img">
                            <input type="file" name="avatar" id="sav-file" hidden>
                            <button type="button" class="c-avatar-upload__btn" id="sav-pick-btn">Change</button>
                        </div>
                    </div>
                </div>

                <div class="c-bento-card">
                    <div class="c-settings-section">
                        <label class="c-settings-label">Account Details</label>
                        <input type="text" name="name" class="c-settings-input" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>">
                        <input type="text" name="username" class="c-settings-input" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                        <input type="password" name="password" class="c-settings-input" placeholder="New Password">
                    </div>
                    <button type="submit" class="c-settings-submit" style="margin-top:20px; width:100%;">Save Changes</button>
                </div>
            </form>
        </main>
    </div>
</div>