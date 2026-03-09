<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<div class="l-container">
    <main class="l-app__body l-settings-wrapper">
        <div class="c-settings-header" style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
            <a href="?page=dashboard" class="c-back-btn">← Back</a>
        </div>

        <form id="settings-form" action="?action=update_profile" method="POST" enctype="multipart/form-data" class="c-settings-grid">

            <div class="c-bento-card c-bento-card--avatar">
                <div class="c-avatar-wrapper">

                    <img src="/acesv2/assets/img/uploads/<?= htmlspecialchars($_SESSION['avatar'] ?? '') ?>"
                        alt="Profile"
                        class="c-avatar-img"
                        style="<?= empty($_SESSION['avatar']) ? 'display: none;' : 'display: block;' ?>">

                    <?php if (empty($_SESSION['avatar'])): ?>
                        <div class="c-avatar-img c-avatar-placeholder" style="display: flex;"></div>
                    <?php endif; ?>

                    <input type="file" name="avatar" id="avatar-upload" accept="image/*">
                    <label for="avatar-upload" class="c-avatar-camera">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </label>
                </div>

                <h2 class="c-avatar-name"><?= htmlspecialchars($_SESSION['role'] ?? 'User') ?></h2>
                <p class="c-avatar-id">ID: <?= htmlspecialchars($_SESSION['user_id']) ?></p>
            </div>


            <!-- Account Details -->
            <div class="c-bento-card c-bento-card--details">
                <div class="c-settings-section">

                    <label class="c-settings-label">Account Details</label>

                    <label class="c-settings-label">Full Name</label>
                    <input type="text" name="name" class="c-settings-input" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>">

                    <label class="c-settings-label">Username</label>
                    <input type="text" name="username" class="c-settings-input" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="c-settings-label">New Password</label>
                            <input type="password" name="password" class="c-settings-input" placeholder="••••••••">
                        </div>

                        <div>
                            <label class="c-settings-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="c-settings-input" placeholder="••••••••">
                        </div>
                    </div>

                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                    <button type="submit" id="save-btn" class="c-settings-submit is-disabled" disabled>
                        Save Changes
                    </button>
                </div>

            </div>

        </form>
    </main>
</div>