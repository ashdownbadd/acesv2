<?php
require_once 'core/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = (new DB())->connect();

// Handle Save Changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_member'])) {

    $targetId = (int)$_POST['member_id'];

    $sql = "UPDATE members SET 
            prefix = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
            birthdate = ?, death_date = ?, civil_status = ?, address = ?, 
            membership_type = ?, status = ?, email = ?, phone_number = ?, 
            telephone_number = ?, remarks = ? 
            WHERE id = ?";

    $stmt = $db->prepare($sql);

    if ($stmt->execute([
        $_POST['prefix'],
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['last_name'],
        $_POST['suffix'],
        $_POST['birthdate'] ?: null,
        $_POST['death_date'] ?: null,
        $_POST['civil_status'],
        $_POST['address'],
        $_POST['membership_type'],
        $_POST['status'],
        $_POST['email'],
        $_POST['phone_number'],
        $_POST['telephone_number'],
        $_POST['remarks'],
        $targetId
    ])) {

        $_SESSION['toast_success'] = "Member details updated successfully!";

        header("Location: index.php?page=member_view&member_id=" . $targetId);
        exit();
    }
}



// Fetch member data
$targetId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$targetId]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    echo '<p>No member data found.</p>';
    return;
}
?>

<form method="POST" action="">
    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">

    <div class="pg-centered">
        <div class="mv">
            <div class="mv__grid">

                <section class="mv-card mv-card--balance">
                    <div class="mv-card__label">Current Balance</div>
                    <div class="mv-card__balance">₱<?= number_format($m['balance'] ?? 0) ?></div>
                </section>

                <section class="mv-card mv-card--membership">
                    <header class="mv-card__title">Membership Details</header>
                    <div class="mv-form">
                        <div class="mv-field">
                            <label class="mv-label">Member ID</label>
                            <input class="mv-input" value="<?= htmlspecialchars($m['member_id']) ?>" readonly>
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
                            <label class="mv-label">Status</label>
                            <select class="mv-input" name="status">
                                <?php foreach (['Active', 'Deceased', 'Delisted', 'On-Hold', 'Overdue', 'Under Litigation'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $m['status'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="mv-card mv-card--personal">
                    <header class="mv-card__title">Personal Details</header>
                    <div class="mv-form mv-form--personal">
                        <div class="mv-field">
                            <label class="mv-label">Prefix</label>
                            <input class="mv-input" name="prefix" value="<?= htmlspecialchars($m['prefix']) ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">First Name</label>
                            <input class="mv-input" name="first_name" value="<?= htmlspecialchars($m['first_name']) ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Middle Name</label>
                            <input class="mv-input" name="middle_name" value="<?= htmlspecialchars($m['middle_name']) ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Last Name</label>
                            <input class="mv-input" name="last_name" value="<?= htmlspecialchars($m['last_name']) ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Suffix</label>
                            <input class="mv-input" name="suffix" value="<?= htmlspecialchars($m['suffix']) ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Birthdate</label>
                            <input type="date" class="mv-input" name="birthdate" value="<?= $m['birthdate'] ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Death Date</label>
                            <input type="date" class="mv-input" name="death_date" value="<?= $m['death_date'] ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Civil Status</label>
                            <select class="mv-input" name="civil_status">
                                <?php foreach (['Single', 'Married', 'Widow'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $m['civil_status'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mv-field mv-field--full">
                            <label class="mv-label">Full Address</label>
                            <textarea class="mv-input" name="address"><?= htmlspecialchars($m['address']) ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="mv-card mv-card--contact">
                    <header class="mv-card__title">Contact Information</header>
                    <div class="mv-form">
                        <div class="mv-field mv-field--full">
                            <label class="mv-label">Email Address</label>
                            <input class="mv-input" name="email" value="<?= htmlspecialchars($m['email']) ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Mobile</label>
                            <input class="mv-input" name="phone_number" value="<?= htmlspecialchars($m['phone_number']) ?>">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Telephone</label>
                            <input class="mv-input" name="telephone_number" value="<?= htmlspecialchars($m['telephone_number']) ?>">
                        </div>
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

<?php if (isset($_SESSION['toast_success'])): ?>
    <div id="toast-trigger" data-message="<?= $_SESSION['toast_success'] ?>" style="display:none;"></div>
    <?php unset($_SESSION['toast_success']); ?>
<?php endif; ?>