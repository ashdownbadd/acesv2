<?php
require_once 'core/db.php';
require_once 'core/repository/member_repository.php';
require_once 'core/repository/loan_repository.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$db = (new DB())->connect();
$memberRepo = new MemberRepository($db);
$loanRepo = new LoanRepository($db);

// Unified Save Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_member'])) {
    // 1. Always update member details
    $memberRepo->update($_POST);

    // 2. If a new loan was submitted, save it
    // The JavaScript below sets 'action_type' to 'new_loan' when principal is entered
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'new_loan') {
        $loanRepo->create($_POST);
    }

    $_SESSION['toast_success'] = "Changes saved successfully!";
    header("Location: index.php?page=member_view&member_id=" . (int)$_POST['member_id']);
    exit();
}

$targetId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$m = $memberRepo->findById($targetId);
$loanHistory = $loanRepo->findByMemberId($targetId);

if (!$m) {
    echo '<p>No member data found.</p>';
    exit();
}
?>

<form method="POST" action="">
    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
    <input type="hidden" name="action_type" id="action_type" value="update_only">

    <div class="pg-centered">
        <div class="mv">
            <div class="mv__grid">

                <section class="mv-card mv-card--balance">
                    <div class="mv-card__label">Current Balance</div>
                    <input type="text" class="mv-card__balance" name="balance" value="<?= number_format((float)$m['balance'], 2, '.', ',') ?>">
                </section>

                <section class="mv-card mv-card--membership">
                    <header class="mv-card__title">Membership Details</header>
                    <div class="mv-form" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="mv-field">
                            <label class="mv-label">Member ID</label>
                            <input class="mv-input" name="member_id" value="<?= htmlspecialchars($m['member_id']) ?>" readonly>
                        </div>

                        <div class="mv-field">
                            <label class="mv-label">Type</label>
                            <select class="mv-input" name="membership_type">
                                <?php foreach (['Regular', 'Associate'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $m['membership_type'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mv-field">
                            <label class="mv-label">Date Joined</label>
                            <input type="date"
                                class="mv-input"
                                name="approval_date"
                                value="<?= !empty($m['approval_date']) ? date('Y-m-d', strtotime($m['approval_date'])) : '' ?>">
                        </div>

                        <div class="mv-field">
                            <label class="mv-label">Status</label>
                            <select class="mv-input" name="status">
                                <?php foreach (['Active', 'Deceased', 'Delisted', 'On-Hold', 'Overdue', 'Under Litigation'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $m['status'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mv-field">
                            <label class="mv-label">MGS Status</label>
                            <div style="display: flex; align-items: center; gap: 12px; height: 38px;">
                                <label class="mgs-switch">
                                    <input type="checkbox" name="is_mgs" value="1" <?= ($m['is_mgs'] ?? 0) ? 'checked' : '' ?>>
                                    <span class="mgs-slider"></span>
                                </label>
                                <span class="mv-label" style="text-transform: none; color: var(--t2); letter-spacing: normal;">
                                    <?= ($m['is_mgs'] ?? 0) ? 'Good Standing' : 'Not in GS' ?>
                                </span>
                            </div>
                        </div>

                        <div class="mv-field">
                            <label class="mv-label">Last Updated</label>
                            <input class="mv-input" value="<?= !empty($m['updated_at']) ? date('M d, Y', strtotime($m['updated_at'])) : 'New Record' ?>" readonly title="Automatically tracked by system">
                        </div>
                    </div>
                </section>

                <section class="mv-card mv-card--personal">
                    <header class="mv-card__title">Personal Details</header>
                    <div class="mv-form mv-form--personal">
                        <div class="mv-field"><label class="mv-label">Prefix</label><input class="mv-input" name="prefix" value="<?= htmlspecialchars($m['prefix']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">First Name</label><input class="mv-input" name="first_name" value="<?= htmlspecialchars($m['first_name']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">Middle Name</label><input class="mv-input" name="middle_name" value="<?= htmlspecialchars($m['middle_name']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">Last Name</label><input class="mv-input" name="last_name" value="<?= htmlspecialchars($m['last_name']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">Suffix</label><input class="mv-input" name="suffix" value="<?= htmlspecialchars($m['suffix']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">Birthdate</label><input type="date" class="mv-input" name="birthdate" value="<?= $m['birthdate'] ?>"></div>
                        <div class="mv-field"><label class="mv-label">Death Date</label><input type="date" class="mv-input" name="death_date" value="<?= $m['death_date'] ?>"></div>
                        <div class="mv-field">
                            <label class="mv-label">Civil Status</label>
                            <select class="mv-input" name="civil_status">
                                <?php foreach (['Single', 'Married', 'Widow'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $m['civil_status'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mv-field mv-field--full"><label class="mv-label">Full Address</label><textarea class="mv-input" name="address"><?= htmlspecialchars($m['address']) ?></textarea></div>
                    </div>
                </section>

                <section class="mv-card mv-card--contact">
                    <header class="mv-card__title">Contact Information</header>
                    <div class="mv-form">
                        <div class="mv-field mv-field--full"><label class="mv-label">Email Address</label><input class="mv-input" name="email" value="<?= htmlspecialchars($m['email']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">Mobile 1</label><input class="mv-input" name="phone_number" value="<?= htmlspecialchars($m['phone_number']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">Mobile 2</label><input class="mv-input" name="phone_number_2" value="<?= htmlspecialchars($m['phone_number_2'] ?? '') ?>"></div>
                        <div class="mv-field"><label class="mv-label">Telephone 1</label><input class="mv-input" name="telephone_number" value="<?= htmlspecialchars($m['telephone_number']) ?>"></div>
                        <div class="mv-field"><label class="mv-label">Telephone 2</label><input class="mv-input" name="telephone_number_2" value="<?= htmlspecialchars($m['telephone_number_2'] ?? '') ?>"></div>
                    </div>
                </section>

                <section class="mv-card mv-card--remarks">
                    <header class="mv-card__title">Admin Remarks</header>
                    <textarea class="mv-remarks" name="remarks"><?= htmlspecialchars($m['remarks']) ?></textarea>
                </section>

                <div class="mv-action-bar">
                    <button type="submit" name="save_member" class="mv-btn mv-btn--save">Save Changes</button>
                    <a href="index.php?page=dashboard" class="mv-btn mv-btn--close" style="text-align: center; text-decoration: none;">Close</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.querySelector('.principal-input').addEventListener('input', function() {
        document.getElementById('action_type').value = 'new_loan';
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toastTrigger = document.getElementById('toast-trigger');
        if (toastTrigger) {
            const message = toastTrigger.getAttribute('data-message');
            // Replace 'showToast' with the actual function used in your project 
            // (e.g., toastr.success(message), alert(message), etc.)
            if (typeof showToast === 'function') {
                showToast(message);
            } else {
                console.log("Toast message:", message);
            }
        }
    });
</script>

<script src="assets/js/loan_calculator.js"></script>