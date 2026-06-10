<?php

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
  $action = $_GET['action'];

  // ── FILE UPLOAD ACTION (multipart — must be handled before json_decode) ──
  if ($action === 'upload_document') {
    $loanId  = intval($_POST['loan_id']  ?? 0);
    $docType = $_POST['doc_type'] ?? '';

    if (!$loanId || !in_array($docType, ['undertaking', 'deed_assignment'])) {
      echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
      exit;
    }

    $file = $_FILES['document'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
      echo json_encode(['success' => false, 'error' => 'Upload failed or no file received.']);
      exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
      echo json_encode(['success' => false, 'error' => 'Only PDF files are allowed.']);
      exit;
    }

    $uploadDir = __DIR__ . '/uploads/loan_documents/' . $loanId . '/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }

    $fileName = $docType . '_' . time() . '.pdf';
    $filePath = $uploadDir . $fileName;
    $storedPath = 'uploads/loan_documents/' . $loanId . '/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
      // Upsert: one record per loan_id + doc_type combination
      $stmt = $pdo->prepare("
        INSERT INTO loan_documents (loan_id, doc_type, file_name, file_path)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          file_name = VALUES(file_name),
          file_path = VALUES(file_path),
          uploaded_at = CURRENT_TIMESTAMP
      ");
      $stmt->execute([$loanId, $docType, $fileName, $storedPath]);
      echo json_encode(['success' => true, 'path' => $storedPath]);
    } else {
      echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
    }
    exit;
  }

  // ── ALL OTHER ACTIONS (JSON body) ────────────────────────────────────
  $body = json_decode(file_get_contents('php://input'), true);

  // ── DELETE / DETACH DOCUMENT ACTION ──────────────────────────────────
  // Sets the row's file_name + file_path to NULL (soft detach) rather than
  // deleting the physical file, preserving it as a recoverable artefact.
  // The UNIQUE KEY (loan_id, doc_type) means one row per slot — so we
  // simply null-out both path columns on that specific row.
  if ($action === 'delete_document') {
    $loanId  = intval($body['loan_id']  ?? 0);
    $docType = $body['doc_type'] ?? '';

    if (!$loanId || !in_array($docType, ['undertaking', 'deed_assignment'])) {
      echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
      exit;
    }

    try {
      // Null-out file columns — keeps the row alive for audit history
      $stmt = $pdo->prepare("
        UPDATE loan_documents
        SET file_name = NULL, file_path = NULL, uploaded_at = CURRENT_TIMESTAMP
        WHERE loan_id = ? AND doc_type = ?
      ");
      $stmt->execute([$loanId, $docType]);

      echo json_encode(['success' => true, 'message' => 'Document detached successfully.']);
    } catch (PDOException $e) {
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
  }

  if ($_GET['action'] === 'save_loan') {
    try {
      $pdo->beginTransaction();
      $loan     = $body['loan'];
      $schedule = $body['schedule'];

      // ── Intercept real property child-table payload node ─────────────
      // The JS sends real_property as a separate object node on the payload.
      // We also accept individual keys on $loan for backwards compatibility.
      $rpData = $body['real_property'] ?? null;
      $isRealProperty = ($loan['collateral'] ?? '') === 'Real Property';

      // Check if this member already has a loan record to update or insert
      $checkStmt = $pdo->prepare("SELECT id FROM loans WHERE member_id = ? LIMIT 1");
      $checkStmt->execute([$loan['member_id']]);
      $existingLoanId = $checkStmt->fetchColumn();

      if ($existingLoanId) {
        // Update main loan record (no RP columns here — they live in the child table)
        $stmt = $pdo->prepare("UPDATE loans SET
          loan_type = :loan_type, collateral = :collateral, soa_status = :soa_status,
          amort_type = :amort_type, principal_amount = :principal, interest_rate = :interest,
          terms = :terms, start_date = :start_date
          WHERE id = :id");

        $stmt->execute([
          ':loan_type'  => $loan['loan_type'],
          ':collateral' => $loan['collateral'],
          ':soa_status' => $loan['soa_status'],
          ':amort_type' => $loan['amort_type'],
          ':principal'  => $loan['principal'],
          ':interest'   => $loan['interest_rate'],
          ':terms'      => $loan['terms'],
          ':start_date' => $loan['start_date'],
          ':id'         => $existingLoanId
        ]);
        $loanId = $existingLoanId;

        // Clear out old schedule entries to overwrite cleanly
        $delStmt = $pdo->prepare("DELETE FROM loan_schedules WHERE loan_id = ?");
        $delStmt->execute([$loanId]);
      } else {
        // Insert a brand new loan record
        $stmt = $pdo->prepare("INSERT INTO loans
          (member_id, loan_type, collateral, soa_status, amort_type, principal_amount, interest_rate, terms, start_date)
          VALUES
          (:member_id, :loan_type, :collateral, :soa_status, :amort_type, :principal, :interest, :terms, :start_date)");

        $stmt->execute([
          ':member_id'  => $loan['member_id'],
          ':loan_type'  => $loan['loan_type'],
          ':collateral' => $loan['collateral'],
          ':soa_status' => $loan['soa_status'],
          ':amort_type' => $loan['amort_type'],
          ':principal'  => $loan['principal'],
          ':interest'   => $loan['interest_rate'],
          ':terms'      => $loan['terms'],
          ':start_date' => $loan['start_date'],
        ]);
        $loanId = $pdo->lastInsertId();
      }

      // ── Real Property Child-Table UPSERT ─────────────────────────────
      // Only execute when the selected collateral is "Real Property".
      // Performs a clean UPSERT: INSERT on first save, UPDATE on subsequent saves.
      if ($isRealProperty) {
        $tctNo       = $rpData['tct_no']            ?? $loan['tct_no']     ?? null;
        $taxDecNo    = $rpData['tax_dec_no']         ?? $loan['tax_dec_no'] ?? null;
        $propPayment = $rpData['property_payments']  ?? $loan['rp_status']  ?? null;

        // Check whether a child-table row already exists for this loan
        $rpCheckStmt = $pdo->prepare(
          "SELECT id FROM loan_real_property_details WHERE loan_id = ? LIMIT 1"
        );
        $rpCheckStmt->execute([$loanId]);
        $rpExistingId = $rpCheckStmt->fetchColumn();

        if ($rpExistingId) {
          // UPDATE existing row
          $rpStmt = $pdo->prepare(
            "UPDATE loan_real_property_details
             SET tct_no = :tct_no, tax_dec_no = :tax_dec_no, property_payments = :property_payments
             WHERE loan_id = :loan_id"
          );
        } else {
          // INSERT new row
          $rpStmt = $pdo->prepare(
            "INSERT INTO loan_real_property_details (loan_id, tct_no, tax_dec_no, property_payments)
             VALUES (:loan_id, :tct_no, :tax_dec_no, :property_payments)"
          );
        }

        $rpStmt->execute([
          ':loan_id'           => $loanId,
          ':tct_no'            => $tctNo,
          ':tax_dec_no'        => $taxDecNo,
          ':property_payments' => $propPayment,
        ]);
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
      echo json_encode(['success' => true, 'loan_id' => (int)$loanId, 'message' => 'Amortization schedule saved successfully!']);
    } catch (Exception $e) {
      $pdo->rollBack();
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
  }

  // ── BAGONG DAGDAG: API ACTION HOOK PARA SA LEDGER PAYMENTS ──────────
  if ($_GET['action'] === 'save_payment') {
    try {
      $pdo->beginTransaction();
      $payment = $body['payment'];

      // Tukuyin muna ang active loan_id ng pinatutungkulang miyembro
      $loanStmt = $pdo->prepare("SELECT id FROM loans WHERE member_id = ? LIMIT 1");
      $loanStmt->execute([$payment['member_id']]);
      $loanId = $loanStmt->fetchColumn();

      if (!$loanId) {
        throw new Exception("Hindi mahanap ang aktibong loan record ng miyembrong ito upang lapatan ng bayad.");
      }

      // Isulat ang permanenteng breakdown record sa ledger entry tracking queue table natin
      $stmt = $pdo->prepare("INSERT INTO loan_payments 
        (loan_id, member_id, amount_paid, penalty_applied, interest_applied, principal_applied, excess_cash, remarks) 
        VALUES (:loan_id, :member_id, :amount_paid, :penalty_applied, :interest_applied, :principal_applied, :excess_cash, :remarks)");

      $stmt->execute([
        ':loan_id'          => $loanId,
        ':member_id'        => $payment['member_id'],
        ':amount_paid'      => $payment['amount_paid'],
        ':penalty_applied'   => $payment['penalty_applied'],
        ':interest_applied'  => $payment['interest_applied'],
        ':principal_applied' => $payment['principal_applied'],
        ':excess_cash'       => $payment['excess_cash'],
        ':remarks'          => $payment['remarks'] ?? null
      ]);

      $pdo->commit();
      echo json_encode(['success' => true, 'message' => 'Ang bayad ay matagumpay na nailapat at naisulat sa database!']);
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
$existingPayments = []; // Tagasalo ng records ng payments mula sa db lifecycle
$existingDocs     = []; // Keyed by doc_type; holds file_name + file_path for State-B hydration

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
      // 2a. If collateral is Real Property, fetch child-table fields and
      //     append them directly to the $existingLoan data array so the
      //     JS SAVED_LOAN_DATA object receives them transparently.
      if (($existingLoan['collateral'] ?? '') === 'Real Property') {
        $rpFetchStmt = $pdo->prepare(
          "SELECT tct_no, tax_dec_no, property_payments
           FROM loan_real_property_details
           WHERE loan_id = ? LIMIT 1"
        );
        $rpFetchStmt->execute([$existingLoan['id']]);
        $rpRow = $rpFetchStmt->fetch(PDO::FETCH_ASSOC);

        if ($rpRow) {
          // Merge child-table fields directly onto the parent array so the
          // frontend hydration function can access them as top-level keys.
          $existingLoan['tct_no']            = $rpRow['tct_no'];
          $existingLoan['tax_dec_no']        = $rpRow['tax_dec_no'];
          $existingLoan['property_payments'] = $rpRow['property_payments'];
        }
      }

      // 3. Fetch matching monthly breakdown tables
      $sStmt = $pdo->prepare("SELECT * FROM loan_schedules WHERE loan_id = ? ORDER BY period ASC");
      $sStmt->execute([$existingLoan['id']]);
      $existingSchedule = $sStmt->fetchAll(PDO::FETCH_ASSOC);

      // 4. BAGONG DAGDAG: Basahin ang lahat ng dating transaction histories mula sa loan_payments storage
      $pStmt = $pdo->prepare("SELECT * FROM loan_payments WHERE member_id = ? ORDER BY created_at ASC");
      $pStmt->execute([$memberId]);
      $existingPayments = $pStmt->fetchAll(PDO::FETCH_ASSOC);

      // 5. Fetch uploaded document paths for State-B hydration of the file UI cards.
      //    Only rows with a non-null file_path are considered "active" uploads.
      $docStmt = $pdo->prepare("
        SELECT doc_type, file_name, file_path
        FROM loan_documents
        WHERE loan_id = ? AND file_path IS NOT NULL
      ");
      $docStmt->execute([$existingLoan['id']]);
      $existingDocs = [];
      foreach ($docStmt->fetchAll(PDO::FETCH_ASSOC) as $docRow) {
        $existingDocs[$docRow['doc_type']] = [
          'file_name' => $docRow['file_name'],
          'file_path' => $docRow['file_path'],
        ];
      }
    }
  } catch (PDOException $e) {
    // Silent recovery
  }
}
?>
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

  /* ── Upload Card: State-A (empty drop-zone) ────────────────────────── */
  .doc-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 18px 12px;
    border: 1.5px dashed var(--border);
    border-radius: 8px;
    background: var(--raised);
    color: var(--t3);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    text-align: center;
    transition: border-color 0.2s, background 0.2s, color 0.2s;
  }

  .doc-dropzone:hover {
    border-color: var(--gold);
    background: var(--gold-dim);
    color: var(--gold);
  }

  .doc-dropzone svg {
    opacity: 0.45;
    transition: opacity 0.2s;
  }

  .doc-dropzone:hover svg {
    opacity: 1;
  }

  /* ── Upload Card: State-B (file banner) ────────────────────────────── */
  .doc-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid rgba(39, 168, 88, 0.35);
    border-radius: 8px;
    background: rgba(39, 168, 88, 0.06);
    width: 100%;
  }

  .doc-banner__icon {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: rgba(39, 168, 88, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ok);
  }

  .doc-banner__meta {
    flex: 1;
    min-width: 0;
  }

  .doc-banner__name {
    font-size: 11px;
    font-weight: 600;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-family: var(--font-mono);
  }

  .doc-banner__actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
  }

  .doc-banner__view {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 9px;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
    border-radius: 5px;
    background: rgba(39, 168, 88, 0.12);
    color: var(--ok);
    border: 1px solid rgba(39, 168, 88, 0.25);
    transition: background 0.15s;
    font-family: var(--font-heading);
    text-transform: uppercase;
    letter-spacing: .05em;
  }

  .doc-banner__view:hover {
    background: rgba(39, 168, 88, 0.22);
  }

  .doc-banner__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 1px solid rgba(217, 61, 61, 0.25);
    border-radius: 5px;
    background: rgba(217, 61, 61, 0.06);
    color: var(--danger);
    cursor: pointer;
    transition: background 0.15s;
    font-size: 13px;
    line-height: 1;
  }

  .doc-banner__remove:hover {
    background: rgba(217, 61, 61, 0.15);
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
          <div style="display: flex; flex-direction: column; gap: 10px;">

            <?php
            // ── UNDERTAKING FILE CARD ─────────────────────────────────────
            // Renders State-B (banner) if a persisted file path exists,
            // otherwise renders State-A (drop-zone). The PHP base URL is
            // used for the persistent View href; local blob URLs are
            // assigned by JS after a new file is chosen.
            $undertakingDoc = $existingDocs['undertaking'] ?? null;
            ?>
            <div id="doc_card_undertaking">
              <?php if ($undertakingDoc && $undertakingDoc['file_path']): ?>
                <!-- STATE B — persistent file from database -->
                <div class="doc-banner" id="doc_banner_undertaking">
                  <div class="doc-banner__icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                      <polyline points="14 2 14 8 20 8" />
                    </svg>
                  </div>
                  <div class="doc-banner__meta">
                    <div class="doc-banner__name" title="<?= htmlspecialchars($undertakingDoc['file_name']) ?>">
                      <?= htmlspecialchars($undertakingDoc['file_name']) ?>
                    </div>
                  </div>
                  <div class="doc-banner__actions">
                    <a href="/acesv2/<?= htmlspecialchars($undertakingDoc['file_path']) ?>"
                      target="_blank"
                      class="doc-banner__view"
                      id="doc_view_undertaking">👁 View</a>
                    <button type="button"
                      class="doc-banner__remove"
                      title="Remove document"
                      onclick="removeUploadedDocument('undertaking')">🗑</button>
                  </div>
                </div>
              <?php else: ?>
                <!-- STATE A — empty drop-zone -->
                <label class="doc-dropzone" id="doc_zone_undertaking" for="file_undertaking">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                  </svg>
                  <span>Undertaking File</span>
                  <span style="font-weight:400; font-size:10px; opacity:0.6;">Click to browse PDF</span>
                </label>
              <?php endif; ?>
              <input type="file" id="file_undertaking" accept=".pdf" style="display:none;"
                onchange="handleFileChosen(this, 'undertaking')">
            </div>

            <?php
            // ── DEED OF ASSIGNMENT FILE CARD ──────────────────────────────
            $deedDoc = $existingDocs['deed_assignment'] ?? null;
            ?>
            <div id="doc_card_deed">
              <?php if ($deedDoc && $deedDoc['file_path']): ?>
                <!-- STATE B — persistent file from database -->
                <div class="doc-banner" id="doc_banner_deed">
                  <div class="doc-banner__icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                      <polyline points="14 2 14 8 20 8" />
                    </svg>
                  </div>
                  <div class="doc-banner__meta">
                    <div class="doc-banner__name" title="<?= htmlspecialchars($deedDoc['file_name']) ?>">
                      <?= htmlspecialchars($deedDoc['file_name']) ?>
                    </div>
                  </div>
                  <div class="doc-banner__actions">
                    <a href="/acesv2/<?= htmlspecialchars($deedDoc['file_path']) ?>"
                      target="_blank"
                      class="doc-banner__view"
                      id="doc_view_deed">👁 View</a>
                    <button type="button"
                      class="doc-banner__remove"
                      title="Remove document"
                      onclick="removeUploadedDocument('deed_assignment')">🗑</button>
                  </div>
                </div>
              <?php else: ?>
                <!-- STATE A — empty drop-zone -->
                <label class="doc-dropzone" id="doc_zone_deed" for="file_deed">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                  </svg>
                  <span>Assignment of Deed</span>
                  <span style="font-weight:400; font-size:10px; opacity:0.6;">Click to browse PDF</span>
                </label>
              <?php endif; ?>
              <input type="file" id="file_deed" accept=".pdf" style="display:none;"
                onchange="handleFileChosen(this, 'deed_assignment')">
            </div>

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
          <div class="card__label">Insurance (Principal + 1000 x 1.2 / Terms)</div>
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
        <div class="table-card__title">Amortization Schedule</div>
        <div id="terms_badge" style="font-size:11px; opacity:0.6; font-family:var(--font-mono)">0 Months</div>
      </div>
      <div class="tbl-wrap">
        <table id="schedule_table">
          <thead>
            <tr>
              <th>Period</th>
              <th style="text-align: right;">Due Date</th>
              <th>Total Amount Due</th>
              <th>Principal</th>
              <th>Interest</th>
              <th>Penalty</th>
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
      Save
    </button>
  </div>

  <div class="row" style="margin-top: 40px;">
    <div class="section-label">Application of Payment</div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--gap); align-items: start;">

      <div style="display: flex; flex-direction: column; gap: var(--gap);">
        <div class="card" style="background: #1e1e24; color: #fff; border-color: #2b2b36;">
          <div class="card__label" style="color: rgba(255,255,255,0.4);">Current Outstanding Balances</div>
          <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between;">
              <span style="opacity: 0.7;">Total Outstanding Principal:</span>
              <span id="live_total_principal" style="font-family: var(--font-mono); font-weight: 600;">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="opacity: 0.7;">Total Outstanding Interest:</span>
              <span id="live_total_interest" style="font-family: var(--font-mono); font-weight: 600;">₱0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="opacity: 0.7;">Total Outstanding Penalty:</span>
              <span id="live_total_penalty" style="font-family: var(--font-mono); font-weight: 600; color: #ff6b6b;">₱0.00</span>
            </div>
            <div style="border-top: 1px dashed rgba(255,255,255,0.2); margin-top: 5px; padding-top: 8px; display: flex; justify-content: space-between; font-size: 15px; font-weight: 700;">
              <span style="color: var(--gold);">Grand Total Due:</span>
              <span id="live_grand_total" style="font-family: var(--font-mono); color: var(--gold);">₱0.00</span>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card__label">Payment</div>
          <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 5px;">
            <div>
              <label style="font-size: 11px; font-weight:600; color: var(--t2); display:block; margin-bottom:4px;">Amount Paid (₱)</label>
              <input type="number" id="pay_amount_input" min="0.01" step="0.01" placeholder="0.00" style="background:#fff;">
            </div>
            <div>
              <label style="font-size: 11px; font-weight:600; color: var(--t2); display:block; margin-bottom:4px;">Remarks</label>
              <input type="text" id="pay_remarks_input" placeholder="Optional transaction notes...">
            </div>
            <button type="button" class="c-btn-save" id="btn_apply_payment" style="margin-top: 5px; background: var(--ok); box-shadow: 0 4px 12px rgba(39,168,88,0.2);">
              Apply
            </button>
          </div>
        </div>
      </div>

      <div class="table-card" style="margin-top: 25px;">
        <div class="table-card__head" style="background: var(--raised);">
          <div class="table-card__title">Transaction History</div>
        </div>
        <div class="tbl-wrap">
          <table>
            <thead>
              <tr>
                <th style="text-align: left; padding: 12px;">Date / Time</th>
                <th style="text-align: right;">Amount Paid</th>
                <th style="text-align: right;">Penalty Applied</th>
                <th style="text-align: right;">Interest Applied</th>
                <th style="text-align: right;">Principal Applied</th>
                <th style="text-align: right;">Excess Balance</th>
                <th style="text-align: left; padding-left: 15px;">Remarks / Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($existingPayments)): ?>
                <tr>
                  <td colspan="7" style="text-align: center; color: var(--t3); padding: 40px 10px;">
                    No permanent payment transactions found in the database for this member.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($existingPayments as $pay): ?>
                  <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="text-align: left; padding: 12px; font-family: monospace; color: var(--t2);">
                      <?= date('M d, Y h:i A', strtotime($pay['created_at'])) ?>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: var(--ok);">
                      ₱<?= number_format($pay['amount_paid'], 2) ?>
                    </td>
                    <td style="text-align: right; color: var(--danger);">
                      ₱<?= number_format($pay['penalty_applied'], 2) ?>
                    </td>
                    <td style="text-align: right; color: var(--gold);">
                      ₱<?= number_format($pay['interest_applied'], 2) ?>
                    </td>
                    <td style="text-align: right; color: #4fa8ff;">
                      ₱<?= number_format($pay['principal_applied'], 2) ?>
                    </td>
                    <td style="text-align: right; color: <?= $pay['excess_cash'] > 0 ? 'var(--gold)' : 'var(--t3)' ?>;">
                      ₱<?= number_format($pay['excess_cash'], 2) ?>
                    </td>
                    <td style="text-align: left; padding-left: 15px; color: var(--t3); font-style: italic; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                      <?= htmlspecialchars($pay['remarks'] ?? 'None') ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  const SESSION_MEMBER_ID = <?= json_encode($memberId) ?>;
  const SAVED_LOAN_DATA = <?= json_encode($existingLoan) ?>;
  const SAVED_SCHEDULE_DATA = <?= json_encode($existingSchedule) ?>;
  // Persisted loan ID — available immediately so removeUploadedDocument()
  // can issue the delete_document request without waiting for save_loan.
  const SAVED_LOAN_ID = <?= json_encode($existingLoan['id'] ?? null) ?>;
  // Keyed by doc_type; tells the JS layer which slots already have a
  // server-side file so it can skip re-uploading them on save.
  const EXISTING_DOCS = <?= json_encode($existingDocs) ?>;

  // 1. Direct and unmutated output from database array
  const RAW_SERVER_PAYMENTS = <?= json_encode($existingPayments ?? []) ?>;

  // 2. Map strictly with the exact properties that lines 375-390 of amortization-engine.js demands
  const SAVED_PAYMENTS_DATA = RAW_SERVER_PAYMENTS.map(function(p) {
    return {
      // Essential raw properties for the JS matching loops
      created_at: p.created_at,
      amount_paid: parseFloat(p.amount_paid || 0),
      penalty_applied: parseFloat(p.penalty_applied || 0),
      interest_applied: parseFloat(p.interest_applied || 0),
      principal_applied: parseFloat(p.principal_applied || 0),
      excess_cash: parseFloat(p.excess_cash || 0),
      remarks: p.remarks || '',

      // Essential camelCase fallback aliases 
      datetime: p.created_at,
      amountPaid: parseFloat(p.amount_paid || 0),
      penaltyApplied: parseFloat(p.penalty_applied || 0),
      interestApplied: parseFloat(p.interest_applied || 0),
      principalApplied: parseFloat(p.principal_applied || 0),
      excess: parseFloat(p.excess_cash || 0)
    };
  });
</script>
<script src="/acesv2/assets/js/amortization-engine.js"></script>