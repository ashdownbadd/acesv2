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

                <section class="mv-card mv-card--loan">
                    <header class="mv-card__title">Loan Details</header>
                    <div class="mv-form">
                        <div class="mv-field"><label class="mv-label">Loan Type</label>
                            <select class="mv-input loan-type" name="loan_type">
                                <option>Bridge Financing</option>
                                <option>Investment Loan</option>
                                <option>Pension Loan</option>
                                <option>Productivity Loan</option>
                                <option>Personal Loan</option>
                                <option>Salary Loan</option>
                                <option>Micro-Finance Loan</option>
                            </select>
                        </div>
                        <div class="mv-field"><label class="mv-label">Principal Amount</label><input class="mv-input principal-input" name="principal_amount"></div>
                        <div class="mv-field"><label class="mv-label">Interest Rate (%)</label><input class="mv-input interest-input" name="interest_rate" readonly></div>
                        <div class="mv-field"><label class="mv-label">Terms (Months)</label><input class="mv-input term-input" name="terms"></div>
                        <div class="mv-field"><label class="mv-label">Monthly Amortization</label><input class="mv-input amort-display" name="monthly_amortization" readonly></div>
                        <div class="mv-field"><label class="mv-label">Collateral</label>
                            <select class="mv-input" name="collateral">
                                <option>Post-Dated Check</option>
                                <option>Real Property</option>
                                <option>Chattels / Movable Assets</option>
                            </select>
                        </div>
                        <div class="mv-field"><label class="mv-label">SOA Status</label>
                            <select class="mv-input" name="soa_status">
                                <option>Updated</option>
                                <option>Pending</option>
                                <option>Overdue</option>
                            </select>
                        </div>

                        <div class="mv-field mv-field--full">
                            <label class="mv-label">Amortization Schedule Preview</label>
                            <div class="mv-schedule-table">
                                <table id="scheduleTable">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Total</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" style="text-align:center;">Enter loan details to generate schedule</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mv-field mv-field--full">
                            <label class="mv-label">Loan History</label>
                            <div class="mv-history-table">
                                <table id="loanHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Loan Type</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Term</th>
                                            <th>Monthly</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($loanHistory)):
                                            $counter = 1;
                                            foreach ($loanHistory as $loan):
                                        ?>
                                                <tr>
                                                    <td><?= $counter++ ?></td>

                                                    <td><?= htmlspecialchars($loan['loan_type']) ?></td>
                                                    <td><?= number_format($loan['principal'], 2) ?></td>
                                                    <td><?= htmlspecialchars($loan['interest_rate']) ?>%</td>
                                                    <td><?= htmlspecialchars($loan['terms']) ?></td>
                                                    <td><?= number_format($loan['monthly_amortization'], 2) ?></td>
                                                    <td><?= htmlspecialchars($loan['soa_status']) ?></td>
                                                    <td><?= date('Y-m-d', strtotime($loan['date_released'])) ?></td>
                                                </tr>
                                            <?php
                                            endforeach;
                                        else:
                                            ?>
                                            <tr>
                                                <td colspan="8" style="text-align:center;">No loan history available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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