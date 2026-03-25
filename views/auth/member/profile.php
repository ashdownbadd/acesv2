<?php
// Database is already connected in index.php as $db
$memberId = $_SESSION['member_id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$memberId]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("Member record not found.");
}

$initial = strtoupper(substr($m['first_name'], 0, 1));
?>

<main class="l-container" style="padding-top: 120px; padding-bottom: 60px;">
    <div class="profile-container">

        <div class="bento-card profile-header-card">
            <div class="profile-avatar-big"><?= $initial ?></div>
            <h2><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></h2>
            <span class="status-pill"><?= htmlspecialchars($m['membership_type']) ?></span>

            <div style="margin-top: 2rem; width: 100%;">
                <div class="stat-item" style="text-align: left;">
                    <span class="stat-label">Member ID</span>
                    <span style="font-weight: 700;">#<?= str_pad($m['id'], 5, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">

            <div class="bento-card">
                <h3 style="font-family: 'Syne'; margin-bottom: 1rem;">Financial Overview</h3>
                <div class="bento-grid-inner">
                    <div class="stat-item">
                        <span class="stat-label">Total Balance</span>
                        <div class="stat-value" style="color: var(--accent-color);">
                            ₱<?= number_format($m['balance'], 2) ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Account Status</span>
                        <div class="stat-value" style="font-size: 1.2rem;">
                            <?= strtoupper($m['status']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bento-card">
                <h3 style="font-family: 'Syne'; margin-bottom: 1.5rem;">Account Access</h3>
                <form action="index.php?action=update_member_credentials" method="POST">
                    <div class="bento-grid-inner" style="margin-top: 0;">
                        <div class="form-group">
                            <label class="stat-label">Username</label>
                            <input type="text" name="username" class="c-login__input" value="<?= htmlspecialchars($m['username']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="stat-label">New Password</label>
                            <input type="password" name="password" class="c-login__input" placeholder="Leave blank to keep current">
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                        <button type="submit" class="c-btn c-btn--primary" style="width: 100%; border-radius: 12px;">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</main>