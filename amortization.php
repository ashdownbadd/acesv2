<?php

/**
 * ACESv3 - Loan Amortization & Payment Tracking
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once 'core/db.php';
$pdo = (new DB())->connect();

$user = [
  'name'   => $_SESSION['name'] ?? 'Admin',
  'role'   => $_SESSION['role'] ?? 'Staff',
  'avatar' => $_SESSION['avatar'] ?? ''
];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

// ── API: HANDLE AJAX ACTIONS FOR PERSISTENCE ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  header('Content-Type: application/json');
  $body = json_decode(file_get_contents('php://input'), true);

  if ($_GET['action'] === 'save_loan') {
    try {
      $pdo->beginTransaction();
      $loan = $body['loan'];
      $schedule = $body['schedule'];

      // Check if this member already has a loan record to update or insert
      $checkStmt = $pdo->prepare("SELECT id FROM loans WHERE member_id = ? LIMIT 1");
      $checkStmt->execute([$loan['member_id']]);
      $existingLoanId = $checkStmt->fetchColumn();

      if ($existingLoanId) {
        // Update main loan record
        $stmt = $pdo->prepare("UPDATE loans SET 
          loan_type = :loan_type, collateral = :collateral, soa_status = :soa_status, 
          amort_type = :amort_type, principal_amount = :principal, interest_rate = :interest, 
          terms = :terms, start_date = :start_date, tct_no = :tct_no, tax_dec_no = :tax_dec_no, 
          rp_status = :rp_status 
          WHERE id = :id");

        $stmt->execute([
          ':loan_type' => $loan['loan_type'],
          ':collateral' => $loan['collateral'],
          ':soa_status' => $loan['soa_status'],
          ':amort_type' => $loan['amort_type'],
          ':principal' => $loan['principal'],
          ':interest' => $loan['interest_rate'],
          ':terms' => $loan['terms'],
          ':start_date' => $loan['start_date'],
          ':tct_no' => $loan['tct_no'] ?? null,
          ':tax_dec_no' => $loan['tax_dec_no'] ?? null,
          ':rp_status' => $loan['rp_status'] ?? null,
          ':id' => $existingLoanId
        ]);
        $loanId = $existingLoanId;

        // Clear out old schedule entries to overwrite cleanly
        $delStmt = $pdo->prepare("DELETE FROM loan_schedules WHERE loan_id = ?");
        $delStmt->execute([$loanId]);
      } else {
        // Insert a brand new loan record
        $stmt = $pdo->prepare("INSERT INTO loans 
          (member_id, loan_type, collateral, soa_status, amort_type, principal_amount, interest_rate, terms, start_date, tct_no, tax_dec_no, rp_status) 
          VALUES 
          (:member_id, :loan_type, :collateral, :soa_status, :amort_type, :principal, :interest, :terms, :start_date, :tct_no, :tax_dec_no, :rp_status)");

        $stmt->execute([
          ':member_id' => $loan['member_id'],
          ':loan_type' => $loan['loan_type'],
          ':collateral' => $loan['collateral'],
          ':soa_status' => $loan['soa_status'],
          ':amort_type' => $loan['amort_type'],
          ':principal' => $loan['principal'],
          ':interest' => $loan['interest_rate'],
          ':terms' => $loan['terms'],
          ':start_date' => $loan['start_date'],
          ':tct_no' => $loan['tct_no'] ?? null,
          ':tax_dec_no' => $loan['tax_dec_no'] ?? null,
          ':rp_status' => $loan['rp_status'] ?? null
        ]);
        $loanId = $pdo->lastInsertId();
      }

      // Batch insert schedule matrix breakdown rows
      $schedStmt = $pdo->prepare("INSERT INTO loan_schedules 
        (loan_id, period, due_date, amortization, principal_component, interest_component, remaining_principal) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

      foreach ($schedule as $row) {
        $schedStmt->execute([
          $loanId,
          $row['period'],
          $row['dueDate'],
          $row['amortization'],
          $row['principalComponent'],
          $row['interestComponent'],
          $row['remainingPrincipal']
        ]);
      }

      $pdo->commit();
      echo json_encode(['success' => true, 'message' => 'Amortization schedule saved successfully!']);
    } catch (Exception $e) {
      $pdo->rollBack();
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
  }
}

// ── Member Context Mapping from GET ───────────────────────────────────
$memberId = intval($_GET['member_id'] ?? 0);
$memberName = '';
$existingLoan = null;
$existingSchedule = [];

if ($memberId > 0) {
  try {
    // 1. Fetch Member Basic Metadata Info
    $mStmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM members WHERE id = ?");
    $mStmt->execute([$memberId]);
    $mRow = $mStmt->fetch();
    if ($mRow) {
      $memberName = trim(($mRow['first_name'] ?? '') . ' ' . ($mRow['middle_name'] ?? '') . ' ' . ($mRow['last_name'] ?? ''));
    }

    // 2. Fetch Hydration Context: Find existing saved loan parameters
    $lStmt = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? LIMIT 1");
    $lStmt->execute([$memberId]);
    $existingLoan = $lStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingLoan) {
      // 3. Fetch matching monthly breakdown tables
      $sStmt = $pdo->prepare("SELECT * FROM loan_schedules WHERE loan_id = ? ORDER BY period ASC");
      $sStmt->execute([$existingLoan['id']]);
      $existingSchedule = $sStmt->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (PDOException $e) {
    // Silent recovery
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loan Amortization Setup</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Syne:wght@600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/variables.css">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #f3f3f2;
      --surface: #f8f8f7;
      --raised: #f0ede8;
      --rim: #e0dcd5;
      --gold: #f59b0a;
      --gold-dim: rgba(217, 142, 22, 0.1);
      --text: #1c1a17b2;
      --t2: rgba(26, 25, 23, 0.6);
      --t3: rgba(26, 25, 23, 0.35);
      --border: rgba(211, 209, 207, 1.0);
      --danger: #d93d3d;
      --ok: #27a858;
      --font-main: 'Poppins', sans-serif;
      --font-heading: 'Syne', sans-serif;
      --font-mono: 'IBM Plex Mono', monospace;
      --radius: 10px;
      --gap: 12px;
      --pad-card: 14px 16px;
    }

    html,
    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--font-main);
      min-height: 100vh;
    }

    .page {
      max-width: 1100px;
      margin: 0 auto;
      padding: 40px 24px;
    }

    .member-banner {
      background: var(--gold-dim);
      border-left: 4px solid var(--gold);
      padding: 12px 16px;
      border-radius: 4px;
      margin-bottom: 20px;
      font-weight: 500;
      color: var(--text);
    }

    .section-label {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--t3);
      margin-bottom: 10px;
      font-family: var(--font-heading);
    }

    .row {
      margin-bottom: 20px;
    }

    .grid-4 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr));
      gap: var(--gap);
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: var(--pad-card);
    }

    .card__label {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--t3);
      margin-bottom: 8px;
      font-family: var(--font-heading);
    }

    .card select,
    .card input {
      width: 100%;
      background: var(--raised);
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 8px 10px;
      color: var(--text);
      font-size: 13px;
      outline: none;
      appearance: none;
    }

    .card select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23a09a91' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 28px;
      cursor: pointer;
    }

    .card input:focus,
    .card select:focus {
      border-color: var(--gold);
      background: var(--surface);
      box-shadow: 0 0 0 3px var(--gold-dim);
    }

    .card input[readonly] {
      background: var(--raised);
      cursor: not-allowed;
      opacity: 0.85;
    }

    .summary-strip {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(180px, 100%), 1fr));
      gap: var(--gap);
      margin-bottom: 20px;
    }

    .stat {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: var(--pad-card);
    }

    .stat__label {
      font-size: 10px;
      font-weight: 600;
      color: var(--t3);
      text-transform: uppercase;
      font-family: var(--font-heading);
      margin-bottom: 6px;
    }

    .stat__value {
      font-family: var(--font-mono);
      font-size: 18px;
      color: var(--text);
      font-weight: 500;
    }

    .stat--highlight {
      background: var(--gold);
      border-color: var(--gold);
    }

    .stat--highlight .stat__label {
      color: rgba(255, 255, 255, 0.7);
    }

    .stat--highlight .stat__value {
      color: #fff;
      font-weight: 700;
    }

    .table-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .table-card__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 18px;
      border-bottom: 1px solid var(--border);
      background: var(--raised);
    }

    .table-card__title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--t2);
      font-family: var(--font-heading);
    }

    .tbl-wrap {
      overflow-x: auto;
      max-height: 400px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }

    thead th {
      padding: 10px 16px;
      text-align: right;
      font-size: 10px;
      font-weight: 600;
      color: var(--t3);
      background: var(--raised);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      font-family: var(--font-heading);
    }

    thead th:first-child {
      text-align: left;
    }

    tbody td {
      padding: 10px 16px;
      text-align: right;
      border-bottom: 1px solid var(--border);
      color: var(--text);
      font-family: var(--font-mono);
      font-variant-numeric: tabular-nums;
    }

    tbody td:first-child {
      text-align: left;
      font-family: var(--font-main);
      color: var(--t2);
    }

    tbody tr:hover td {
      background: var(--gold-dim);
    }

    .editable-cell {
      background: #fff !important;
      border: 1px solid var(--gold) !important;
      padding: 2px 4px;
      width: 80px;
      text-align: right;
      font-family: var(--font-mono);
      color: #000;
    }

    .rp-upload-label {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 12px;
      border: 1px dashed var(--border);
      border-radius: 6px;
      background: var(--raised);
      color: var(--t2);
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: all 0.2s;
    }

    .rp-upload-label:hover {
      border-color: var(--gold);
      background: var(--gold-dim);
      color: var(--gold);
    }

    .rp-has-file {
      border-style: solid;
      border-color: var(--ok);
      background: rgba(39, 168, 88, 0.06);
      color: var(--ok);
    }

    .am-back {
      font-size: 11px;
      font-weight: 600;
      color: var(--t3);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border: 1px solid var(--border);
      border-radius: 20px;
      margin-bottom: 20px;
    }

    .am-back:hover {
      background: var(--raised);
    }

    /* Save Button Aesthetic */
    .c-btn-save {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--gold);
      color: #fff;
      font-family: var(--font-heading);
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s;
      gap: 8px;
      width: 100%;
      margin-top: 15px;
      box-shadow: 0 4px 12px var(--gold-dim);
    }

    .c-btn-save:hover {
      background: #e08b00;
      transform: translateY(-1px);
    }

    .c-btn-save:active {
      transform: translateY(0);
    }
  </style>
