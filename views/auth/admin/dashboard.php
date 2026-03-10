<?php

require_once 'core/db.php';

$dbClass = new DB();
$db = $dbClass->connect();

$userId = $_SESSION['user_id'] ?? 0;
$user = [
    'name' => $_SESSION['name'] ?? 'Admin',
    'role' => ucfirst($_SESSION['role'] ?? 'Staff'),
    'initials' => strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1) . substr(strrchr($_SESSION['name'] ?? ' ', ' '), 1, 1))
];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

try {
    $stmt = $db->query("SELECT * FROM members ORDER BY id ASC");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $members = [];
}

$total        = array_sum(array_column($members, 'balance'));
$totalCount   = count($members);
$regCount     = count(array_filter($members, fn($m) => ($m['membership_type'] ?? '') === 'Regular'));
$ascCount     = count(array_filter($members, fn($m) => ($m['membership_type'] ?? '') === 'Associate'));
$activeCount  = count(array_filter($members, fn($m) => ($m['status'] ?? '') === 'Active'));

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
                    <input type="text" id="tbl-search" class="c-table-card__search" placeholder="Search...">
                </div>

                <div class="tbl-scroll">
                    <table class="c-table">
                        <thead>
                            <tr>
                                <th class="c-table__header">#</th>
                                <th class="c-table__header">Name</th>
                                <th class="c-table__header">Balance</th>
                                <th class="c-table__header">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                                <tr class="c-table__row"
                                    onclick="window.location='?page=member_view&member_id=<?= $m['id'] ?>'"
                                    style="cursor: pointer;">
                                    <td class="c-table__cell c-table__cell--id"><?= $m['id'] ?></td>
                                    <td class="c-table__cell">
                                        <div class="c-member-info">
                                            <span class="c-member-info__name"><?= htmlspecialchars(fullName($m)) ?></span>
                                            <span class="c-member-info__id"><?= str_pad($m['member_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                    </td>
                                    <td class="c-table__cell c-table__cell--balance">
                                        &#8369;<?= number_format($m['balance'], 2) ?>
                                    </td>
                                    <td class="c-table__cell">
                                        <span class="c-badge c-badge--<?= str_replace(' ', '-', strtolower($m['status'] ?? 'active')) ?>">
                                            <?= htmlspecialchars($m['status']) ?>
                                        </span>
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

<script type="module" src="/acesv2/assets/js/app.js"></script>