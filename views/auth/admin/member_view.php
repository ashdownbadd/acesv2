<?php
require_once 'core/db.php';
$db = (new DB())->connect();

$targetId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$targetId]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    echo '<p>No member data found.</p>';
    return;
}
?>

<div class="pg-centered">
    <div class="mv">

        <div class="mv__grid">

            <!-- 1 BALANCE -->
            <section class="mv-card mv-card--balance">
                <div class="mv-card__label">Current Balance</div>
                <div class="mv-card__balance">₱<?= number_format($m['balance'] ?? 0) ?></div>
            </section>


            <!-- 2 MEMBERSHIP -->
            <section class="mv-card mv-card--membership">

                <header class="mv-card__title">Membership Details</header>

                <div class="mv-form">

                    <div class="mv-field">
                        <label class="mv-label">Member ID</label>
                        <input class="mv-input" value="<?= htmlspecialchars($m['member_id']) ?>" readonly>
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Type</label>
                        <select class="mv-input">
                            <option><?= htmlspecialchars($m['membership_type']) ?></option>
                        </select>
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Status</label>
                        <select class="mv-input">
                            <option><?= htmlspecialchars($m['status']) ?></option>
                        </select>
                    </div>

                </div>

            </section>


            <!-- 3 PERSONAL -->
            <section class="mv-card mv-card--personal">

                <header class="mv-card__title">Personal Details</header>

                <div class="mv-form mv-form--personal">

                    <div class="mv-field">
                        <label class="mv-label">Prefix</label>
                        <input class="mv-input" value="<?= $m['prefix'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">First Name</label>
                        <input class="mv-input" value="<?= $m['first_name'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Middle Name</label>
                        <input class="mv-input" value="<?= $m['middle_name'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Last Name</label>
                        <input class="mv-input" value="<?= $m['last_name'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Suffix</label>
                        <input class="mv-input" value="<?= $m['suffix'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Birthdate</label>
                        <input type="date" class="mv-input" value="<?= $m['birthdate'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Death Date</label>
                        <input type="date" class="mv-input" value="<?= $m['death_date'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Civil Status</label>
                        <select class="mv-input">
                            <option><?= $m['civil_status'] ?></option>
                        </select>
                    </div>

                    <div class="mv-field mv-field--full">
                        <label class="mv-label">Full Address</label>
                        <textarea class="mv-input"><?= $m['address'] ?></textarea>
                    </div>

                </div>

            </section>


            <!-- 4 CONTACT -->
            <section class="mv-card mv-card--contact">

                <header class="mv-card__title">Contact Information</header>

                <div class="mv-form">

                    <div class="mv-field mv-field--full">
                        <label class="mv-label">Email Address</label>
                        <input class="mv-input" value="<?= $m['email'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Mobile</label>
                        <input class="mv-input" value="<?= $m['phone_number'] ?>">
                    </div>

                    <div class="mv-field">
                        <label class="mv-label">Telephone</label>
                        <input class="mv-input" value="<?= $m['telephone_number'] ?>">
                    </div>

                </div>

            </section>


            <!-- 5 REMARKS -->
            <section class="mv-card mv-card--remarks">

                <header class="mv-card__title">Admin Remarks</header>

                <textarea class="mv-remarks"><?= htmlspecialchars($m['remarks']) ?></textarea>

            </section>


            <!-- 6 ACTION BAR -->
            <div class="mv-action-bar">

                <button class="mv-btn mv-btn--save">Save Changes</button>
                <button class="mv-btn mv-btn--close">Close</button>

            </div>

        </div>

    </div>
</div>