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
    <div class="mvw pg">

            <div class="mv-grid mv-grid--top">

            <!-- BALANCE -->ddsdasnncvjsadsadsa
            <div class="mv-card mv-card--balance">
                <div class="mv-bl">Current Balance</div>
                <div class="mv-balance">₱<?= number_format($m['balance'] ?? 0) ?></div>
                <div class="mv-id"><?= htmlspecialchars($m['member_id']) ?></div>
            </div>

            <!-- MEMBERSHIP -->
            <div class="mv-card mv-card--membership">

                <div class="mv-ct">Membership Details</div>

                <div class="mv-form-grid">

                    <div class="mv-f">
                        <div class="mv-lbl">Member ID</div>
                        <input class="mv-input"
                            value="<?= htmlspecialchars($m['member_id']) ?>" readonly>
                    </div>

                    <div class="mv-f">
                        <div class="mv-lbl">Type</div>
                        <select class="mv-input">
                            <option><?= htmlspecialchars($m['membership_type']) ?></option>
                        </select>
                    </div>

                    <div class="mv-f">
                        <div class="mv-lbl">Status</div>
                        <select class="mv-input">
                            <option><?= htmlspecialchars($m['status']) ?></option>
                        </select>
                    </div>

                </div>
            </div>

        </div>


        <!-- PERSONAL -->
        <div class="mv-card mv-card--personal">

            <div class="mv-ct">Personal Details</div>

            <div class="mv-form-grid mv-form-grid--personal">

                <div class="mv-f">
                    <div class="mv-lbl">Prefix</div>
                    <input class="mv-input" value="<?= $m['prefix'] ?>">
                </div>

                <div class="mv-f">
                    <div class="mv-lbl">First Name</div>
                    <input class="mv-input" value="<?= $m['first_name'] ?>">
                </div>

                <div class="mv-f">
                    <div class="mv-lbl">Middle Name</div>
                    <input class="mv-input" value="<?= $m['middle_name'] ?>">
                </div>

                <div class="mv-f">
                    <div class="mv-lbl">Last Name</div>
                    <input class="mv-input" value="<?= $m['last_name'] ?>">
                </div>

                <div class="mv-f">
                    <div class="mv-lbl">Suffix</div>
                    <input class="mv-input" value="<?= $m['suffix'] ?>">
                </div>

                <div class="mv-f">
                    <div class="mv-lbl">Birthdate</div>
                    <input type="date" class="mv-input"
                        value="<?= $m['birthdate'] ?>">
                </div>

                <div class="mv-f">
                    <div class="mv-lbl">Death Date</div>
                    <input type="date" class="mv-input"
                        value="<?= $m['death_date'] ?>">
                </div>

                <div class="mv-f">
                    <div class="mv-lbl">Civil Status</div>
                    <select class="mv-input">
                        <option><?= $m['civil_status'] ?></option>
                    </select>
                </div>

                <div class="mv-f mv-f--full">
                    <div class="mv-lbl">Full Address</div>
                    <textarea class="mv-input"><?= $m['address'] ?></textarea>
                </div>

            </div>
        </div>


        <!-- BOTTOM GRID -->
        <div class="mv-grid mv-grid--bottom">

            <!-- CONTACT -->
            <div class="mv-card mv-card--contact">

                <div class="mv-ct">Contact Information</div>

                <div class="mv-form-grid">

                    <div class="mv-f mv-f--full">
                        <div class="mv-lbl">Email Address</div>
                        <input class="mv-input"
                            value="<?= $m['email'] ?>">
                    </div>

                    <div class="mv-f">
                        <div class="mv-lbl">Mobile</div>
                        <input class="mv-input"
                            value="<?= $m['phone_number'] ?>">
                    </div>

                    <div class="mv-f">
                        <div class="mv-lbl">Telephone</div>
                        <input class="mv-input"
                            value="<?= $m['telephone_number'] ?>">
                    </div>

                </div>

            </div>


            <!-- RIGHT COLUMN -->
            <div class="mv-right">

                <div class="mv-card mv-card--remarks">

                    <div class="mv-ct">Admin Remarks</div>

                    <textarea class="mv-remarks"><?= $m['remarks'] ?></textarea>

                </div>

                <div class="mv-btn-stack">

                    <button class="mv-btn mv-btn--save">
                        Save Changes
                    </button>

                    <button class="mv-btn mv-btn--close">
                        Close
                    </button>

                </div>

            </div>

        </div>

    </div>
</div>