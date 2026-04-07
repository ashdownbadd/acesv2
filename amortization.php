<?php

/**
 * ACESv3 - Loan Amortization & Payment Tracking
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once 'core/db.php';
$pdo = (new DB())->connect();

// 1. AUTHENTICATION & NAVBAR DATA MAPPING
// We trust the session data already established in index.php
$user = [
  'name'   => $_SESSION['name'] ?? 'Admin',
  'role'   => $_SESSION['role'] ?? 'Staff',
  'avatar' => $_SESSION['avatar'] ?? ''
];

// Check if the role is admin (case-insensitive)
$isAdmin = (strtolower($user['role']) === 'admin');

// ============================================================
// API — handle AJAX requests
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  header('Content-Type: application/json');
  $body = json_decode(file_get_contents('php://input'), true);

  switch ($_GET['action']) {

    case 'save_loan':
      try {
        $pdo->beginTransaction();
        $loan = $body['loan'];

        if (!empty($loan['id'])) {
          $stmt = $pdo->prepare("
                        UPDATE loans SET
                            member_id=:member_id,
                            loan_type=:loan_type, collateral=:collateral, soa_status=:soa_status,
                            amort_type=:amort_type, mf_freq=:mf_freq,
                            principal_amount=:principal_amount, interest_rate=:interest_rate,
                            terms_months=:terms_months, start_date=:start_date,
                            manual_payment=:manual_payment,
                            monthly_amortization=:monthly_amortization,
                            total_interest=:total_interest, total_payment=:total_payment,
                            processing_fee=:processing_fee, insurance=:insurance,
                            notarial_fee=:notarial_fee, net_proceeds=:net_proceeds
                        WHERE id=:id
                    ");
          $stmt->execute($loan);
          $loan_id = $loan['id'];
        } else {
          $stmt = $pdo->prepare("
                        INSERT INTO loans
                            (member_id, loan_type, collateral, soa_status, amort_type, mf_freq,
                             principal_amount, interest_rate, terms_months, start_date,
                             manual_payment, monthly_amortization, total_interest,
                             total_payment, processing_fee, insurance, notarial_fee, net_proceeds)
                        VALUES
                            (:member_id, :loan_type, :collateral, :soa_status, :amort_type, :mf_freq,
                             :principal_amount, :interest_rate, :terms_months, :start_date,
                             :manual_payment, :monthly_amortization, :total_interest,
                             :total_payment, :processing_fee, :insurance, :notarial_fee, :net_proceeds)
                    ");
          $stmt->execute($loan);
          $loan_id = $pdo->lastInsertId();
        }

        $pdo->prepare("DELETE FROM loan_schedule WHERE loan_id = ?")->execute([$loan_id]);
        $sched = $pdo->prepare("
                    INSERT INTO loan_schedule
                        (loan_id, period, principal, interest, payment, due_date,
                         status, rem_principal, rem_interest, rem_penalty, remarks)
                    VALUES
                        (:loan_id, :period, :principal, :interest, :payment, :due_date,
                         :status, :rem_principal, :rem_interest, :rem_penalty, :remarks)
                ");
        foreach ($body['schedule'] as $row) {
          $sched->execute(array_merge(['loan_id' => $loan_id], $row));
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'loan_id' => $loan_id]);
      } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
      }
      break;

    case 'save_payment':
      try {
        $p = $body['payment'];
        $stmt = $pdo->prepare("
                    INSERT INTO loan_payments
                        (loan_id, amount_paid, penalty_applied, interest_applied,
                         principal_applied, excess, payment_type, remarks)
                    VALUES
                        (:loan_id, :amount_paid, :penalty_applied, :interest_applied,
                         :principal_applied, :excess, :payment_type, :remarks)
                ");
        $stmt->execute($p);
        echo json_encode(['success' => true, 'payment_id' => $pdo->lastInsertId()]);
      } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
      }
      break;

    case 'update_schedule':
      try {
        $stmt = $pdo->prepare("
                    UPDATE loan_schedule
                    SET status=:status, rem_principal=:rem_principal,
                        rem_interest=:rem_interest, rem_penalty=:rem_penalty,
                        remarks=:remarks
                    WHERE loan_id=:loan_id AND period=:period
                ");
        foreach ($body['rows'] as $row) {
          $stmt->execute($row);
        }
        echo json_encode(['success' => true]);
      } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
      }
      break;

    case 'load_by_member':
      try {
        $mid  = intval($body['member_id']);
        $loan = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? ORDER BY created_at DESC LIMIT 1");
        $loan->execute([$mid]);
        $loanData = $loan->fetch();

        if (!$loanData) {
          echo json_encode(['loan' => null]);
          break;
        }

        $sched = $pdo->prepare("SELECT * FROM loan_schedule WHERE loan_id = ? ORDER BY period");
        $sched->execute([$loanData['id']]);
        $schedData = $sched->fetchAll();

        $pays = $pdo->prepare("SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY paid_at");
        $pays->execute([$loanData['id']]);
        $paysData = $pays->fetchAll();

        echo json_encode(['loan' => $loanData, 'schedule' => $schedData, 'payments' => $paysData]);
      } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
      }
      break;

    default:
      http_response_code(400);
      echo json_encode(['error' => 'Unknown action']);
  }
  exit;
}

// ── Member context from URL ──────────────────────────────────────────
$memberId   = intval($_GET['member_id'] ?? 0);
$memberName = '';
$existingLoan = null;

if ($memberId > 0) {
  try {
    $mStmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM members WHERE id = ?");
    $mStmt->execute([$memberId]);
    $mRow = $mStmt->fetch();
    if ($mRow) {
      $memberName = trim(($mRow['first_name'] ?? '') . ' ' . ($mRow['middle_name'] ?? '') . ' ' . ($mRow['last_name'] ?? ''));
    }

    $lStmt = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? ORDER BY created_at DESC LIMIT 1");
    $lStmt->execute([$memberId]);
    $loanRow = $lStmt->fetch();

    if ($loanRow) {
      $sStmt = $pdo->prepare("SELECT * FROM loan_schedule WHERE loan_id = ? ORDER BY period");
      $sStmt->execute([$loanRow['id']]);
      $pStmt = $pdo->prepare("SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY paid_at");
      $pStmt->execute([$loanRow['id']]);
      $existingLoan = [
        'loan'     => $loanRow,
        'schedule' => $sStmt->fetchAll(),
        'payments' => $pStmt->fetchAll(),
      ];
    }
  } catch (PDOException $e) {
    // Log error if needed
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loan Amortization Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Syne:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
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
      --border2: rgba(0, 0, 0, 0.15);
      --danger: #d93d3d;
      --ok: #27a858;
      --font-main: 'Poppins', sans-serif;
      --font-heading: 'Syne', sans-serif;
      --font-mono: 'IBM Plex Mono', monospace;
      --status-active: #10b981;
      --status-deceased: #4b5563;
      --status-delisted: #ef4444;
      --status-hold: #3b82f6;
      --status-overdue: #f59e0b;
      --status-litigation: #8b5cf6;
      --radius: 10px;
      --gap: clamp(8px, 1.5vw, 12px);
      --pad-h: clamp(12px, 4vw, 32px);
      --pad-v: clamp(24px, 4vw, 40px);
      --pad-card: clamp(10px, 1.8vw, 14px) clamp(12px, 2vw, 16px);
    }

    [data-theme='dark'] {
      --bg: #0c0b09;
      --surface: #181613;
      --raised: #201e1a;
      --rim: #2c2a25;
      --gold: #f59b0a;
      --gold-dim: rgba(245, 167, 42, 0.13);
      --text: #f0ece58c;
      --t2: rgba(240, 236, 229, 0.55);
      --t3: rgba(240, 236, 229, 0.28);
      --border: rgba(255, 255, 255, 0.07);
      --border2: rgba(255, 255, 255, 0.13);
      --danger: #ff5e5e;
      --ok: #4ade80;
      --status-active: #4ade80;
      --status-deceased: #9ca3af;
      --status-delisted: #f87171;
      --status-hold: #60a5fa;
      --status-overdue: #fbbf24;
      --status-litigation: #c084fc;
    }

    html,
    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--font-main);
      font-size: clamp(13px, 1.4vw, 15px);
      min-height: 100vh;
      line-height: 1.6;
    }

    .page {
      max-width: 1100px;
      margin: 0 auto;
      padding: var(--pad-v) var(--pad-h);
    }

    .section-label {
      font-size: clamp(9px, 1vw, 10px);
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--t3);
      margin-bottom: clamp(6px, 1vw, 10px);
      padding-left: 2px;
      font-family: var(--font-heading);
    }

    .row {
      margin-bottom: var(--gap);
    }

    .grid-4,
    .grid-5 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(140px, 100%), 1fr));
      gap: var(--gap);
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: var(--pad-card);
    }

    .card__label {
      font-size: clamp(9px, 1vw, 10px);
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--t3);
      margin-bottom: 8px;
      font-family: var(--font-heading);
    }

    .card select,
    .card input[type=number],
    .card input[type=date] {
      width: 100%;
      background: var(--raised);
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: clamp(6px, 1vw, 8px) 10px;
      color: var(--text);
      font-family: var(--font-main);
      font-size: clamp(12px, 1.3vw, 13px);
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

    .card input::placeholder {
      color: var(--t3);
    }

    .card select:invalid,
    .card select option[value=""] {
      color: var(--t3);
    }

    .card select option:not([value=""]) {
      color: var(--text);
    }

    .card--result {
      background: var(--gold);
      border-color: var(--gold);
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 6px;
    }

    .card--result .card__label {
      color: rgba(255, 255, 255, 0.7);
    }

    .result-value {
      font-family: var(--font-heading);
      font-size: clamp(20px, 4vw, 30px);
      color: #fff;
      font-weight: 700;
      letter-spacing: -.01em;
      line-height: 1;
    }

    .result-sub {
      font-size: clamp(10px, 1.1vw, 11px);
      color: rgba(255, 255, 255, 0.6);
      margin-top: 2px;
    }

    .summary-strip {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(110px, 100%), 1fr));
      gap: var(--gap);
      margin-bottom: var(--gap);
    }

    .summary-strip.hidden {
      opacity: 0;
      pointer-events: none;
    }

    .stat {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: var(--pad-card);
    }

    .stat__label {
      font-size: clamp(9px, 1vw, 10px);
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--t3);
      margin-bottom: 6px;
      font-family: var(--font-heading);
    }

    .stat__value {
      font-family: var(--font-mono);
      font-size: clamp(13px, 2.5vw, 18px);
      color: var(--text);
      font-weight: 500;
      letter-spacing: -.01em;
    }

    .stat--highlight .stat__value {
      color: var(--gold);
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
      padding: clamp(10px, 1.5vw, 14px) clamp(12px, 2vw, 18px);
      border-bottom: 1px solid var(--border);
      background: var(--raised);
    }

    .table-card__title {
      font-size: clamp(10px, 1.2vw, 12px);
      font-weight: 700;
      letter-spacing: .07em;
      text-transform: uppercase;
      color: var(--t2);
      font-family: var(--font-heading);
    }

    .table-badge {
      font-size: clamp(10px, 1.1vw, 11px);
      color: var(--t3);
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2px 10px;
      white-space: nowrap;
      font-family: var(--font-mono);
    }

    .tbl-wrap {
      overflow-x: auto;
      overflow-y: auto;
      max-height: clamp(220px, 40vh, 360px);
      -webkit-overflow-scrolling: touch;
    }

    table {
      width: 100%;
      min-width: 420px;
      border-collapse: collapse;
      font-size: clamp(11px, 1.2vw, 12px);
    }

    thead th {
      padding: clamp(7px, 1vw, 9px) clamp(10px, 1.5vw, 16px);
      text-align: right;
      font-size: clamp(9px, 1vw, 10px);
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--t3);
      background: var(--raised);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      white-space: nowrap;
      font-family: var(--font-heading);
    }

    thead th:first-child {
      text-align: left;
    }

    tbody td {
      padding: clamp(7px, 1vw, 9px) clamp(10px, 1.5vw, 16px);
      text-align: right;
      border-bottom: 1px solid var(--border);
      color: var(--text);
      font-family: var(--font-mono);
      font-size: clamp(11px, 1.2vw, 12px);
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }

    tbody td:first-child {
      text-align: left;
      font-family: var(--font-main);
      color: var(--t2);
      font-weight: 500;
      white-space: nowrap;
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    tbody tr:hover td {
      background: var(--gold-dim);
    }

    .empty-row td {
      text-align: center !important;
      color: var(--t3);
      font-family: var(--font-main) !important;
      padding: clamp(20px, 4vw, 36px) 16px !important;
      white-space: normal !important;
    }

    tbody tr.row--overdue td {
      background: rgba(217, 61, 61, 0.06);
      color: var(--danger);
    }

    tbody tr.row--near-due td {
      background: rgba(245, 155, 10, 0.07);
    }

    tbody tr.row--paid td {
      background: rgba(39, 168, 88, 0.06);
      color: var(--t3);
    }

    tbody tr.row--overdue:hover td {
      background: rgba(217, 61, 61, 0.1);
    }

    tbody tr.row--near-due:hover td {
      background: rgba(245, 155, 10, 0.12);
    }

    tbody tr.row--paid:hover td {
      background: rgba(39, 168, 88, 0.1);
    }

    .status-select {
      appearance: none;
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2px 22px 2px 8px;
      font-family: var(--font-main);
      font-size: 10px;
      font-weight: 600;
      letter-spacing: .04em;
      text-transform: uppercase;
      cursor: pointer;
      outline: none;
      background-repeat: no-repeat;
      background-position: right 6px center;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='6' viewBox='0 0 8 6'%3E%3Cpath d='M1 1l3 3 3-3' stroke='%23888' stroke-width='1.2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    }

    .status-select.s-pending {
      background-color: var(--raised);
      color: var(--t2);
      border-color: var(--rim);
    }

    .status-select.s-paid {
      background-color: rgba(39, 168, 88, 0.1);
      color: var(--ok);
      border-color: rgba(39, 168, 88, 0.3);
    }

    .status-select.s-overdue {
      background-color: rgba(217, 61, 61, 0.08);
      color: var(--danger);
      border-color: rgba(217, 61, 61, 0.25);
    }

    td.due-date {
      font-family: var(--font-main);
      font-size: 11px;
      color: var(--t3);
    }

    td.due-near {
      color: var(--status-overdue);
      font-weight: 600;
    }

    td.due-over {
      color: var(--danger);
      font-weight: 600;
    }

    .card--computed {
      background: var(--raised);
      border-color: var(--border);
    }

    .card__note {
      font-size: 9px;
      font-weight: 400;
      letter-spacing: 0;
      text-transform: none;
      color: var(--t3);
      margin-left: 2px;
    }

    .computed-value {
      font-family: var(--font-mono);
      font-size: clamp(14px, 2vw, 18px);
      font-weight: 500;
      color: var(--text);
      letter-spacing: -.01em;
    }

    .card--net {
      background: var(--gold);
      border-color: var(--gold);
    }

    .card--net .card__label,
    .card--net .card__note {
      color: rgba(255, 255, 255, 0.65);
    }

    .card--net .computed-value {
      color: #fff;
      font-weight: 700;
    }


    .btn-save {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: clamp(5px, 1vw, 7px) clamp(10px, 1.5vw, 14px);
      border-radius: 6px;
      border: none;
      background: var(--gold);
      color: #fff;
      font-family: var(--font-main);
      font-size: clamp(10px, 1.1vw, 11px);
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      cursor: pointer;
      white-space: nowrap;
    }

    .btn-save:hover {
      filter: brightness(1.08);
    }

    .btn-save:active {
      transform: scale(.98);
    }

    .toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--text);
      font-size: 12px;
      font-weight: 500;
      font-family: var(--font-main);
      padding: 10px 18px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      opacity: 0;
      transform: translateY(10px);
      pointer-events: none;
      z-index: 999;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .toast::before {
      content: '';
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--ok);
      flex-shrink: 0;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .theme-toggle {
      position: fixed;
      top: 16px;
      right: 16px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 5px 12px;
      font-family: var(--font-main);
      font-size: 11px;
      font-weight: 600;
      color: var(--t2);
      cursor: pointer;
      z-index: 100;
    }

    .theme-toggle:hover {
      background: var(--raised);
    }

    /* --- 1. Screen Behavior (Prevents ghosting on the UI) --- */
    @media screen {
      #soa-print-section {
        display: none !important;
      }
    }

    /* --- 2. Print Behavior (The Word Document Layout) --- */
    @media print {

      /* Hide everything except the SOA container */
      body>*:not(#soa-print-section) {
        display: none !important;
      }

      #soa-print-section {
        display: block !important;
        visibility: visible !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        color: #000 !important;
        background: #fff !important;
        font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
        line-height: 1.5;
      }

      /* Table Styling for Professional Document */
      .soa-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
      }

      .soa-table th {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #333;
        padding: 12px 8px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: right;
      }

      .soa-table td {
        border-bottom: 1px solid #eee;
        padding: 10px 8px;
        font-size: 11px;
        text-align: right;
      }

      /* Alignment & Accents */
      .soa-table .text-left {
        text-align: left;
      }

      .soa-table .penalty-cell {
        color: #c0392b !important;
        font-weight: bold;
      }

      /* Remove URL/Header/Footer added by browsers */
      @page {
        margin: 1.5cm;
      }
    }

    /* --- 3. Dashboard UI Elements --- */
    .no-print {
      display: none !important;
    }

    .btn-soa {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: clamp(5px, 1vw, 7px) clamp(10px, 1.5vw, 14px);
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--raised);
      color: var(--text);
      font-family: var(--font-main);
      font-size: clamp(10px, 1.1vw, 11px);
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      cursor: pointer;
      white-space: nowrap;
    }

    .btn-soa:hover {
      background: var(--gold-dim);
      border-color: var(--gold);
      color: var(--gold);
    }

    /* Icons within the button */
    .btn-soa svg {
      opacity: 0.8;
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body>

  <?php include 'views/auth/partials/navbar.php'; ?>

  <div class="page">
    <a href="index.php?page=dashboard"
      style="font-size:11px;font-weight:600;color:var(--t3);text-decoration:none;display:flex;align-items:center;gap:6px;padding:6px 14px;border:1px solid var(--border);border-radius:20px;font-family:var(--font-main);"
      onmouseover="this.style.background='var(--raised)'"
      onmouseout="this.style.background=''">
      <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M10 12L6 8l4-4" />
      </svg>
      Back to Dashboard
    </a>



    <!-- PHP data injected for JS to consume -->
    <script>
      const PHP_MEMBER_ID = <?= json_encode($memberId) ?>;
      const PHP_MEMBER_NAME = <?= json_encode($memberName) ?>;
      const PHP_LOAN_DATA = <?= json_encode($existingLoan) ?>;
    </script>

    <!-- Row 1 -->
    <div class="row">
      <div class="section-label">Loan parameters</div>
      <div class="grid-4">
        <div class="card">
          <div class="card__label">Loan type</div>
          <select id="loan_type">
            <option value="" disabled selected>— Select —</option>
            <option>Bridge Financing</option>
            <option>Investment Loan</option>
            <option>Pension Loan</option>
            <option>Productivity Loan</option>
            <option>Personal Loan</option>
            <option>Salary Loan</option>
            <option>Micro-Finance Loan</option>
          </select>
        </div>
        <div class="card">
          <div class="card__label">Collateral</div>
          <select id="collateral">
            <option value="" disabled selected>— Select —</option>
            <option>Post-Dated Check</option>
            <option>Real Property</option>
            <option>Chattels / Movable Assets</option>
          </select>
        </div>
        <div class="card">
          <div class="card__label">SOA status</div>
          <select id="soa">
            <option value="" disabled selected>— Select —</option>
            <option>Updated</option>
            <option>Pending</option>
            <option>Overdue</option>
          </select>
        </div>
        <div class="card">
          <div class="card__label">Amortization type</div>
          <select id="amort_type">
            <option value="" disabled selected>— Select —</option>
            <option value="straight">Straight-line</option>
            <option value="diminishing">Diminishing balance</option>
            <option value="manual">Manual</option>
          </select>
        </div>
        <div class="card" id="mf_freq_card" style="display:none">
          <div class="card__label">Payment frequency</div>
          <select id="mf_freq">
            <option value="" disabled selected>— Select —</option>
            <option value="monthly">Monthly</option>
            <option value="bi-monthly">Bi-Monthly</option>
            <option value="weekly">Weekly</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Row 2 -->
    <div class="row">
      <div class="section-label">Figures</div>
      <div class="grid-5">
        <div class="card">
          <div class="card__label">Principal amount (₱)</div><input type="number" id="principal" placeholder="0.00">
        </div>
        <div class="card">
          <div class="card__label">Interest rate (%)</div><input type="number" id="interest" placeholder="0.00">
        </div>
        <div class="card">
          <div class="card__label">Terms (months)</div><input type="number" id="months" placeholder="0">
        </div>
        <div class="card">
          <div class="card__label">Start date</div><input type="date" id="start_date">
        </div>
        <div class="card" id="manual_card" style="display:none">
          <div class="card__label">Manual monthly payment</div><input type="number" id="manual_payment" placeholder="0.00">
        </div>
        <div class="card card--result">
          <div>
            <div class="card__label">Monthly amortization</div>
            <div class="result-value" id="monthly_display">₱ —</div>
            <div class="result-sub" id="result_sub">Updates automatically</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 3: Deductions -->
    <div class="row" id="loan_details_row" style="display:none">
      <div class="section-label">Loan deductions &amp; net proceeds</div>
      <div class="grid-4">
        <div class="card card--computed">
          <div class="card__label">Processing Fee <span class="card__note">(2% of principal)</span></div>
          <div class="computed-value" id="c_processing">₱ —</div>
        </div>
        <div class="card card--computed">
          <div class="card__label">Insurance <span class="card__note">(principal ÷ 1000 × 1.2 × terms)</span></div>
          <div class="computed-value" id="c_insurance">₱ —</div>
        </div>
        <div class="card card--computed">
          <div class="card__label">Notarial Fee <span class="card__note">(fixed)</span></div>
          <div class="computed-value" id="c_notarial">₱400.00</div>
        </div>
        <div class="card card--computed card--net">
          <div class="card__label">Net Proceeds</div>
          <div class="computed-value" id="c_net">₱ —</div>
        </div>
      </div>
    </div>

    <!-- Summary Strip -->
    <div class="summary-strip hidden" id="summary_strip">
      <div class="stat">
        <div class="stat__label">Total interest</div>
        <div class="stat__value" id="s_interest">—</div>
      </div>
      <div class="stat">
        <div class="stat__label">Total payment</div>
        <div class="stat__value" id="s_total">—</div>
      </div>
      <div class="stat stat--highlight">
        <div class="stat__label" id="s_rate_label">Fixed Principal</div>
        <div class="stat__value" id="s_rate">—</div>
      </div>
      <div class="stat">
        <div class="stat__label" id="s_months_label">Term length</div>
        <div class="stat__value" id="s_months">—</div>
      </div>
    </div>

    <!-- Payment Summary Panel -->
    <div id="overdue_panel" style="display:none;margin-bottom:var(--gap)">
      <div class="section-label">Payment summary — application of payment</div>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);background:var(--raised);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start">
            <div>
              <div style="font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);font-family:var(--font-heading);margin-bottom:3px">Total Principal</div>
              <div style="font-family:var(--font-mono);font-size:16px;font-weight:600;color:var(--text)" id="od_total_principal">₱0.00</div>
            </div>
            <div>
              <div style="font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);font-family:var(--font-heading);margin-bottom:3px">Total Interest</div>
              <div style="font-family:var(--font-mono);font-size:16px;font-weight:600;color:var(--text)" id="od_total_interest">₱0.00</div>
            </div>
            <div id="od_penalty_wrap" style="display:none">
              <div style="font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--danger);font-family:var(--font-heading);margin-bottom:3px">
                Total Penalty
                <span id="od_overdue_badge" style="display:none;font-size:9px;padding:1px 6px;border-radius:20px;background:rgba(217,61,61,0.1);color:var(--danger);border:1px solid rgba(217,61,61,0.2);margin-left:4px;vertical-align:middle"></span>
              </div>
              <div style="font-family:var(--font-mono);font-size:16px;font-weight:600;color:var(--danger)" id="od_total_penalty">₱0.00</div>
            </div>
            <div style="border-left:1px solid var(--border);padding-left:24px">
              <div style="font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--t2);font-family:var(--font-heading);margin-bottom:3px">Grand Total Due</div>
              <div style="font-family:var(--font-mono);font-size:18px;font-weight:700;color:var(--text)" id="od_grand_total">₱0.00</div>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <input type="number" id="od_amount_paid" placeholder="Enter amount paid" style="background:var(--raised);border:1px solid var(--border);border-radius:6px;padding:8px 12px;color:var(--text);font-family:var(--font-mono);font-size:13px;outline:none;width:200px">
            <button onclick="applyGlobalPayment()" style="padding:8px 14px;border-radius:6px;border:none;background:var(--gold);color:#fff;font-family:var(--font-main);font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap">Apply</button>
          </div>
        </div>
        <div id="od_breakdown" style="padding:12px 18px;font-size:12px;color:var(--t3);font-family:var(--font-main)">Enter an amount above to see how it will be applied.</div>
      </div>
    </div>

    <!-- Amortization Table -->
    <div class="table-card">
      <div class="table-card__head">
        <span class="table-card__title">Amortization table</span>
        <div style="display:flex;align-items:center;gap:10px">
          <span class="table-badge" id="row_count">No data</span>
          <button type="button" class="btn-soa" onclick="exportSOAExcel()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="8" y1="13" x2="16" y2="13" />
              <line x1="8" y1="17" x2="16" y2="17" />
              <line x1="8" y1="9" x2="10" y2="9" />
            </svg>
            Export SOA (Excel)
          </button>
          <button class="btn-save" onclick="saveToDB()">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M13 2H5L2 5v9a1 1 0 001 1h10a1 1 0 001-1V3a1 1 0 00-1-1z" />
              <path d="M10 2v4H5V2" />
              <path d="M4 9h8" />
            </svg>
            Save to DB
          </button>
        </div>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr id="table_head_row">
              <th>Period</th>
              <th>Opening Balance</th>
              <th>Amortization</th>
              <th>Interest</th>
              <th>Payment</th>
              <th>Due Date</th>
              <th>Status</th>
              <th style="color:var(--danger)">Penalty</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody id="sched_body">
            <tr class="empty-row">
              <td colspan="9">Enter loan details above to generate the schedule</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payment Ledger -->
    <div id="ledger_section" style="display:none;margin-top:var(--gap)">
      <div class="table-card">
        <div class="table-card__head">
          <span class="table-card__title">Recorded payments</span>
          <div style="display:flex;align-items:center;gap:10px">
            <span class="table-badge" id="ledger_count">0 records</span>
          </div>
        </div>
        <div class="tbl-wrap" style="max-height:280px">
          <table>
            <thead>
              <tr>
                <th style="text-align:left">#</th>
                <th style="text-align:left">Date &amp; Time</th>
                <th style="text-align:right">Amount Paid</th>
                <th style="text-align:right;color:var(--danger)">→ Penalty</th>
                <th style="text-align:right;color:var(--status-overdue)">→ Interest</th>
                <th style="text-align:right;color:var(--t2)">→ Principal</th>
                <th style="text-align:right">Excess</th>
                <th style="text-align:left">Type</th>
                <th style="text-align:left">Remarks</th>
                <th style="text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody id="ledger_body">
              <tr class="empty-row">
                <td colspan="10">No payments recorded yet</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
  <div class="toast" id="toast">Saved to database</div>

  <script>
    // ── Helpers ──────────────────────────────────────────────────────────
    function peso(n) {
      return "₱" + n.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function fmtDate(d) {
      return d.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric"
      });
    }

    function fmtDateTime(d) {
      return fmtDate(d) + " " + d.toLocaleTimeString("en-PH", {
        hour: "2-digit",
        minute: "2-digit"
      });
    }

    function nextDate(d, freq) {
      const nd = new Date(d);
      if (freq === "monthly") nd.setMonth(nd.getMonth() + 1);
      else if (freq === "bi-monthly") nd.setDate(nd.getDate() + 15);
      else if (freq === "weekly") nd.setDate(nd.getDate() + 7);
      else nd.setMonth(nd.getMonth() + 1);
      return nd;
    }

    function dueness(dueDate) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const due = new Date(dueDate);
      due.setHours(0, 0, 0, 0);
      const diff = Math.ceil((due - today) / 86400000);
      if (diff < 0) return "overdue";
      if (diff <= 3) return "near-due";
      return "pending";
    }

    function monthsOverdue(dueDate) {
      if (!dueDate) return 0;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const due = new Date(dueDate);
      due.setHours(0, 0, 0, 0);
      if (due >= today) return 0;
      const mo = (today.getFullYear() - due.getFullYear()) * 12 + (today.getMonth() - due.getMonth());
      return Math.max(1, mo);
    }

    function calcPenalty(pp, int, dueDate) {
      const mo = monthsOverdue(dueDate);
      if (mo === 0) return 0;
      return (pp + int) * 0.03 * mo;
    }

    function applyPayment(amountPaid, penalty, interest, principal) {
      let r = amountPaid;
      const a = {
        penalty: 0,
        interest: 0,
        principal: 0,
        remaining: 0
      };
      if (r > 0 && penalty > 0) {
        a.penalty = Math.min(r, penalty);
        r -= a.penalty;
      }
      if (r > 0 && interest > 0) {
        a.interest = Math.min(r, interest);
        r -= a.interest;
      }
      if (r > 0 && principal > 0) {
        a.principal = Math.min(r, principal);
        r -= a.principal;
      }
      a.remaining = r;
      return a;
    }

    function chip(label, color, filled) {
      const bg = filled ? color : "transparent",
        text = filled ? "#fff" : color;
      return `<span style="display:inline-block;font-size:9px;font-weight:600;letter-spacing:.04em;padding:2px 7px;border-radius:20px;background:${bg};color:${text};border:1px solid ${color};white-space:nowrap">${label}</span>`;
    }

    // ── DB API ────────────────────────────────────────────────────────────
    let currentLoanId = null;

    // ── Load existing loan from PHP injection on page start ───────────
    function loadFromDb() {
      if (!PHP_LOAN_DATA || !PHP_LOAN_DATA.loan) return;

      const loan = PHP_LOAN_DATA.loan;
      currentLoanId = loan.id;

      // Populate form fields
      const fieldMap = {
        loan_type: loan.loan_type,
        collateral: loan.collateral,
        soa: loan.soa_status,
        amort_type: loan.amort_type,
        mf_freq: loan.mf_freq,
        principal: loan.principal_amount,
        interest: loan.interest_rate,
        months: loan.terms_months,
        start_date: loan.start_date,
        manual_payment: loan.manual_payment,
      };
      Object.entries(fieldMap).forEach(([id, val]) => {
        const el = document.getElementById(id);
        if (el && val !== null && val !== undefined) el.value = val;
      });

      // Mark interest as user-edited so auto-rate doesn't overwrite
      document.getElementById("interest").dataset.userEdited = "true";

      // Trigger calculate to rebuild the table
      calculate();

      // Restore per-row statuses and rem values from saved schedule
      if (PHP_LOAN_DATA.schedule && PHP_LOAN_DATA.schedule.length > 0) {
        const rows = document.querySelectorAll("#sched_body tr:not(.empty-row)");
        PHP_LOAN_DATA.schedule.forEach((saved, i) => {
          const tr = rows[i];
          if (!tr) return;
          tr.dataset.remPrincipal = saved.rem_principal;
          tr.dataset.remInterest = saved.rem_interest;
          tr.dataset.remPenalty = saved.rem_penalty;
          tr.dataset.origPenalty = saved.rem_penalty; // treat saved rem as orig since penalty may have changed

          const sel = tr.querySelector(".status-select");
          if (sel && saved.status) {
            sel.value = saved.status;
            onStatusChange(sel);
          }

          const remarks = tr.querySelector(".remarks-input");
          if (remarks && saved.remarks) remarks.value = saved.remarks;
        });
      }

      // Restore payment ledger
      if (PHP_LOAN_DATA.payments && PHP_LOAN_DATA.payments.length > 0) {
        PHP_LOAN_DATA.payments.forEach(p => {
          paymentLedger.push({
            id: p.id,
            datetime: p.paid_at,
            periods: "—",
            amountPaid: parseFloat(p.amount_paid),
            penalty: parseFloat(p.penalty_applied),
            interest: parseFloat(p.interest_applied),
            principal: parseFloat(p.principal_applied),
            excess: parseFloat(p.excess),
            type: p.payment_type,
            remarks: p.remarks || "",
            savedToDb: true,
          });
        });
        renderLedger();
      }

      updateOverduePanel();
    }

    async function api(action, body) {
      const res = await fetch(`?action=${action}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
      });
      return res.json();
    }

    async function saveToDB() {
      const fields = ["loan_type", "collateral", "soa", "amort_type", "mf_freq", "principal", "interest", "months", "start_date", "manual_payment"];
      const loan = {
        member_id: MEMBER_ID || null,
        member_id: PHP_MEMBER_ID || null,
        loan_type: document.getElementById("loan_type").value || null,
        collateral: document.getElementById("collateral").value || null,
        soa_status: document.getElementById("soa").value || 'Pending',
        amort_type: document.getElementById("amort_type").value || null,
        mf_freq: document.getElementById("mf_freq").value || null,
        principal_amount: parseFloat(document.getElementById("principal").value) || 0,
        interest_rate: parseFloat(document.getElementById("interest").value) || 0,
        terms_months: parseInt(document.getElementById("months").value) || 0,
        start_date: document.getElementById("start_date").value || null,
        manual_payment: parseFloat(document.getElementById("manual_payment").value) || null,
        monthly_amortization: parseFloat(document.getElementById("monthly_display").textContent.replace(/[₱,]/g, '')) || null,
        total_interest: parseFloat(document.getElementById("s_interest").textContent.replace(/[₱,]/g, '')) || null,
        total_payment: parseFloat(document.getElementById("s_total").textContent.replace(/[₱,]/g, '')) || null,
        processing_fee: parseFloat(document.getElementById("c_processing").textContent.replace(/[₱,]/g, '')) || null,
        insurance: parseFloat(document.getElementById("c_insurance").textContent.replace(/[₱,]/g, '')) || null,
        notarial_fee: 400,
        net_proceeds: parseFloat(document.getElementById("c_net").textContent.replace(/[₱,]/g, '')) || null,
      };
      if (currentLoanId) loan.id = currentLoanId;

      // Collect schedule rows
      const schedule = [];
      document.querySelectorAll("#sched_body tr:not(.empty-row)").forEach(tr => {
        const sel = tr.querySelector(".status-select");
        const remarks = tr.querySelector(".remarks-input")?.value || null;
        schedule.push({
          period: parseInt(tr.querySelector("td:first-child").textContent) || 0,
          principal: parseFloat(tr.dataset.principal) || 0,
          interest: parseFloat(tr.dataset.interest) || 0,
          payment: parseFloat(tr.dataset.basepayment) || 0,
          due_date: tr.dataset.duedate ? tr.dataset.duedate.split("T")[0] : null,
          status: sel ? sel.value : "pending",
          rem_principal: parseFloat(tr.dataset.remPrincipal) || 0,
          rem_interest: parseFloat(tr.dataset.remInterest) || 0,
          rem_penalty: parseFloat(tr.dataset.remPenalty) || 0,
          remarks: remarks
        });
      });

      const result = await api('save_loan', {
        loan,
        schedule
      });
      if (result.error) {
        alert("Save failed: " + result.error);
        return;
      }

      currentLoanId = result.loan_id;

      // Save payment ledger entries that haven't been saved yet
      for (const entry of paymentLedger) {
        if (!entry.savedToDb) {
          await api('save_payment', {
            payment: {
              loan_id: currentLoanId,
              amount_paid: entry.amountPaid,
              penalty_applied: entry.penalty,
              interest_applied: entry.interest,
              principal_applied: entry.principal,
              excess: entry.excess,
              payment_type: entry.type,
              remarks: entry.remarks || null
            }
          });
          entry.savedToDb = true;
        }
      }

      showToast("Saved to database ✓");
    }

    function showToast(msg) {
      const t = document.getElementById("toast");
      t.textContent = msg;
      t.classList.add("show");
      setTimeout(() => t.classList.remove("show"), 2500);
    }

    // ── Ledger ────────────────────────────────────────────────────────────
    let paymentLedger = [];

    function recordPayment({
      periods,
      amountPaid,
      penalty,
      interest,
      principal,
      excess,
      type,
      remarks
    }) {
      paymentLedger.push({
        id: Date.now(),
        datetime: new Date().toISOString(),
        periods,
        amountPaid,
        penalty,
        interest,
        principal,
        excess,
        type,
        remarks: remarks || "",
        savedToDb: false
      });
      renderLedger();
    }

    function renderLedger() {
      const tbody = document.getElementById("ledger_body"),
        section = document.getElementById("ledger_section");
      document.getElementById("ledger_count").textContent = `${paymentLedger.length} record${paymentLedger.length!==1?"s":""}`;
      if (paymentLedger.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="10">No payments recorded yet</td></tr>`;
        section.style.display = "none";
        return;
      }
      section.style.display = "block";
      tbody.innerHTML = "";
      paymentLedger.forEach((e, idx) => {
        const tc = e.type === "Global" ? "var(--danger)" : "var(--status-hold)";
        const tr = document.createElement("tr");
        tr.innerHTML = `
        <td style="color:var(--t3);font-family:var(--font-mono)">${idx+1}</td>
        <td style="white-space:nowrap;font-size:11px">${fmtDateTime(new Date(e.datetime))}</td>
        <td style="font-family:var(--font-mono);font-weight:600">${peso(e.amountPaid)}</td>
        <td style="font-family:var(--font-mono);color:${e.penalty>0?"var(--danger)":"var(--t3)"}">${e.penalty>0?peso(e.penalty):"—"}</td>
        <td style="font-family:var(--font-mono);color:${e.interest>0?"var(--status-overdue)":"var(--t3)"}">${e.interest>0?peso(e.interest):"—"}</td>
        <td style="font-family:var(--font-mono)">${e.principal>0?peso(e.principal):"—"}</td>
        <td style="font-family:var(--font-mono);color:var(--t3)">${e.excess>0?peso(e.excess):"—"}</td>
        <td><span style="font-size:9px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:2px 8px;border-radius:20px;border:1px solid ${tc};color:${tc};background:${tc}18">${e.type}</span></td>
        <td><input type="text" value="${e.remarks}" placeholder="Add note…" onchange="updateLedgerRemark(${e.id},this.value)" style="width:100%;background:transparent;border:none;outline:none;color:var(--text);font-family:var(--font-main);font-size:11px;padding:2px 0"></td>
        <td style="text-align:center;white-space:nowrap">
          <button onclick="editLedgerEntry(${e.id})" title="Edit" style="background:none;border:none;cursor:pointer;padding:3px 6px;border-radius:4px;color:var(--t2)" onmouseover="this.style.background='var(--raised)'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button onclick="deleteLedgerEntry(${e.id})" title="Delete" style="background:none;border:none;cursor:pointer;padding:3px 6px;border-radius:4px;color:var(--danger)" onmouseover="this.style.background='rgba(217,61,61,0.08)'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
        </td>`;
        tbody.appendChild(tr);
      });
    }

    function updateLedgerRemark(id, value) {
      const e = paymentLedger.find(e => e.id === id);
      if (e) e.remarks = value;
    }

    function deleteLedgerEntry(id) {
      if (!confirm("Delete this payment record?")) return;
      paymentLedger = paymentLedger.filter(e => e.id !== id);
      renderLedger();
    }

    function editLedgerEntry(id) {
      const entry = paymentLedger.find(e => e.id === id);
      if (!entry) return;
      const newAmount = prompt("Edit amount paid:", entry.amountPaid);
      if (newAmount === null) return;
      const parsed = parseFloat(newAmount);
      if (isNaN(parsed) || parsed < 0) {
        alert("Invalid amount.");
        return;
      }
      entry.amountPaid = parsed;
      entry.savedToDb = false; // mark as needing re-save
      renderLedger();
    }

    // ── Payment Panel ────────────────────────────────────────────────────
    function updateOverduePanel() {
      const panel = document.getElementById("overdue_panel");
      const allRows = [...document.querySelectorAll("#sched_body tr:not(.empty-row)")];
      if (allRows.length === 0) {
        panel.style.display = "none";
        return;
      }
      panel.style.display = "block";
      let tP = 0,
        tI = 0,
        tPen = 0,
        oc = 0;
      allRows.forEach(tr => {
        const sel = tr.querySelector(".status-select");
        if (!sel || sel.value === "paid") return;
        tP += parseFloat(tr.dataset.remPrincipal) || 0;
        tI += parseFloat(tr.dataset.remInterest) || 0;
        tPen += parseFloat(tr.dataset.remPenalty) || 0;
        if (sel.value === "overdue") oc++;
      });
      document.getElementById("od_total_principal").textContent = peso(tP);
      document.getElementById("od_total_interest").textContent = peso(tI);
      document.getElementById("od_total_penalty").textContent = peso(tPen);
      document.getElementById("od_grand_total").textContent = peso(tP + tI + tPen);
      const badge = document.getElementById("od_overdue_badge");
      badge.textContent = oc > 0 ? `${oc} overdue` : "";
      badge.style.display = oc > 0 ? "inline" : "none";
      document.getElementById("od_penalty_wrap").style.display = tPen > 0 ? "block" : "none";
    }

    function applyGlobalPayment() {
      const amountPaid = parseFloat(document.getElementById("od_amount_paid").value) || 0;
      if (amountPaid <= 0) return;
      const unpaidRows = [...document.querySelectorAll("#sched_body tr:not(.empty-row)")].filter(tr => {
        const sel = tr.querySelector(".status-select");
        return sel && sel.value !== "paid";
      });
      if (unpaidRows.length === 0) return;
      const rowData = unpaidRows.map(tr => ({
        tr,
        pp: parseFloat(tr.dataset.remPrincipal) || 0,
        int: parseFloat(tr.dataset.remInterest) || 0,
        penalty: parseFloat(tr.dataset.remPenalty) || 0
      }));
      const totPen = rowData.reduce((s, r) => s + r.penalty, 0),
        totInt = rowData.reduce((s, r) => s + r.int, 0),
        totPrin = rowData.reduce((s, r) => s + r.pp, 0);
      let rem = amountPaid,
        aPen = 0,
        aInt = 0,
        aPrin = 0;
      for (const rd of rowData) {
        if (rem <= 0) break;
        const t = Math.min(rem, rd.penalty);
        rd.appliedPenalty = t;
        aPen += t;
        rem -= t;
      }
      for (const rd of rowData) {
        if (rem <= 0) break;
        const t = Math.min(rem, rd.int);
        rd.appliedInterest = t;
        aInt += t;
        rem -= t;
      }
      for (const rd of rowData) {
        if (rem <= 0) break;
        const t = Math.min(rem, rd.pp);
        rd.appliedPrincipal = t;
        aPrin += t;
        rem -= t;
      }
      const excess = rem;
      for (const rd of rowData) {
        const pP = rd.appliedPenalty || 0,
          pI = rd.appliedInterest || 0,
          pPr = rd.appliedPrincipal || 0;
        const full = (pP + pI + pPr) >= (rd.penalty + rd.int + rd.pp);
        rd.tr.dataset.remPenalty = Math.max(0, rd.penalty - pP);
        rd.tr.dataset.remInterest = Math.max(0, rd.int - pI);
        rd.tr.dataset.remPrincipal = Math.max(0, rd.pp - pPr);
        const sel = rd.tr.querySelector(".status-select");
        if (sel) {
          const remPen = Math.max(0, rd.penalty - pP);
          const ns = full ? "paid" : (remPen > 0 ? "overdue" : "pending");
          sel.value = ns;
          sel.className = `status-select s-${ns}`;
          rowStatuses[sel.dataset.idx] = ns;
          rd.tr.className = ns === "paid" ? "row--paid" : ns === "overdue" ? "row--overdue" : "";
          const dueTd = rd.tr.querySelector(".due-date");
          if (dueTd) {
            dueTd.className = "due-date";
            if (ns === "overdue") dueTd.classList.add("due-over");
          }
          const payTd = rd.tr.querySelector(".payment-cell");
          if (payTd) {
            const base = parseFloat(rd.tr.dataset.basepayment) || (rd.pp + rd.int);
            payTd.textContent = peso(full ? base : base + remPen);
          }
        }
        const penTd = rd.tr.querySelector(".penalty-cell");
        if (penTd) {
          const origPen = parseFloat(rd.tr.dataset.origPenalty) || 0;
          const remPen = Math.max(0, rd.penalty - pP);
          if (origPen === 0) {
            penTd.textContent = "No penalty";
            penTd.style.cssText = "text-align:right;color:var(--t3);font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em";
          } else if (remPen <= 0.01) {
            penTd.textContent = "Paid";
            penTd.style.cssText = "text-align:right;color:var(--ok);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em";
          } else {
            penTd.textContent = peso(remPen);
            penTd.style.cssText = "text-align:right;color:var(--danger);font-weight:600";
          }
        }
      }
      const fP = aPen >= totPen,
        fI = aInt >= totInt,
        fPr = aPrin >= totPrin;
      let html = `<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">`;
      html += chip(`Penalty: ${peso(aPen)} / ${peso(totPen)}`, fP ? "var(--ok)" : "var(--danger)", fP);
      html += chip(`Interest: ${peso(aInt)} / ${peso(totInt)}`, fI ? "var(--ok)" : "var(--status-overdue)", fI);
      html += chip(`Principal: ${peso(aPrin)} / ${peso(totPrin)}`, fPr ? "var(--ok)" : "var(--t3)", fPr);
      if (excess > 0) html += chip(`Excess: ${peso(excess)}`, "var(--status-hold)", true);
      html += `</div>`;
      document.getElementById("od_breakdown").innerHTML = html;
      recordPayment({
        periods: "—",
        amountPaid,
        penalty: aPen,
        interest: aInt,
        principal: aPrin,
        excess,
        type: "Global",
        remarks: ""
      });
      document.getElementById("od_amount_paid").value = "";
      updateOverduePanel();
    }

    // ── Core ──────────────────────────────────────────────────────────────
    let rowStatuses = {};
    const THEAD_NORMAL = `<th>Period</th><th style="text-align:right">Principal</th><th style="text-align:right">Interest</th><th style="text-align:right">Payment</th><th>Due Date</th><th>Status</th><th style="text-align:right;color:var(--danger)">Penalty</th><th>Remarks</th>`;
    const THEAD_MF = `<th>Period</th><th style="text-align:right">Opening Balance</th><th style="text-align:right">Amortization</th><th style="text-align:right">Interest</th><th style="text-align:right">Payment</th><th>Due Date</th><th>Status</th><th style="text-align:right;color:var(--danger)">Penalty</th><th>Remarks</th>`;

    function buildStatusSelect(idx, status) {
      const opts = ["pending", "paid", "overdue"],
        labels = {
          pending: "Pending",
          paid: "Paid",
          overdue: "Overdue"
        };
      let h = `<select class="status-select s-${status}" data-idx="${idx}" onchange="onStatusChange(this)">`;
      opts.forEach(o => {
        h += `<option value="${o}"${o===status?" selected":""}>${labels[o]}</option>`;
      });
      return h + `</select>`;
    }

    function onStatusChange(sel) {
      const idx = sel.dataset.idx,
        status = sel.value;
      rowStatuses[idx] = status;
      sel.className = `status-select s-${status}`;
      const tr = sel.closest("tr");
      tr.className = status === "overdue" ? "row--overdue" : status === "paid" ? "row--paid" : tr.dataset.dueness === "near-due" ? "row--near-due" : "";
      const dueTd = tr.querySelector(".due-date");
      if (dueTd) {
        dueTd.className = "due-date";
        if (status !== "paid") {
          if (tr.dataset.dueness === "overdue" || status === "overdue") dueTd.classList.add("due-over");
          else if (tr.dataset.dueness === "near-due") dueTd.classList.add("due-near");
        }
      }
      const payTd = tr.querySelector(".payment-cell");
      if (payTd) {
        const pp = parseFloat(tr.dataset.principal) || 0,
          int = parseFloat(tr.dataset.interest) || 0,
          due = tr.dataset.duedate ? new Date(tr.dataset.duedate) : null,
          base = parseFloat(tr.dataset.basepayment) || (pp + int);
        payTd.textContent = peso(base + (status === "overdue" ? calcPenalty(pp, int, due) : 0));
      }
      const penTd = tr.querySelector(".penalty-cell");
      if (penTd) {
        const origPen = parseFloat(tr.dataset.origPenalty) || 0;
        const pp = parseFloat(tr.dataset.principal) || 0,
          int = parseFloat(tr.dataset.interest) || 0,
          due = tr.dataset.duedate ? new Date(tr.dataset.duedate) : null,
          pen = calcPenalty(pp, int, due);
        if (origPen === 0) {
          penTd.textContent = "No penalty";
          penTd.style.cssText = "text-align:right;color:var(--t3);font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em";
        } else if (status === "paid" || parseFloat(tr.dataset.remPenalty) <= 0.01) {
          penTd.textContent = "Paid";
          penTd.style.cssText = "text-align:right;color:var(--ok);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em";
        } else {
          penTd.textContent = peso(pen);
          penTd.style.cssText = "text-align:right;color:var(--danger);font-weight:600";
        }
      }
      updateOverduePanel();
    }

    function applyRowState(tr, idx, dueDate, statusOverride) {
      const d = dueness(dueDate);
      const st = statusOverride || rowStatuses[idx] || (d === "overdue" ? "overdue" : "pending");
      rowStatuses[idx] = st;
      tr.dataset.dueness = d;
      tr.className = st === "overdue" ? "row--overdue" : st === "paid" ? "row--paid" : d === "near-due" ? "row--near-due" : "";
      return st;
    }

    function calculate() {
      const loanType = document.getElementById("loan_type").value,
        isMF = loanType === "Micro-Finance Loan";
      const intEl = document.getElementById("interest");
      if (loanType && !intEl.dataset.userEdited) intEl.value = isMF ? 5 : 2;
      const p = parseFloat(document.getElementById("principal").value),
        r = parseFloat(intEl.value) / 100,
        m = parseInt(document.getElementById("months").value);
      const t = document.getElementById("amort_type").value,
        man = parseFloat(document.getElementById("manual_payment").value) || 0;
      const freq = document.getElementById("mf_freq").value,
        startRaw = document.getElementById("start_date").value,
        hasDate = !!startRaw,
        startDate = hasDate ? new Date(startRaw) : null;
      document.getElementById("amort_type").closest(".card").style.display = isMF ? "none" : "";
      document.getElementById("mf_freq_card").style.display = isMF ? "block" : "none";
      document.getElementById("manual_card").style.display = (!isMF && t === "manual") ? "block" : "none";
      const dlr = document.getElementById("loan_details_row");
      if (p && m) {
        const pf = p * 0.02,
          ins = (p / 1000) * 1.2 * m,
          not = 400;
        document.getElementById("c_processing").textContent = peso(pf);
        document.getElementById("c_insurance").textContent = peso(ins);
        document.getElementById("c_notarial").textContent = peso(not);
        document.getElementById("c_net").textContent = peso(p - pf - ins - not);
        dlr.style.display = "block";
      } else dlr.style.display = "none";
      document.getElementById("table_head_row").innerHTML = isMF ? THEAD_MF : THEAD_NORMAL;
      const tbody = document.getElementById("sched_body"),
        resultEl = document.getElementById("monthly_display"),
        resultSub = document.getElementById("result_sub"),
        colSpan = isMF ? 9 : 8;
      const invalid = !p || !m || (isMF && !r) || (!isMF && t !== "manual" && !r) || (!isMF && t === "manual" && !man);
      if (invalid) {
        resultEl.textContent = "₱ —";
        resultSub.textContent = "Updates automatically";
        tbody.innerHTML = `<tr class="empty-row"><td colspan="${colSpan}">Enter loan details to generate the schedule</td></tr>`;
        document.getElementById("row_count").textContent = "No data";
        document.getElementById("summary_strip").classList.add("hidden");
        rowStatuses = {};
        updateOverduePanel();
        return;
      }
      const prev = {
        ...rowStatuses
      };
      rowStatuses = {};
      tbody.innerHTML = "";

      function dateCells(dueDate, idx, st) {
        if (!hasDate) return `<td class="due-date">—</td><td>${buildStatusSelect(idx,st)}</td>`;
        const d = dueness(dueDate);
        const dc = st !== "paid" ? (d === "overdue" || st === "overdue" ? "due-date due-over" : d === "near-due" ? "due-date due-near" : "due-date") : "due-date";
        return `<td class="${dc}">${fmtDate(dueDate)}</td><td>${buildStatusSelect(idx,st)}</td>`;
      }

      function penaltyCell(pp, int, dueDate, st) {
        const pen = calcPenalty(pp, int, dueDate);
        if (pen === 0) return `<td class="penalty-cell" style="text-align:right;color:var(--t3);font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em">No penalty</td>`;
        if (st !== "overdue") return `<td class="penalty-cell" style="text-align:right;color:var(--t3)">—</td>`;
        return `<td class="penalty-cell" style="text-align:right;color:var(--danger);font-weight:600">${peso(pen)}</td>`;
      }

      function remarksCell(idx) {
        return `<td style="min-width:160px"><input type="text" class="remarks-input" data-idx="${idx}" placeholder="Add note…" style="width:100%;background:var(--raised);border:1px solid var(--border);border-radius:6px;padding:4px 8px;color:var(--text);font-family:var(--font-main);font-size:11px;outline:none"></td>`;
      }

      function buildRow(i, cells, pp, int, payment, dueDate, st) {
        const tr = document.createElement("tr");
        const origPen = calcPenalty(pp, int, dueDate);
        tr.dataset.principal = pp;
        tr.dataset.interest = int;
        tr.dataset.basepayment = payment;
        tr.dataset.duedate = dueDate ? dueDate.toISOString() : "";
        tr.dataset.remPrincipal = pp;
        tr.dataset.remInterest = int;
        tr.dataset.remPenalty = origPen;
        tr.dataset.origPenalty = origPen;
        tr.innerHTML = cells + dateCells(dueDate, i, st) + penaltyCell(pp, int, dueDate, st) + remarksCell(i);
        tbody.appendChild(tr);
        applyRowState(tr, i, dueDate || new Date(0), st);
      }
      if (isMF) {
        const fm = freq === "monthly" ? 1 : freq === "bi-monthly" ? 2 : 4,
          rp = r / fm,
          periods = m * fm,
          amort = p / periods,
          intPer = p * rp,
          payment = amort + intPer;
        resultEl.textContent = peso(payment);
        resultSub.textContent = `Fixed per ${freq==="monthly"?"month":freq==="bi-monthly"?"bi-month":"week"} · ${periods} periods`;
        let bal = p,
          tI = 0,
          tP = 0,
          dd = startDate ? new Date(startDate) : null;
        for (let i = 1; i <= periods; i++) {
          if (dd) dd = nextDate(dd, freq);
          const ob = bal;
          bal -= amort;
          if (bal < 0) bal = 0;
          tI += intPer;
          tP += payment;
          const autoDue = dd ? (dueness(dd) === "overdue" ? "overdue" : "pending") : "pending";
          const st = prev[i] === "paid" ? "paid" : autoDue;
          const pen = st === "overdue" ? calcPenalty(amort, intPer, dd) : 0;
          buildRow(i, `<td>${i}</td><td>${peso(ob)}</td><td>${peso(amort)}</td><td>${peso(intPer)}</td><td class="payment-cell">${peso(payment+pen)}</td>`, amort, intPer, payment, dd, st);
        }
        document.getElementById("row_count").textContent = `${periods} periods`;
        document.getElementById("s_interest").textContent = peso(tI);
        document.getElementById("s_total").textContent = peso(tP);
        document.getElementById("s_rate").textContent = peso(amort);
        document.getElementById("s_months").textContent = `${m} months`;
        document.getElementById("s_rate_label").textContent = "Amortization / period";
        document.getElementById("s_months_label").textContent = "Term";
      } else {
        let bal = p,
          tI = 0,
          tP = 0,
          dd = startDate ? new Date(startDate) : null;
        for (let i = 1; i <= m; i++) {
          if (dd) dd = nextDate(dd, "monthly");
          let pp, int, payment;
          if (t === "straight") {
            pp = p / m;
            int = p * r;
            payment = pp + int;
          } else if (t === "diminishing") {
            pp = p / m;
            int = bal * r;
            payment = pp + int;
          } else {
            payment = man;
            int = bal * r;
            pp = Math.max(0, payment - int);
          }
          bal -= pp;
          if (bal < 0) bal = 0;
          tI += int;
          tP += payment;
          if (i === 1) {
            resultEl.textContent = peso(payment);
            resultSub.textContent = t === "diminishing" ? "First month (decreases each period)" : t === "straight" ? "Fixed — same every month" : "Manual payment";
          }
          const autoDue = dd ? (dueness(dd) === "overdue" ? "overdue" : "pending") : "pending";
          const st = prev[i] === "paid" ? "paid" : autoDue;
          const pen = st === "overdue" ? calcPenalty(pp, int, dd) : 0;
          buildRow(i, `<td>${i}</td><td>${peso(pp)}</td><td>${peso(int)}</td><td class="payment-cell">${peso(payment+pen)}</td>`, pp, int, payment, dd, st);
        }
        document.getElementById("row_count").textContent = `${m} periods`;
        document.getElementById("s_interest").textContent = peso(tI);
        document.getElementById("s_total").textContent = peso(tP);
        document.getElementById("s_rate").textContent = peso(p / m);
        document.getElementById("s_months").textContent = m + " months";
        document.getElementById("s_rate_label").textContent = "Fixed Principal";
        document.getElementById("s_months_label").textContent = "Term length";
      }
      document.getElementById("summary_strip").classList.remove("hidden");
      updateOverduePanel();
    }

    function toggleTheme() {
      const d = document.documentElement.dataset.theme === "dark";
      document.documentElement.dataset.theme = d ? "" : "dark";
      document.querySelector(".theme-toggle").textContent = d ? "🌙 Dark" : "☀️ Light";
    }

    document.querySelectorAll("input, select").forEach(el => {
      if (el.id === "od_amount_paid") return;
      el.addEventListener("input", calculate);
      el.addEventListener("change", calculate);
    });
    document.getElementById("interest").addEventListener("input", function() {
      this.dataset.userEdited = "true";
    });
    document.getElementById("loan_type").addEventListener("change", function() {
      document.getElementById("interest").dataset.userEdited = "";
    });

    // ── Auto-load from server if member_id was passed ─────────────────
    const SERVER_DATA = <?= json_encode($existingLoan) ?>;
    const MEMBER_ID = <?= $memberId ?>;

    function loadFromServer() {
      if (!SERVER_DATA || !SERVER_DATA.loan) return;

      const loan = SERVER_DATA.loan;
      currentLoanId = loan.id;

      // Populate form fields
      const set = (id, val) => {
        const el = document.getElementById(id);
        if (el && val != null) el.value = val;
      };
      set('loan_type', loan.loan_type);
      set('collateral', loan.collateral);
      set('soa', loan.soa_status);
      set('amort_type', loan.amort_type);
      set('mf_freq', loan.mf_freq);
      set('principal', loan.principal_amount);
      set('interest', loan.interest_rate);
      set('months', loan.terms_months);
      set('start_date', loan.start_date);
      set('manual_payment', loan.manual_payment);

      // Mark interest as user-set so auto-rate doesn't override
      document.getElementById("interest").dataset.userEdited = "true";

      // Build rowStatuses from saved schedule
      if (SERVER_DATA.schedule) {
        SERVER_DATA.schedule.forEach(row => {
          rowStatuses[row.period] = row.status;
        });
      }

      // Trigger calculate to build the table
      calculate();

      // After table is built, restore rem* values and remarks from saved schedule
      if (SERVER_DATA.schedule) {
        SERVER_DATA.schedule.forEach(row => {
          const tr = document.querySelector(`#sched_body tr:not(.empty-row):nth-child(${row.period})`);
          if (!tr) return;
          tr.dataset.remPrincipal = row.rem_principal;
          tr.dataset.remInterest = row.rem_interest;
          tr.dataset.remPenalty = row.rem_penalty;
          const remarksInput = tr.querySelector(".remarks-input");
          if (remarksInput && row.remarks) remarksInput.value = row.remarks;
        });
        updateOverduePanel();
      }

      // Restore payment ledger
      if (SERVER_DATA.payments && SERVER_DATA.payments.length > 0) {
        SERVER_DATA.payments.forEach(p => {
          paymentLedger.push({
            id: p.id,
            datetime: p.paid_at,
            periods: "—",
            amountPaid: parseFloat(p.amount_paid),
            penalty: parseFloat(p.penalty_applied),
            interest: parseFloat(p.interest_applied),
            principal: parseFloat(p.principal_applied),
            excess: parseFloat(p.excess),
            type: p.payment_type,
            remarks: p.remarks || "",
            savedToDb: true
          });
        });
        renderLedger();
      }
    }



    loadFromServer();

    // Load existing loan data if member was passed from dashboard
    loadFromDb();

    document.addEventListener('DOMContentLoaded', function() {
      // 1. THEME TOGGLE (Dark Mode)
      const themeToggle = document.getElementById('theme-toggle');
      const htmlEl = document.documentElement;

      // Check for saved user preference
      const savedTheme = localStorage.getItem('theme') || 'light';
      htmlEl.setAttribute('data-theme', savedTheme);

      if (themeToggle) {
        themeToggle.addEventListener('click', () => {
          const currentTheme = htmlEl.getAttribute('data-theme');
          const newTheme = currentTheme === 'light' ? 'dark' : 'light';

          htmlEl.setAttribute('data-theme', newTheme);
          localStorage.setItem('theme', newTheme);
        });
      }

      // 2. PROFILE DROPDOWN
      const profilePill = document.querySelector('.c-navbar__profile');
      const dropdown = document.querySelector('.c-navbar__dropdown');

      if (profilePill && dropdown) {
        profilePill.addEventListener('click', function(e) {
          e.stopPropagation();
          dropdown.classList.toggle('is-active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
          dropdown.classList.remove('is-active');
        });
      }
    });

    function exportSOAExcel() {
      if (!PHP_LOAN_DATA || !PHP_LOAN_DATA.loan) {
        alert("No loan record found. Please save the loan to the database first.");
        return;
      }

      const loan = PHP_LOAN_DATA.loan;
      const sched = PHP_LOAN_DATA.schedule || [];

      // ── Helpers ──────────────────────────────────────────────────
      function fmt(n) {
        const v = parseFloat(n) || 0;
        return v === 0 ? '-' : v.toLocaleString('en-PH', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      }

      function fmtDate(d) {
        if (!d) return '-';
        const dt = new Date(d);
        return dt.toLocaleDateString('en-PH', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        }).replace(/\//g, '/');
      }

      function addMonths(dateStr, n) {
        if (!dateStr) return null;
        const d = new Date(dateStr);
        d.setMonth(d.getMonth() + n);
        return d;
      }

      const today = new Date();
      const asOfLabel = today.toLocaleDateString('en-PH', {
        day: '2-digit',
        month: 'short',
        year: '2-digit'
      }).replace(/ /g, '-');
      const dateReleased = loan.start_date ? fmtDate(loan.start_date) : '-';
      const maturityDate = loan.start_date ? fmtDate(addMonths(loan.start_date, parseInt(loan.terms_months) || 0)) : '-';

      const principal = parseFloat(loan.principal_amount) || 0;
      const totalInterest = parseFloat(loan.total_interest) || 0;
      const loanNotes = principal + totalInterest;
      const periodicAmort = parseFloat(loan.monthly_amortization) || 0;

      // Compute months-past-due per row from saved rem data
      function monthsPastDue(dueDateStr) {
        if (!dueDateStr) return 0;
        const due = new Date(dueDateStr);
        due.setHours(0, 0, 0, 0);
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        if (due >= now) return 0;
        const mo = (now.getFullYear() - due.getFullYear()) * 12 + (now.getMonth() - due.getMonth());
        return Math.max(1, mo);
      }

      // Build worksheet data as array of arrays
      const WS = [];

      // ── Row 1: Title + as-of ──────────────────────────────────────
      WS.push(['STATEMENT OF ACCOUNT', '', '', '', '', '', 'as of', asOfLabel, '']);

      // ── Row 2: blank ─────────────────────────────────────────────
      WS.push([]);

      // ── Rows 3–10: info block (left) + summary (right) ───────────
      const infoLeft = [
        ['Name:', PHP_MEMBER_NAME || '-'],
        ['Member ID', String(loan.member_id || '').padStart(4, '0')],
        ['Term', loan.terms_months || '-'],
        ['Payment Frequency', loan.mf_freq ? (loan.mf_freq === 'bi-monthly' ? '2' : loan.mf_freq === 'weekly' ? '4' : '1') : '1'],
        ['Interest Rate/annum', (parseFloat(loan.interest_rate) || 0).toFixed(2) + '%'],
        ['Date Released', dateReleased],
        ['Maturity Date', maturityDate],
        ['Loan ID', loan.id || '-'],
      ];
      const infoRight = [
        ['Principal', fmt(principal)],
        ['Interest', fmt(totalInterest)],
        ['Service Charges', '-'],
        ['Non-finance charges', '-'],
        ['Loan Notes Receivable', fmt(loanNotes)],
        ['Periodic Amortization', fmt(periodicAmort)],
      ];

      for (let i = 0; i < 8; i++) {
        const left = infoLeft[i] || ['', ''];
        const right = infoRight[i] || ['', ''];
        // col A=label, B=value, C=blank, D=blank, E=blank, F=right-label, G=right-value
        WS.push([left[0], left[1], '', '', '', right[0], right[1]]);
      }

      // ── Blank row ────────────────────────────────────────────────
      WS.push([]);

      // ── Column headers ────────────────────────────────────────────
      WS.push([
        'Due Date', 'Principal', 'Interest', 'Total Amount Due',
        'Payments', 'Months past due', 'Principal', 'Interest', 'Penalty (3%)'
      ]);

      // ── Data rows ─────────────────────────────────────────────────
      let totPrincipal = 0,
        totInterest = 0,
        totAmountDue = 0,
        totPayments = 0;
      let totOverduePrin = 0,
        totOverdueInt = 0,
        totPenalty = 0;

      const DATA_START = WS.length + 1; // 1-indexed for Excel

      sched.forEach(row => {
        const pp = parseFloat(row.principal) || 0;
        const int = parseFloat(row.interest) || 0;
        const amtDue = pp + int;
        const isPaid = row.status === 'paid';
        const payment = isPaid ? amtDue : 0;
        const mo = (row.status === 'overdue') ? monthsPastDue(row.due_date) : 0;
        const remPen = parseFloat(row.rem_penalty) || 0;
        const overduePrin = (row.status === 'overdue') ? (parseFloat(row.rem_principal) || 0) : 0;
        const overdueInt = (row.status === 'overdue') ? (parseFloat(row.rem_interest) || 0) : 0;

        totPrincipal += pp;
        totInterest += int;
        totAmountDue += amtDue;
        totPayments += payment;
        totOverduePrin += overduePrin;
        totOverdueInt += overdueInt;
        totPenalty += remPen;

        WS.push([
          row.due_date || '-',
          fmt(pp),
          fmt(int),
          fmt(amtDue),
          isPaid ? fmt(payment) : '-',
          mo > 0 ? mo : '-',
          overduePrin > 0 ? fmt(overduePrin) : '-',
          overdueInt > 0 ? fmt(overdueInt) : '-',
          remPen > 0 ? fmt(remPen) : '-',
        ]);
      });

      // ── Totals row ────────────────────────────────────────────────
      WS.push([
        'Total',
        fmt(totPrincipal),
        fmt(totInterest),
        fmt(totAmountDue),
        fmt(totPayments),
        '',
        fmt(totOverduePrin),
        fmt(totOverdueInt),
        fmt(totPenalty),
      ]);

      // ── Grand total row ───────────────────────────────────────────
      const grandTotal = totOverduePrin + totOverdueInt + totPenalty;
      WS.push([
        'Grand Total (Principal + Interest + Penalty)',
        '', '', '', '', '',
        fmt(totOverduePrin + totOverdueInt),
        '',
        fmt(grandTotal),
      ]);

      // ── Notes / footer ────────────────────────────────────────────
      WS.push([]);
      WS.push(['Notes:', '', '', '', '', '', '', '', '']);
      WS.push(['*', '', '', '', '', '', '', '', '']);
      WS.push([]);
      WS.push(['Total Receivables', '', '', '', 'Total Outstanding', '', '', '', '']);

      // ── Build workbook ────────────────────────────────────────────
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet(WS);

      // ── Column widths ─────────────────────────────────────────────
      ws['!cols'] = [{
          wch: 16
        }, // Due Date
        {
          wch: 14
        }, // Principal
        {
          wch: 14
        }, // Interest
        {
          wch: 18
        }, // Total Amount Due
        {
          wch: 14
        }, // Payments
        {
          wch: 16
        }, // Months past due
        {
          wch: 14
        }, // Principal (overdue)
        {
          wch: 14
        }, // Interest (overdue)
        {
          wch: 14
        }, // Penalty
      ];

      // ── Merges ────────────────────────────────────────────────────
      ws['!merges'] = [
        // Title: A1:E1
        {
          s: {
            r: 0,
            c: 0
          },
          e: {
            r: 0,
            c: 4
          }
        },
        // Grand total label: A last-total-row spanning cols 0-5
        {
          s: {
            r: WS.length - 6,
            c: 0
          },
          e: {
            r: WS.length - 6,
            c: 5
          }
        },
      ];

      XLSX.utils.book_append_sheet(wb, ws, 'Statement of Account');

      const memberSlug = (PHP_MEMBER_NAME || 'member').replace(/\s+/g, '-').toLowerCase();
      XLSX.writeFile(wb, `SOA_${memberSlug}_${asOfLabel}.xlsx`);
    }
  </script>

</body>

</html>