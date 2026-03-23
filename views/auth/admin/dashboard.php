<?php
require_once 'core/db.php';

$dbClass = new DB();
$db = $dbClass->connect();

$userId = $_SESSION['user_id'] ?? 0;
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

try {
    $stmt = $db->query("SELECT * FROM members ORDER BY id ASC");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $members = [];
}

// KPI Logic
$total       = array_sum(array_column($members, 'balance'));
$totalCount  = count($members);
$regCount    = count(array_filter($members, fn($m) => ($m['membership_type'] ?? '') === 'Regular'));
$ascCount    = count(array_filter($members, fn($m) => ($m['membership_type'] ?? '') === 'Associate'));

if (!function_exists('fmtK')) {
    function fmtK($n)
    {
        return number_format($n, 2);
    }
}
if (!function_exists('fullName')) {
    function fullName($m)
    {
        return trim(($m['first_name'] ?? '') . ' ' . ($m['middle_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
    }
}
?>

<div class="l-app">
    <div class="l-container">
        <main class="l-app__body">
            <div class="c-kpi-grid">
                <div class="c-kpi-card">
                    <div class="c-kpi-card__label">Total Capital</div>
                    <div class="c-kpi-card__value">P <?= fmtK($total) ?></div>
                </div>
                <div class="c-kpi-card">
                    <div class="c-kpi-card__label">Total Members</div>
                    <div class="c-kpi-card__value"><?= $totalCount ?></div>
                </div>
                <div class="c-kpi-card">
                    <div class="c-kpi-card__label">Regular</div>
                    <div class="c-kpi-card__value"><?= $regCount ?></div>
                </div>
                <div class="c-kpi-card">
                    <div class="c-kpi-card__label">Associate</div>
                    <div class="c-kpi-card__value"><?= $ascCount ?></div>
                </div>
            </div>

            <div class="c-table-card">
                <div class="c-table-card__header">
                    <span class="c-table-card__title">Member Registry</span>
                    <div class="c-table-card__actions">
                        <input type="text" id="tbl-search" class="c-table-card__search" placeholder="Search...">
                        <a href="?page=member_add" class="c-btn-add">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Member
                        </a>
                    </div>
                </div>

                <div class="tbl-scroll">
                    <table class="c-table">
                        <thead>
                            <tr>
                                <th class="c-table__header">No.</th>
                                <th class="c-table__header">Name</th>
                                <th class="c-table__header">Balance</th>
                                <th class="c-table__header">Status</th>
                                <th class="c-table__header" style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1; // Initialize the counter
                            foreach ($members as $m):
                            ?>
                                <tr class="c-table__row" onclick="window.location='?page=member_view&member_id=<?= $m['id'] ?>'" style="cursor: pointer;">
                                    <td class="c-table__cell c-table__cell--id"><?= $counter++ ?></td>

                                    <td class="c-table__cell">
                                        <div class="c-member-info">
                                            <span class="c-member-info__name"><?= htmlspecialchars(fullName($m)) ?></span>
                                            <span class="c-member-info__id"><?= str_pad($m['member_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                    </td>
                                    <td class="c-table__cell c-table__cell--balance">&#8369;<?= number_format($m['balance'], 2) ?></td>
                                    <td class="c-table__cell">
                                        <span class="c-badge c-badge--<?= str_replace(' ', '-', strtolower($m['status'] ?? 'active')) ?>">
                                            <?= htmlspecialchars($m['status']) ?>
                                        </span>
                                    </td>
                                    <td class="c-table__cell" style="text-align: center;">
                                        <div class="c-table__actions-wrapper">
                                            <button class="c-btn-icon c-btn-icon--amort"
                                                onclick="event.stopPropagation(); window.location='amortization.html?member_id=<?= $m['id'] ?>'"
                                                title="Amortization">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                                </svg>
                                            </button>

                                            <button class="c-btn-icon c-btn-icon--del"
                                                onclick="event.stopPropagation(); confirmDelete(<?= $m['id'] ?>, '<?= htmlspecialchars(fullName($m)) ?>')"
                                                title="Delete Member">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) {
            window.location.href = `?action=delete_member&id=${id}`;
        }
    }
</script>