</head>

<body>

  <?php include 'views/auth/partials/navbar.php'; ?>

  <div class="page">
    <a href="index.php?page=dashboard" class="am-back">
      <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M10 12L6 8l4-4" />
      </svg>
      Back to Dashboard
    </a>

    <?php if ($memberId > 0): ?>
      <div class="member-banner">
        Active Member Context: <span style="color: var(--gold); font-weight: 700;"><?= htmlspecialchars($memberName) ?></span> (ID: <?= $memberId ?>)
      </div>
    <?php endif; ?>

    <form id="amortizationForm" onsubmit="event.preventDefault();">
      <div class="row">
        <div class="section-label">Loan parameters</div>
        <div class="grid-4">
          <div class="card">
            <div class="card__label">Loan type</div>
            <select id="loan_type" required>
              <option value="" disabled selected>— Select Type —</option>
              <option value="Bridge Financing">Bridge Financing</option>
              <option value="Investment Loan">Investment Loan</option>
              <option value="Pension Loan">Pension Loan</option>
              <option value="Productivity Loan">Productivity Loan</option>
              <option value="Personal Loan">Personal Loan</option>
              <option value="Salary Loan">Salary Loan</option>
              <option value="Micro-Finance Loan">Micro-Finance Loan</option>
            </select>
          </div>
          <div class="card">
            <div class="card__label">Collateral</div>
            <select id="collateral" required>
              <option value="" disabled selected>— Select Collateral —</option>
              <option value="Post-Dated Check">Post-Dated Check</option>
              <option value="Real Property">Real Property</option>
              <option value="Chattels / Movable Assets">Chattels / Movable Assets</option>
            </select>
          </div>
          <div class="card">
            <div class="card__label">SOA Status</div>
            <select id="soa_status" required>
              <option value="" disabled selected>— Select Status —</option>
              <option value="Updated">Updated</option>
              <option value="Pending">Pending</option>
              <option value="Overdue">Overdue</option>
            </select>
          </div>
          <div class="card">
            <div class="card__label">Amortization type</div>
            <select id="amort_type" required>
              <option value="" disabled selected>— Select Rules —</option>
              <option value="Straight-line">Straight-line</option>
              <option value="Diminishing balance">Diminishing balance</option>
              <option value="Manual">Manual</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row" id="real_property_section" style="display: none;">
        <div class="section-label">Real Property Details</div>
        <div class="grid-4">
          <div class="card">
            <div class="card__label">TCT No.</div>
            <input type="text" id="rp_tct" placeholder="Enter TCT Number">
          </div>
          <div class="card">
            <div class="card__label">Tax Declaration No.</div>
            <input type="text" id="rp_tax" placeholder="Enter Tax Dec Number">
          </div>
          <div class="card">
            <div class="card__label">Real Property Payments</div>
            <select id="rp_payments">
              <option value="" disabled selected>— Select Payment State —</option>
              <option value="Updated">Updated</option>
              <option value="Not Updated">Not Updated</option>
              <option value="Pending">Pending</option>
            </select>
          </div>
          <div class="card">
            <div class="card__label">Required Uploads (.PDF Only)</div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label class="rp-upload-label" id="lbl_undertaking">
                <span>Undertaking File</span>
                <input type="file" id="file_undertaking" accept=".pdf" style="display:none;">
              </label>
              <label class="rp-upload-label" id="lbl_deed">
                <span>Assignment of Deed</span>
                <input type="file" id="file_deed" accept=".pdf" style="display:none;">
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="section-label">Numeric Terms & Figures</div>
        <div class="grid-4">
          <div class="card">
            <div class="card__label">Principal Amount (₱)</div>
            <input type="number" id="principal" min="0" step="0.01" placeholder="0.00" required>
          </div>
          <div class="card">
            <div class="card__label">Interest Rate (%)</div>
            <input type="number" id="interest_rate" readonly placeholder="Calculated from type">
          </div>
          <div class="card">
            <div class="card__label">Terms (Months)</div>
            <input type="number" id="terms" min="1" max="360" placeholder="Duration count" required>
          </div>
          <div class="card">
            <div class="card__label">Start Date</div>
            <input type="date" id="start_date" required>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="section-label">Deducted Fee Calculations</div>
        <div class="grid-4">
          <div class="card">
            <div class="card__label">Processing Fee (2%)</div>
            <input type="text" id="fee_processing" readonly value="₱0.00">
          </div>
          <div class="card">
            <div class="card__label">Insurance Protection</div>
            <input type="text" id="fee_insurance" readonly value="₱0.00">
          </div>
          <div class="card">
            <div class="card__label">Notarial Fee (Fixed)</div>
            <input type="text" id="fee_notarial" readonly value="₱400.00">
          </div>
        </div>
      </div>
    </form>

    <div class="row">
      <div class="section-label">Loan Calculation Summary</div>
      <div class="summary-strip">
        <div class="stat stat--highlight">
          <div class="stat__label">Amortization / Period</div>
          <div class="stat__value" id="sum_amortization">₱0.00</div>
        </div>
        <div class="stat">
          <div class="stat__label">Net Proceeds</div>
          <div class="stat__value" id="sum_net_proceeds">₱0.00</div>
        </div>
        <div class="stat">
          <div class="stat__label">Total Interest Cost</div>
          <div class="stat__value" id="sum_interest">₱0.00</div>
        </div>
        <div class="stat">
          <div class="stat__label">Total Gross Repayment</div>
          <div class="stat__value" id="sum_total_payment">₱0.00</div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="table-card">
        <div class="table-card__head">
          <div class="table-card__title">Amortization Schedule Ledger</div>
          <div id="terms_badge" style="font-size:11px; opacity:0.6; font-family:var(--font-mono)">0 Months</div>
        </div>
        <div class="tbl-wrap">
          <table id="schedule_table">
            <thead>
              <tr>
                <th style="text-align: left;">Period</th>
                <th>Due Date</th>
                <th>Monthly Amortization</th>
                <th>Principal Component</th>
                <th>Interest Component</th>
                <th>Remaining Principal</th>
              </tr>
            </thead>
            <tbody id="schedule_body">
              <tr>
                <td colspan="6" style="text-align: center; color: var(--t3); padding: 30px;">
                  Fill out parameters above to dynamically compute schedule data.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <button type="button" class="c-btn-save" id="btn_save_loan">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        Save Loan Configuration
      </button>
    </div>

    <div class="row" style="margin-top: 40px;">
      <div class="section-label">Application of Payment Ledger System</div>
      <div class="card" style="border-style: dashed; text-align: center; color: var(--t2); padding: 40px 20px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 12px; opacity: 0.5;">
          <rect x="2" y="4" width="20" height="16" rx="2" />
          <line x1="2" y1="10" x2="22" y2="10" />
        </svg>
        <span class="font-medium text-sm block mb-1">Payment Tracking Queue Operational Area</span>
        <p class="text-xs text-gray-400 max-w-md mx-auto">Once the amortization schedule above is committed, individual transaction entries, transaction validations, receipts, and balancing ledgers clear through this workspace.</p>
      </div>
    </div>
  </div>

  <script>
    const SESSION_MEMBER_ID = <?= json_encode($memberId) ?>;
    const SAVED_LOAN_DATA = <?= json_encode($existingLoan) ?>;
    const SAVED_SCHEDULE_DATA = <?= json_encode($existingSchedule) ?>;
  </script>
  <script src="assets/js/amortization-engine.js"></script>
</body>

</html>