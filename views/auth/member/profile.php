<?php
// Database connection inherited from index.php
$memberId = $_SESSION['member_id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$memberId]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("Member record not found.");
}

$status_msg      = $_GET['status'] ?? '';
$initial         = strtoupper(substr($m['first_name'] ?? 'U', 0, 1));
$fullName        = htmlspecialchars(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')));
$memberIdDisplay = str_pad($m['id'] ?? 0, 4, '0', STR_PAD_LEFT);
?>


<div id="mp-root">
    <div class="mp-page">

        <!-- Top bar -->
        <div class="mp-topbar">
            <div>
                <?php if ($status_msg === 'success'): ?>
                    <span class="mp-toast">Changes saved</span>
                <?php endif; ?>
            </div>
            <button type="button" id="mp-edit-btn" class="mp-btn-edit">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit Profile
            </button>
        </div>

        <form id="mp-form" action="index.php?action=update_member_credentials" method="POST">
            <div class="mp-grid">

                <!-- ── Identity sidebar ── -->
                <div class="mp-card mp-identity">
                    <div class="mp-avatar"><?= $initial ?></div>
                    <div class="mp-identity__name"><?= $fullName ?></div>
                    <div class="mp-identity__id">#<?= $memberIdDisplay ?></div>
                    <div class="mp-divider"></div>
                    <div class="mp-pill-row">
                        <div class="mp-pill">
                            <span class="mp-pill__label">Type</span>
                            <span class="mp-pill__val"><?= htmlspecialchars($m['membership_type'] ?? 'Regular') ?></span>
                        </div>
                        <div class="mp-pill">
                            <span class="mp-pill__label">Status</span>
                            <span class="mp-pill__val"><?= htmlspecialchars($m['status'] ?? 'Active') ?></span>
                        </div>
                    </div>
                </div>

                <!-- ── Right column ── -->
                <div class="mp-right">

                    <!-- Financial Overview -->
                    <div class="mp-card">
                        <div class="mp-section-label">Financial Overview</div>
                        <div class="mp-balance-wrap">
                            <span class="mp-currency">₱</span>
                            <span class="mp-amount"><?= number_format($m['balance'] ?? 0, 2) ?></span>
                        </div>
                        <div class="mp-subtext">Current outstanding balance</div>
                    </div>

                    <!-- Account Access -->
                    <div class="mp-card">
                        <div class="mp-section-label">Account Access</div>
                        <div class="mp-row-2">
                            <div class="mp-field">
                                <label>Username</label>
                                <input type="text" name="username" class="mp-input mp-editable"
                                    value="<?= htmlspecialchars($m['username'] ?? '') ?>"
                                    autocomplete="username" disabled required>
                            </div>
                            <div class="mp-field">
                                <label>New Password</label>
                                <input type="password" name="password" class="mp-input mp-editable"
                                    placeholder="Leave blank to keep current"
                                    autocomplete="new-password" disabled>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="mp-card">
                        <div class="mp-section-label">Contact Information</div>
                        <div class="mp-field">
                            <label>Email Address</label>
                            <input type="email" name="email" class="mp-input mp-editable"
                                value="<?= htmlspecialchars($m['email'] ?? '') ?>" disabled>
                        </div>
                        <div class="mp-row-2 mp-mt">
                            <div class="mp-field">
                                <label>Mobile 1</label>
                                <input type="text" name="phone_number" class="mp-input mp-editable"
                                    value="<?= htmlspecialchars($m['phone_number'] ?? '') ?>" disabled>
                            </div>
                            <div class="mp-field">
                                <label>Mobile 2</label>
                                <input type="text" name="phone_number_2" class="mp-input mp-editable"
                                    value="<?= htmlspecialchars($m['phone_number_2'] ?? '') ?>" disabled>
                            </div>
                        </div>
                        <div class="mp-row-2 mp-mt">
                            <div class="mp-field">
                                <label>Landline 1</label>
                                <input type="text" name="telephone_number" class="mp-input mp-editable"
                                    value="<?= htmlspecialchars($m['telephone_number'] ?? '') ?>" disabled>
                            </div>
                            <div class="mp-field">
                                <label>Landline 2</label>
                                <input type="text" name="telephone_number_2" class="mp-input mp-editable"
                                    value="<?= htmlspecialchars($m['telephone_number_2'] ?? '') ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <!-- Home Address -->
                    <div class="mp-card">
                        <div class="mp-section-label">Home Address</div>
                        <div class="mp-field">
                            <input type="text" name="address" class="mp-input mp-editable"
                                value="<?= htmlspecialchars($m['address'] ?? '') ?>" disabled>
                        </div>
                    </div>

                    <!-- Save bar -->
                    <div id="mp-save-bar" class="mp-save-bar">
                        <button type="submit" class="mp-btn-save">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            Confirm &amp; Save
                        </button>
                    </div>

                </div><!-- /mp-right -->
            </div><!-- /mp-grid -->
        </form>

    </div>
</div>

<script>
    (function() {
        const editBtn = document.getElementById('mp-edit-btn');
        const saveBar = document.getElementById('mp-save-bar');
        const editables = document.querySelectorAll('#mp-root .mp-editable');
        let editing = false;

        const iconEdit = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
        const iconCancel = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;

        editBtn.addEventListener('click', function() {
            editing = !editing;
            editBtn.innerHTML = editing ? `${iconCancel} Cancel` : `${iconEdit} Edit Profile`;
            editBtn.classList.toggle('mp-btn-edit--active', editing);
            saveBar.style.display = editing ? 'flex' : 'none';
            editables.forEach(el => {
                el.disabled = !editing;
            });
        });
    })();
</script>