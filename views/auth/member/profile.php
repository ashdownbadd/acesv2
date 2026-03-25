<?php
// Database is already connected in index.php as $db
$memberId = $_SESSION['member_id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$memberId]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("Member record not found.");
}

$status = $_GET['status'] ?? '';
$initial  = strtoupper(substr($m['first_name'] ?? 'U', 0, 1));
$fullName = htmlspecialchars(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')));
$memberIdDisplay = str_pad($m['id'] ?? 0, 5, '0', STR_PAD_LEFT);
?>

<div class="l-container" style="padding-top: 110px; padding-bottom: 60px;">
    <main class="l-settings-wrapper">

        <?php if ($status === 'success'): ?>
            <div style="background: var(--ok); color: #fff; padding: 1rem; border-radius: 50px; margin-bottom: 24px; font-family: var(--font-heading); font-weight: 700; text-align: center; font-size: 0.85rem;">
                CHANGES SAVED SUCCESSFULLY
            </div>
        <?php endif; ?>

        <div class="c-settings-grid">

            <div class="c-bento-card c-bento-card--avatar" style="color: white;">
                <div class="c-avatar-wrapper">
                    <div class="c-avatar-img c-avatar-placeholder" style="display: flex; background: rgba(255,255,255,0.2); font-family: var(--font-heading); font-size: 3rem; font-weight: 800; color: white; border: 3px solid white;">
                        <?= $initial ?>
                    </div>
                </div>

                <h2 class="c-avatar-name" style="color: white;"><?= $fullName ?></h2>
                <p class="c-avatar-id" style="color: white; opacity: 0.9;">ID: #<?= $memberIdDisplay ?></p>

                <div style="width: 100%; height: 1px; background: rgba(255,255,255,0.2); margin: 20px 0;"></div>

                <div style="width: 100%; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem;">
                        <span style="opacity: 0.8; font-weight: 700;">TYPE</span>
                        <span style="font-weight: 800;"><?= strtoupper($m['membership_type'] ?? 'REGULAR') ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem;">
                        <span style="opacity: 0.8; font-weight: 700;">STATUS</span>
                        <span style="font-weight: 800;"><?= htmlspecialchars($m['civil_status'] ?? '—') ?></span>
                    </div>
                </div>
            </div>

            <div class="c-bento-card c-bento-card--details">

                <div class="c-settings-section" style="margin-bottom: 32px;">
                    <label class="c-settings-label">Financial Overview</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 8px;">
                        <div style="background: var(--bg); border: 1px solid var(--border); padding: 16px; border-radius: 18px;">
                            <span class="c-settings-label" style="font-size: 0.55rem;">Total Balance</span>
                            <div style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 800; color: var(--gold); margin-top: 4px;">
                                ₱<?= number_format($m['balance'] ?? 0, 2) ?>
                            </div>
                        </div>
                        <div style="background: var(--bg); border: 1px solid var(--border); padding: 16px; border-radius: 18px;">
                            <span class="c-settings-label" style="font-size: 0.55rem;">Account Status</span>
                            <div style="margin-top: 8px;">
                                <span style="background: var(--gold-dim); color: var(--gold); padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; border: 1px solid var(--gold);">
                                    <?= strtoupper($m['status'] ?? 'ACTIVE') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="index.php?action=update_member_credentials" method="POST" class="c-settings-section">
                    <label class="c-settings-label">Account Access</label>

                    <div style="margin-top: 8px;">
                        <label class="c-settings-label">Username</label>
                        <input type="text" name="username" class="c-settings-input"
                            value="<?= htmlspecialchars($m['username'] ?? '') ?>" required>
                    </div>

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

                    <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                        <button type="submit" class="c-settings-submit">
                            Save Changes
                        </button>
                    </div>
                </form>

            </div>

        </div>
    </main>
</div>