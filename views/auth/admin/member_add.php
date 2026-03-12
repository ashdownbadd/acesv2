<?php
require_once 'core/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = (new DB())->connect();

// Update your POST handling block to this:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {

    // Use null coalescing to handle missing/empty keys
    $memberId        = $_POST['member_id'] ?? '';
    $prefix          = $_POST['prefix'] ?? '';
    $firstName       = $_POST['first_name'] ?? '';
    $middleName      = $_POST['middle_name'] ?? '';
    $lastName        = $_POST['last_name'] ?? '';
    $suffix          = $_POST['suffix'] ?? '';
    $birthdate       = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
    $deathDate       = !empty($_POST['death_date']) ? $_POST['death_date'] : null;
    $civilStatus     = $_POST['civil_status'] ?? 'Single';
    $address         = $_POST['address'] ?? '';
    $membershipType  = $_POST['membership_type'] ?? 'Regular';
    $status          = $_POST['status'] ?? 'Active';
    $email           = $_POST['email'] ?? '';
    $phone           = $_POST['phone_number'] ?? '';
    $tel             = $_POST['telephone_number'] ?? '';
    $remarks         = $_POST['remarks'] ?? '';
    $rawBalance = $_POST['balance'] ?? '0';
    $initialBalance = (float)str_replace(',', '', $rawBalance);

    $sql = "INSERT INTO members (
                member_id, prefix, first_name, middle_name, last_name, suffix, 
                birthdate, death_date, civil_status, address, 
                membership_type, status, email, phone_number, 
                telephone_number, remarks, balance
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);

    if ($stmt->execute([
        $memberId,
        $prefix,
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $birthdate,
        $deathDate,
        $civilStatus,
        $address,
        $membershipType,
        $status,
        $email,
        $phone,
        $tel,
        $remarks,
        $initialBalance
    ])) {
        $_SESSION['toast_success'] = "New member registered successfully!";
        header("Location: index.php?page=dashboard");
        exit();
    }
}
?>

<form method="POST" action="">
    <div class="pg-centered">
        <div class="mv">
            <div class="mv__grid">

                <section class="mv-card mv-card--balance">
                    <div class="mv-card__label">Current Balance</div>
                    <input type="text"
                        class="mv-card__balance"
                        name="balance"
                        value="0.00">
                </section>

                <section class="mv-card mv-card--membership">
                    <header class="mv-card__title">Membership Details</header>
                    <div class="mv-form">
                        <div class="mv-field">
                            <label class="mv-label">Member ID</label>
                            <input class="mv-input" name="member_id" placeholder="e.g. 1001" required>
                        </div>

                        <div class="mv-field">
                            <label class="mv-label">Type</label>
                            <select class="mv-input" name="membership_type">
                                <option value="Regular">Regular</option>
                                <option value="Associate">Associate</option>
                            </select>
                        </div>

                        <div class="mv-field">
                            <label class="mv-label">Status</label>
                            <select class="mv-input" name="status">
                                <option value="Active">Active</option>
                                <option value="On-Hold">On-Hold</option>
                                <option value="Under Litigation">Under Litigation</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="mv-card mv-card--personal">
                    <header class="mv-card__title">Personal Details</header>
                    <div class="mv-form mv-form--personal">
                        <div class="mv-field">
                            <label class="mv-label">Prefix</label>
                            <input class="mv-input" name="prefix" placeholder="Mr./Ms.">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">First Name</label>
                            <input class="mv-input" name="first_name" required>
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Middle Name</label>
                            <input class="mv-input" name="middle_name">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Last Name</label>
                            <input class="mv-input" name="last_name" required>
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Suffix</label>
                            <input class="mv-input" name="suffix" placeholder="Jr./III">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Birthdate</label>
                            <input type="date" class="mv-input" name="birthdate">
                        </div>
                        <div class="mv-field" style="opacity: 0.5; pointer-events: none;">
                            <label class="mv-label">Death Date</label>
                            <input type="date" class="mv-input" name="death_date" disabled>
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Civil Status</label>
                            <select class="mv-input" name="civil_status">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widow">Widow</option>
                            </select>
                        </div>
                        <div class="mv-field mv-field--full">
                            <label class="mv-label">Full Address</label>
                            <textarea class="mv-input" name="address"></textarea>
                        </div>
                    </div>
                </section>

                <section class="mv-card mv-card--contact">
                    <header class="mv-card__title">Contact Information</header>
                    <div class="mv-form">
                        <div class="mv-field mv-field--full">
                            <label class="mv-label">Email Address</label>
                            <input type="email" class="mv-input" name="email">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Mobile</label>
                            <input class="mv-input" name="phone_number">
                        </div>
                        <div class="mv-field">
                            <label class="mv-label">Telephone</label>
                            <input class="mv-input" name="telephone_number">
                        </div>
                    </div>
                </section>

                <section class="mv-card mv-card--remarks">
                    <header class="mv-card__title">Admin Remarks</header>
                    <textarea class="mv-remarks" name="remarks" placeholder="Notes about the new member..."></textarea>
                </section>

                <div class="mv-action-bar">
                    <button type="submit" name="add_member" class="mv-btn mv-btn--save">Add Member</button>
                    <a href="index.php?page=dashboard" class="mv-btn mv-btn--close" style="text-align: center; text-decoration: none;">Cancel</a>
                </div>

            </div>
        </div>
    </div>
</form>