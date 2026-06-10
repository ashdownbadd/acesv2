/**
 * ACESv3 - Loan Amortization Reactive Client Engine with Auto-Hydration,
 * Persistence, and Strict Waterfall Payment Application Ledger System.
 */

document.addEventListener("DOMContentLoaded", () => {
  // --- DOM Elements Handle References ---
  const form = document.getElementById("amortizationForm");
  const loanTypeSelect = document.getElementById("loan_type");
  const collateralSelect = document.getElementById("collateral");
  const amortTypeSelect = document.getElementById("amort_type");
  const principalInput = document.getElementById("principal");
  const interestInput = document.getElementById("interest_rate");
  const termsInput = document.getElementById("terms");
  const startDateInput = document.getElementById("start_date");

  // Real Property Conditional Fields
  const rpSection = document.getElementById("real_property_section");
  const rpTct = document.getElementById("rp_tct");
  const rpTax = document.getElementById("rp_tax");
  const rpPayments = document.getElementById("rp_payments");

  // Calculated Static Displays
  const feeProcessing = document.getElementById("fee_processing");
  const feeInsurance = document.getElementById("fee_insurance");
  const feeNotarial = document.getElementById("fee_notarial");

  // Summary Outputs
  const sumAmortization = document.getElementById("sum_amortization");
  const sumNetProceeds = document.getElementById("sum_net_proceeds");
  const sumInterest = document.getElementById("sum_interest");
  const sumTotalPayment = document.getElementById("sum_total_payment");
  const termsBadge = document.getElementById("terms_badge");
  const scheduleBody = document.getElementById("schedule_body");
  const saveButton = document.getElementById("btn_save_loan");

  // --- Strict Waterfall Payment State Arrays ---
  let scheduleArray = [];
  let paymentLedger = [];
  let isHydrating = false;

  const formatCurrency = (num) => {
    return (
      "₱" +
      parseFloat(num || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  };

  const safeNum = (val) => parseFloat(val || 0);

  const handleLoanTypeTransformation = () => {
    if (loanTypeSelect.value === "Micro-Finance Loan") {
      interestInput.value = 5;
    } else {
      interestInput.value = 2;
    }
    if (!isHydrating) calculateLoanPipeline();
  };

  const handleCollateralToggles = () => {
    if (collateralSelect.value === "Real Property") {
      rpSection.style.display = "block";
    } else {
      rpSection.style.display = "none";
      if (!isHydrating) {
        rpTct.value = "";
        rpTax.value = "";
        rpPayments.value = "";
      }
    }
  };

  // --- Dynamic Calculator for Overdue Penalties via 3% Rule ---
  const calculateRowPenaltyState = (dueDateStr, totalOwedAmount) => {
    if (!dueDateStr) return 0;
    const today = new Date();
    const dueDate = new Date(dueDateStr);

    if (today <= dueDate) return 0;

    let monthsPastDue =
      (today.getFullYear() - dueDate.getFullYear()) * 12 +
      (today.getMonth() - dueDate.getMonth());

    if (monthsPastDue <= 0 && today > dueDate) {
      monthsPastDue = 1;
    }
    return monthsPastDue > 0
      ? safeNum(totalOwedAmount * 0.03 * monthsPastDue)
      : 0;
  };

  // --- Inject State Memory Parameter Trackers into the Row ---
  const initializeRowTrackingState = (row) => {
    // Ensure both naming conventions exist so the engine works
    // whether the row came from calculateLoanPipeline or hydrateSavedData.
    if (row.principal === undefined)
      row.principal = row.principalComponent ?? 0;
    if (row.interest === undefined) row.interest = row.interestComponent ?? 0;

    if (row.remPrincipal === undefined)
      row.remPrincipal = safeNum(row.principal);
    if (row.remInterest === undefined) row.remInterest = safeNum(row.interest);

    // Set origPenalty and remPenalty together.
    // We check origPenalty (not remPenalty) because remPenalty can be 0
    // for two different reasons: "never had a penalty" vs "penalty was paid".
    // origPenalty is the only reliable way to distinguish the two.
    if (row.origPenalty === undefined) {
      if (row.status === "paid") {
        row.remPenalty = 0;
        row.origPenalty = 0;
      } else {
        const penaltyCalc = calculateRowPenaltyState(
          row.dueDate,
          row.remPrincipal + row.remInterest,
        );
        // Only set remPenalty from fresh calculation if it wasn't already
        // set by hydration (hydration waterfall may have already reduced it).
        if (row.remPenalty === undefined) row.remPenalty = penaltyCalc;
        row.origPenalty = penaltyCalc;
      }
    }

    if (row.status === undefined || row.status === "unpaid") {
      row.status = row.origPenalty > 0 ? "overdue" : "pending";
    }
  };

  // --- Global Payment Summary Panel Recalculation Matrix ---
  const recalculateOutstandingBalancesPanel = () => {
    let totalPrincipal = 0,
      totalInterest = 0,
      totalPenalty = 0;

    scheduleArray.forEach((row) => {
      initializeRowTrackingState(row);
      if (row.status !== "paid") {
        totalPrincipal += row.remPrincipal;
        totalInterest += row.remInterest;
        totalPenalty += row.remPenalty;
      }
    });

    const liveTotalPrincipal = document.getElementById("live_total_principal");
    const liveTotalInterest = document.getElementById("live_total_interest");
    const liveTotalPenalty = document.getElementById("live_total_penalty");
    const liveGrandTotal = document.getElementById("live_grand_total");

    if (liveTotalPrincipal)
      liveTotalPrincipal.textContent = formatCurrency(totalPrincipal);
    if (liveTotalInterest)
      liveTotalInterest.textContent = formatCurrency(totalInterest);
    if (liveTotalPenalty)
      liveTotalPenalty.textContent = formatCurrency(totalPenalty);
    if (liveGrandTotal)
      liveGrandTotal.textContent = formatCurrency(
        totalPrincipal + totalInterest + totalPenalty,
      );
  };

  // --- Main Calculation Architecture Pipeline ---
  const calculateLoanPipeline = () => {
    if (isHydrating) return;

    const principalValue = parseFloat(principalInput.value) || 0;
    const interestRatePercent = parseFloat(interestInput.value) || 0;
    const terms = parseInt(termsInput.value) || 0;
    const start = startDateInput.value;
    const amortType = amortTypeSelect.value;

    if (principalValue <= 0 || terms <= 0 || !amortType) {
      clearSummaryAndSchedule();
      return;
    }

    const processingFeeValue = principalValue * 0.02;
    const insuranceFeeValue = ((principalValue + 1000) * 1.2) / terms;
    const notarialFeeValue = 400.0;

    feeProcessing.value = formatCurrency(processingFeeValue);
    feeInsurance.value = formatCurrency(insuranceFeeValue);
    feeNotarial.value = formatCurrency(notarialFeeValue);

    const netProceedsValue =
      principalValue -
      (processingFeeValue + insuranceFeeValue + notarialFeeValue);
    sumNetProceeds.textContent = formatCurrency(netProceedsValue);
    termsBadge.textContent = `${terms} Months`;

    scheduleArray = [];
    let totalInterestCalculated = 0;
    let totalRepaymentCalculated = 0;
    let amortPerPeriodValue = 0;

    const monthlyRate = interestRatePercent / 100;
    let baseDate = start ? new Date(start) : new Date();

    if (amortType === "Straight-line") {
      const monthlyPrincipal = principalValue / terms;
      const monthlyInterest = principalValue * monthlyRate;
      amortPerPeriodValue = monthlyPrincipal + monthlyInterest;

      for (let i = 1; i <= terms; i++) {
        baseDate.setMonth(baseDate.getMonth() + 1);

        scheduleArray.push({
          period: i,
          dueDate: baseDate.toISOString().split("T")[0],
          amortization: amortPerPeriodValue,
          principal: monthlyPrincipal,
          interest: monthlyInterest,
        });
        totalInterestCalculated += monthlyInterest;
      }
      totalRepaymentCalculated = principalValue + totalInterestCalculated;
    } else if (amortType === "Diminishing balance") {
      if (monthlyRate > 0) {
        amortPerPeriodValue =
          (principalValue * monthlyRate * Math.pow(1 + monthlyRate, terms)) /
          (Math.pow(1 + monthlyRate, terms) - 1);
      } else {
        amortPerPeriodValue = principalValue / terms;
      }
      let remainingBalance = principalValue;

      for (let i = 1; i <= terms; i++) {
        const currentPeriodInterest = remainingBalance * monthlyRate;
        let currentPeriodPrincipal =
          amortPerPeriodValue - currentPeriodInterest;
        remainingBalance -= currentPeriodPrincipal;

        if (i === terms || remainingBalance < 0) {
          currentPeriodPrincipal += remainingBalance;
          remainingBalance = 0;
        }
        baseDate.setMonth(baseDate.getMonth() + 1);

        scheduleArray.push({
          period: i,
          dueDate: baseDate.toISOString().split("T")[0],
          amortization: currentPeriodPrincipal + currentPeriodInterest,
          principal: currentPeriodPrincipal,
          interest: currentPeriodInterest,
        });
        totalInterestCalculated += currentPeriodInterest;
      }
      totalRepaymentCalculated = principalValue + totalInterestCalculated;
    } else if (amortType === "Manual") {
      const uniformPrincipal = principalValue / terms;
      const uniformInterest = principalValue * monthlyRate;
      amortPerPeriodValue = uniformPrincipal + uniformInterest;

      for (let i = 1; i <= terms; i++) {
        baseDate.setMonth(baseDate.getMonth() + 1);

        scheduleArray.push({
          period: i,
          dueDate: baseDate.toISOString().split("T")[0],
          amortization: uniformPrincipal + uniformInterest,
          principal: uniformPrincipal,
          interest: uniformInterest,
        });
        totalInterestCalculated += uniformInterest;
      }
      totalRepaymentCalculated = principalValue + totalInterestCalculated;
    }

    sumAmortization.textContent = formatCurrency(amortPerPeriodValue);
    sumInterest.textContent = formatCurrency(totalInterestCalculated);
    sumTotalPayment.textContent = formatCurrency(totalRepaymentCalculated);

    renderScheduleTable(amortType === "Manual");
  };

  // --- Comprehensive Schedule Table Renderer Engine ---
  const renderScheduleTable = (isEditable = false) => {
    scheduleBody.innerHTML = "";
    if (scheduleArray.length === 0) {
      scheduleBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--t3);padding:30px;">Fill out parameters above to dynamically compute schedule data.</td></tr>`;
      if (document.getElementById("live_grand_total"))
        recalculateOutstandingBalancesPanel();
      return;
    }

    scheduleArray.forEach((row) => initializeRowTrackingState(row));

    scheduleArray.forEach((row, index) => {
      const tr = document.createElement("tr");

      if (isEditable) {
        tr.innerHTML = `
                    <td>Month ${row.period}</td>
                    <td><input type="date" value="${row.dueDate}" class="editable-cell" data-index="${index}" data-field="dueDate" style="width:120px;"></td>
                    <td><input type="number" step="0.01" value="${parseFloat(row.amortization).toFixed(2)}" class="editable-cell" data-index="${index}" data-field="amortization"></td>
                    <td><input type="number" step="0.01" value="${parseFloat(row.principal).toFixed(2)}" class="editable-cell" data-index="${index}" data-field="principal"></td>
                    <td><input type="number" step="0.01" value="${parseFloat(row.interest).toFixed(2)}" class="editable-cell" data-index="${index}" data-field="interest"></td>
                    <td>—</td>`;
      } else {
        let penaltyBadgeText = "";
        if (row.origPenalty === 0) {
          penaltyBadgeText = '<span style="color:var(--t3)">No penalty</span>';
        } else if (row.origPenalty > 0 && row.remPenalty <= 0) {
          penaltyBadgeText =
            '<span style="color:var(--ok); font-weight:600;">Paid</span>';
        } else {
          penaltyBadgeText = `<span style="color:var(--danger); font-weight:600;">${formatCurrency(row.remPenalty)}</span>`;
        }

        let rowStyle = "";
        if (row.status === "paid") {
          rowStyle =
            "background: rgba(39, 168, 88, 0.05); opacity: 0.8; text-decoration: line-through;";
        } else if (row.status === "overdue") {
          rowStyle = "background: rgba(217, 61, 61, 0.04);";
        }

        if (rowStyle) tr.setAttribute("style", rowStyle);

        tr.innerHTML = `
                    <td>Month ${row.period}</td>
                    <td style="color:var(--t3); text-align:right;">${row.dueDate}</td>
                    <td style="font-weight:600; color:var(--gold)">${formatCurrency(row.remPrincipal + row.remInterest + row.remPenalty)}</td>
                    <td>${formatCurrency(row.remPrincipal)}</td>
                    <td>${formatCurrency(row.remInterest)}</td>
                    <td>${penaltyBadgeText}</td>`;
      }
      scheduleBody.appendChild(tr);
    });

    if (isEditable) {
      bindManualMutationListeners();
    }

    recalculateOutstandingBalancesPanel();
  };

  const bindManualMutationListeners = () => {
    const cells = scheduleBody.querySelectorAll(".editable-cell");
    cells.forEach((cell) => {
      cell.addEventListener("input", (e) => {
        const idx = parseInt(e.target.getAttribute("data-index"));
        const field = e.target.getAttribute("data-field");
        const val = e.target.value;

        if (field === "dueDate") {
          scheduleArray[idx].dueDate = val;
        } else {
          scheduleArray[idx][field] = parseFloat(val) || 0;
          if (field === "principal" || field === "interest") {
            scheduleArray[idx].amortization =
              scheduleArray[idx].principal + scheduleArray[idx].interest;
            const rowAmortInput = scheduleBody.querySelector(
              `.editable-cell[data-index="${idx}"][data-field="amortization"]`,
            );
            if (rowAmortInput)
              rowAmortInput.value = scheduleArray[idx].amortization.toFixed(2);
          }
        }
        delete scheduleArray[idx].remPrincipal;
        delete scheduleArray[idx].remInterest;
        delete scheduleArray[idx].remPenalty;
        delete scheduleArray[idx].origPenalty;

        updateSummaryFromManualState();
      });
    });
  };

  const updateSummaryFromManualState = () => {
    let runningInterest = 0,
      runningTotal = 0;
    scheduleArray.forEach((row) => {
      runningInterest += row.interest;
      runningTotal += row.amortization;
    });
    sumInterest.textContent = formatCurrency(runningInterest);
    sumTotalPayment.textContent = formatCurrency(runningTotal);
    renderScheduleTable(amortTypeSelect.value === "Manual");
  };

  const clearSummaryAndSchedule = () => {
    feeProcessing.value = "₱0.00";
    feeInsurance.value = "₱0.00";
    sumAmortization.textContent = "₱0.00";
    sumNetProceeds.textContent = "₱0.00";
    sumInterest.textContent = "₱0.00";
    sumTotalPayment.textContent = "₱0.00";
    termsBadge.textContent = "0 Months";
    scheduleArray = [];
    renderScheduleTable();
  };

  // ── STRICT WATERFALL METHOD ALGORITHM IMPLEMENTATION ──────────
  async function applyGlobalPayment(amountPaid, remarks = "") {
    const unpaidRows = scheduleArray.filter((row) => row.status !== "paid");
    if (unpaidRows.length === 0 || amountPaid <= 0) return;

    let remaining = amountPaid;

    unpaidRows.forEach((row) => {
      row.appliedPenalty = 0;
      row.appliedInterest = 0;
      row.appliedPrincipal = 0;
    });

    // PASS 1 — Penalty First
    for (let row of unpaidRows) {
      if (remaining <= 0) break;
      let take = Math.min(remaining, row.remPenalty);
      row.appliedPenalty = take;
      remaining -= take;
    }

    // PASS 2 — Interest Second
    for (let row of unpaidRows) {
      if (remaining <= 0) break;
      let take = Math.min(remaining, row.remInterest);
      row.appliedInterest = take;
      remaining -= take;
    }

    // PASS 3 — Principal Third
    for (let row of unpaidRows) {
      if (remaining <= 0) break;
      let take = Math.min(remaining, row.remPrincipal);
      row.appliedPrincipal = take;
      remaining -= take;
    }

    let excess = remaining;

    let summaryPenaltyApplied = 0;
    let summaryInterestApplied = 0;
    let summaryPrincipalApplied = 0;

    unpaidRows.forEach((row) => {
      row.remPenalty = Math.max(0, row.remPenalty - row.appliedPenalty);
      row.remInterest = Math.max(0, row.remInterest - row.appliedInterest);
      row.remPrincipal = Math.max(0, row.remPrincipal - row.appliedPrincipal);

      summaryPenaltyApplied += row.appliedPenalty;
      summaryInterestApplied += row.appliedInterest;
      summaryPrincipalApplied += row.appliedPrincipal;

      if (
        row.remPenalty === 0 &&
        row.remInterest === 0 &&
        row.remPrincipal === 0
      ) {
        row.status = "paid";
      } else if (row.remPenalty > 0) {
        row.status = "overdue";
      } else {
        row.status = "pending";
      }
    });

    // Bagong entry format na may timestamp
    const entry = {
      datetime: new Date().toISOString(),
      amountPaid: amountPaid,
      penaltyApplied: summaryPenaltyApplied,
      interestApplied: summaryInterestApplied,
      principalApplied: summaryPrincipalApplied,
      excess: excess,
      remarks: remarks,
    };

    // ASYNC DB SAVE: I-save kaagad ang transaction sa backend table natin
    try {
      const response = await fetch(`amortization.php?action=save_payment`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          payment: {
            member_id: SESSION_MEMBER_ID,
            amount_paid: amountPaid,
            penalty_applied: summaryPenaltyApplied,
            interest_applied: summaryInterestApplied,
            principal_applied: summaryPrincipalApplied,
            excess_cash: excess,
            remarks: remarks,
          },
        }),
      });
      const res = await response.json();
      if (!res.success) {
        alert("Payment DB Error: " + res.error);
        return;
      }
    } catch (err) {
      console.error(err);
      alert("Network Error: Hindi mai-save ang bayad sa database.");
      return;
    }

    // Kung successful sa backend, i-push sa local UI ledger state
    paymentLedger.push(entry);

    renderScheduleTable(amortTypeSelect.value === "Manual");
    renderLedgerTable();
  }

  const renderLedgerTable = () => {
    // Hahanapin ang element kung may manual update request,
    // kung bagong refresh ang page, hahayaan ang PHP HTML rendering na gumana.
    const ledgerBody = document.getElementById("ledger_body");
    if (!ledgerBody) return;

    if (paymentLedger.length === 0) {
      return; // Hayaan ang pre-rendered database HTML rows ng PHP ang manatili sa screen
    }

    ledgerBody.innerHTML = "";
    paymentLedger.forEach((log) => {
      const tr = document.createElement("tr");
      tr.style.borderBottom = "1px solid rgba(255,255,255,0.05)";

      const dateParsed = new Date(
        log.datetime || log.created_at,
      ).toLocaleString("en-US", {
        hour12: true,
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });

      tr.innerHTML = `
                <td style="text-align: left; padding: 12px; font-family: monospace; color: var(--t2);">${dateParsed}</td>
                <td style="text-align: right; font-weight: 600; color: var(--ok);">${formatCurrency(log.amountPaid || log.amount_paid)}</td>
                <td style="text-align: right; color: var(--danger);">${formatCurrency(log.penaltyApplied || log.penalty_applied)}</td>
                <td style="text-align: right; color: var(--gold);">${formatCurrency(log.interestApplied || log.interest_applied)}</td>
                <td style="text-align: right; color: #4fa8ff;">${formatCurrency(log.principalApplied || log.principal_applied)}</td>
                <td style="text-align: right; color: ${(log.excess || log.excess_cash) > 0 ? "var(--gold)" : "var(--t3)"};">${formatCurrency(log.excess || log.excess_cash)}</td>
                <td style="text-align: left; padding-left: 15px; color: var(--t3); font-style: italic;">${log.remarks || "None"}</td>`;
      ledgerBody.appendChild(tr);
    });
  };

  const btnApplyPayment = document.getElementById("btn_apply_payment");
  if (btnApplyPayment) {
    btnApplyPayment.addEventListener("click", () => {
      const amtInput = document.getElementById("pay_amount_input");
      const remInput = document.getElementById("pay_remarks_input");

      const amount = safeNum(amtInput.value);
      const remarks = remInput.value.trim();

      if (amount <= 0 || isNaN(amount)) {
        alert("Please enter a valid numeric payment value greater than zero.");
        return;
      }

      const unpaidRows = scheduleArray.filter((row) => row.status !== "paid");
      if (unpaidRows.length === 0) {
        alert(
          "This loan configuration account is completely paid off already.",
        );
        return;
      }

      applyGlobalPayment(amount, remarks);

      amtInput.value = "";
      remInput.value = "";
    });
  }

  // ── DOCUMENT LIFECYCLE HELPERS ───────────────────────────────────────

  // ── handleFileChosen ─────────────────────────────────────────────────
  // Fired by onchange on each hidden <input type="file">.
  // Immediately creates a local blob URL so the user can verify the PDF
  // in a new tab BEFORE hitting Save, then swaps the card into State B.
  // The actual server upload still happens inside uploadDocumentIfSelected
  // which is called after save_loan succeeds and a loan_id is confirmed.
  const handleFileChosen = (inputEl, docType) => {
    if (!inputEl.files || inputEl.files.length === 0) return;
    const file = inputEl.files[0];

    if (!file.name.toLowerCase().endsWith(".pdf")) {
      alert("Only PDF files are accepted. Please select a valid PDF.");
      inputEl.value = "";
      return;
    }

    // Build a temporary object URL for instant in-browser preview
    const blobUrl = URL.createObjectURL(file);

    // Determine which card wrapper and key suffix to use
    const suffix = docType === "undertaking" ? "undertaking" : "deed";
    const cardEl = document.getElementById(`doc_card_${suffix}`);
    if (!cardEl) return;

    // Inject a State-B banner, wiring the View link to the blob URL
    cardEl.innerHTML = `
      <div class="doc-banner" id="doc_banner_${suffix}">
        <div class="doc-banner__icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
        </div>
        <div class="doc-banner__meta">
          <div class="doc-banner__name" title="${file.name}">${file.name}</div>
        </div>
        <div class="doc-banner__actions">
          <a href="${blobUrl}" target="_blank" class="doc-banner__view" id="doc_view_${suffix}">👁 View</a>
          <button type="button" class="doc-banner__remove" title="Remove document"
                  onclick="removeUploadedDocument('${docType}')">🗑</button>
        </div>
      </div>
      <input type="file" id="file_${docType === "undertaking" ? "undertaking" : "deed"}"
             accept=".pdf" style="display:none;"
             onchange="handleFileChosen(this, '${docType}')">
    `;
  };

  // ── uploadDocumentIfSelected ──────────────────────────────────────────
  // Called after save_loan succeeds. Silently skips if:
  //  (a) the input has no file selected (user didn't touch it), OR
  //  (b) EXISTING_DOCS already has a live path for this slot (already on server).
  // This prevents re-uploading a file that the user never changed.
  const uploadDocumentIfSelected = async (inputId, docType, loanId) => {
    // If the server already has a file for this doc_type, skip upload.
    if (
      typeof EXISTING_DOCS !== "undefined" &&
      EXISTING_DOCS[docType]?.file_path
    )
      return;

    const input = document.getElementById(inputId);
    if (!input || !input.files || input.files.length === 0) return;

    const formData = new FormData();
    formData.append("document", input.files[0]);
    formData.append("loan_id", loanId);
    formData.append("doc_type", docType);

    try {
      const res = await fetch("amortization.php?action=upload_document", {
        method: "POST",
        body: formData,
      });
      const result = await res.json();
      if (!result.success) {
        console.warn(`Document upload failed (${docType}):`, result.error);
      }
    } catch (err) {
      console.error(`Document upload network error (${docType}):`, err);
    }
  };

  // ── removeUploadedDocument ────────────────────────────────────────────
  // Global function (called via inline onclick from PHP-rendered HTML).
  // Issues a POST to delete_document, then resets the card to State A.
  // If no loan has been saved yet (SAVED_LOAN_ID is null), we only reset
  // the local UI — there is nothing to delete on the server.
  window.removeUploadedDocument = async (docType) => {
    const suffix = docType === "undertaking" ? "undertaking" : "deed";
    const cardEl = document.getElementById(`doc_card_${suffix}`);
    const inputId =
      docType === "undertaking" ? "file_undertaking" : "file_deed";
    const label =
      docType === "undertaking" ? "Undertaking File" : "Assignment of Deed";

    const activeLoanId =
      typeof SAVED_LOAN_ID !== "undefined" && SAVED_LOAN_ID
        ? SAVED_LOAN_ID
        : null;

    if (activeLoanId) {
      // Confirm before making the destructive server call
      if (
        !confirm(
          `Remove this document? This will detach it from the loan record.`,
        )
      )
        return;

      try {
        const res = await fetch("amortization.php?action=delete_document", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ loan_id: activeLoanId, doc_type: docType }),
        });
        const result = await res.json();
        if (!result.success) {
          alert("Server error while removing document: " + result.error);
          return;
        }

        // Clear the EXISTING_DOCS slot so a new upload can proceed on next Save
        if (typeof EXISTING_DOCS !== "undefined" && EXISTING_DOCS[docType]) {
          EXISTING_DOCS[docType] = null;
        }
      } catch (err) {
        console.error("Remove document network error:", err);
        alert("Network error — could not reach the server. Please try again.");
        return;
      }
    }

    // ── Reset card to State A (drop-zone) ────────────────────────────
    if (!cardEl) return;
    cardEl.innerHTML = `
      <label class="doc-dropzone" id="doc_zone_${suffix}" for="${inputId}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/>
          <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        <span>${label}</span>
        <span style="font-weight:400; font-size:10px; opacity:0.6;">Click to browse PDF</span>
      </label>
      <input type="file" id="${inputId}" accept=".pdf" style="display:none;"
             onchange="handleFileChosen(this, '${docType}')">
    `;
  };

  // Expose to global scope — called from PHP-rendered inline onchange attributes
  window.handleFileChosen = handleFileChosen;
  saveButton.addEventListener("click", async () => {
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    if (scheduleArray.length === 0) {
      alert("Please generate a valid amortization schedule before saving.");
      return;
    }

    const mappedSchedule = scheduleArray.map((row) => ({
      period: row.period,
      dueDate: row.dueDate,
      amortization: row.amortization,
      principalComponent: row.principal,
      interestComponent: row.interest,
      remainingPrincipal: 0,
    }));

    const payload = {
      loan: {
        member_id: SESSION_MEMBER_ID,
        loan_type: loanTypeSelect.value,
        collateral: collateralSelect.value,
        soa_status: soa_status.value,
        amort_type: amortTypeSelect.value,
        principal: parseFloat(principalInput.value),
        interest_rate: parseFloat(interestInput.value),
        terms: parseInt(termsInput.value),
        start_date: startDateInput.value,
      },
      // ── Real Property child-table node ────────────────────────────────
      // Compiled separately so the backend can route it to the dedicated
      // loan_real_property_details table via its own UPSERT pipeline.
      // Sent on every save; the backend ignores it when collateral is not
      // "Real Property", so there is no risk of spurious writes.
      real_property: {
        tct_no: rpTct.value || null,
        tax_dec_no: rpTax.value || null,
        property_payments: rpPayments.value || null,
      },
      schedule: mappedSchedule,
    };

    saveButton.disabled = true;
    saveButton.textContent = "Saving...";

    try {
      const response = await fetch(`amortization.php?action=save_loan`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const result = await response.json();
      if (result.success) {
        // Upload any selected Real Property documents
        if (result.loan_id) {
          await uploadDocumentIfSelected(
            "file_undertaking",
            "undertaking",
            result.loan_id,
          );
          await uploadDocumentIfSelected(
            "file_deed",
            "deed_assignment",
            result.loan_id,
          );
        }
        alert(result.message);
      } else {
        alert("Database Error: " + result.error);
      }
    } catch (err) {
      console.error(err);
      alert("Network Connection Error occurred while saving configuration.");
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = "Save";
    }
  });

  const hydrateSavedData = () => {
    if (
      !SAVED_LOAN_DATA ||
      !SAVED_SCHEDULE_DATA ||
      SAVED_SCHEDULE_DATA.length === 0
    )
      return;

    isHydrating = true;

    // ── 1. Populate Form Fields ───────────────────────────────────────
    loanTypeSelect.value = SAVED_LOAN_DATA.loan_type || "";
    collateralSelect.value = SAVED_LOAN_DATA.collateral || "";
    amortTypeSelect.value = SAVED_LOAN_DATA.amort_type || "";
    principalInput.value = SAVED_LOAN_DATA.principal_amount || "";
    interestInput.value = SAVED_LOAN_DATA.interest_rate || "";
    termsInput.value = SAVED_LOAN_DATA.terms || "";
    startDateInput.value = SAVED_LOAN_DATA.start_date || "";

    if (typeof handleCollateralToggles === "function")
      handleCollateralToggles();

    // ── 1b. Hydrate Real Property fields ─────────────────────────────
    // After handleCollateralToggles() has made the section visible,
    // populate the three child-table fields that the PHP backend appended
    // to SAVED_LOAN_DATA when collateral === "Real Property".
    // Guards prevent errors if the section is absent or the fields are null.
    if (SAVED_LOAN_DATA.collateral === "Real Property") {
      if (rpTct && SAVED_LOAN_DATA.tct_no != null)
        rpTct.value = SAVED_LOAN_DATA.tct_no;

      if (rpTax && SAVED_LOAN_DATA.tax_dec_no != null)
        rpTax.value = SAVED_LOAN_DATA.tax_dec_no;

      if (rpPayments && SAVED_LOAN_DATA.property_payments != null)
        rpPayments.value = SAVED_LOAN_DATA.property_payments;
    }

    // ── 2. Rebuild Schedule Array from DB rows ────────────────────────
    // Store values under BOTH naming conventions so the rest of the
    // engine (which uses row.principal / row.interest) works correctly.
    scheduleArray = SAVED_SCHEDULE_DATA.map((row) => {
      const principal = safeNum(row.principal_component);
      const interest = safeNum(row.interest_component);

      return {
        period: parseInt(row.period),
        dueDate: row.due_date || row.dueDate || "",
        amortization: safeNum(row.amortization),

        // Engine naming (used by calculateLoanPipeline and initializeRowTrackingState)
        principal: principal,
        interest: interest,

        // DB naming (kept for compatibility with save payload)
        principalComponent: principal,
        interestComponent: interest,
        remainingPrincipal: safeNum(row.remaining_principal),

        // Waterfall state — start at full values; waterfall will reduce them
        remPrincipal: principal,
        remInterest: interest,
        remPenalty: undefined, // intentionally undefined so initializeRowTrackingState computes it fresh
        origPenalty: undefined, // same — will be set by initializeRowTrackingState

        status: "pending", // default; waterfall will correct this
      };
    });

    // ── 3. Initialize tracking state (sets origPenalty, remPenalty, status) ─
    scheduleArray.forEach((row) => initializeRowTrackingState(row));

    // ── 4. Load Payment History ───────────────────────────────────────
    if (
      typeof SAVED_PAYMENTS_DATA !== "undefined" &&
      Array.isArray(SAVED_PAYMENTS_DATA) &&
      SAVED_PAYMENTS_DATA.length > 0
    ) {
      paymentLedger = SAVED_PAYMENTS_DATA.map((p) => ({
        datetime: p.created_at || p.datetime || "",
        amountPaid: safeNum(p.amount_paid ?? p.amountPaid),
        penaltyApplied: safeNum(p.penalty_applied ?? p.penaltyApplied),
        interestApplied: safeNum(p.interest_applied ?? p.interestApplied),
        principalApplied: safeNum(p.principal_applied ?? p.principalApplied),
        excess: safeNum(p.excess_cash ?? p.excess),
        remarks: p.remarks || "",
      }));

      // ── 5. Replay Waterfall to Reduce rem* Values ─────────────────
      // Each payment is replayed in chronological order (ASC from DB).
      // Three passes per payment — same strict order as live applyGlobalPayment.
      paymentLedger.forEach((pay) => {
        let penLeft = pay.penaltyApplied;
        let intLeft = pay.interestApplied;
        let princLeft = pay.principalApplied;

        // Pass 1 — Penalty
        for (const row of scheduleArray) {
          if (penLeft <= 0) break;
          if (row.status === "paid") continue;
          const take = Math.min(penLeft, row.remPenalty);
          row.remPenalty -= take;
          penLeft -= take;
        }

        // Pass 2 — Interest
        for (const row of scheduleArray) {
          if (intLeft <= 0) break;
          if (row.status === "paid") continue;
          const take = Math.min(intLeft, row.remInterest);
          row.remInterest -= take;
          intLeft -= take;
        }

        // Pass 3 — Principal
        for (const row of scheduleArray) {
          if (princLeft <= 0) break;
          if (row.status === "paid") continue;
          const take = Math.min(princLeft, row.remPrincipal);
          row.remPrincipal -= take;
          princLeft -= take;
        }

        // Update status after each payment replay
        scheduleArray.forEach((row) => {
          if (row.status === "paid") return; // already paid, skip
          if (
            row.remPrincipal <= 0 &&
            row.remInterest <= 0 &&
            row.remPenalty <= 0
          ) {
            row.status = "paid";
          } else if (row.remPenalty > 0) {
            row.status = "overdue";
          } else {
            row.status = "pending";
          }
        });
      });
    }

    // ── 6. Compute and Display Fee Cards ─────────────────────────────
    // calculateLoanPipeline is blocked during hydration (isHydrating guard),
    // so we compute and set the fee fields explicitly here.
    const principal = safeNum(SAVED_LOAN_DATA.principal_amount);
    const terms = parseInt(SAVED_LOAN_DATA.terms || 0);

    if (principal > 0 && terms > 0) {
      const processingFee = principal * 0.02;
      const insuranceFee = ((principal + 1000) * 1.2) / terms;
      const notarialFee = 400;
      const netProceeds =
        principal - processingFee - insuranceFee - notarialFee;

      if (feeProcessing) feeProcessing.value = formatCurrency(processingFee);
      if (feeInsurance) feeInsurance.value = formatCurrency(insuranceFee);
      if (feeNotarial) feeNotarial.value = formatCurrency(notarialFee);
      if (sumNetProceeds)
        sumNetProceeds.textContent = formatCurrency(netProceeds);
    }

    // ── 7. Compute Summary Totals ─────────────────────────────────────
    let totalInterest = 0,
      totalRepay = 0;
    scheduleArray.forEach((r) => {
      totalInterest += r.interest; // use engine naming (.interest not .interestComponent)
      totalRepay += r.amortization;
    });

    if (sumAmortization)
      sumAmortization.textContent = formatCurrency(
        scheduleArray[0]?.amortization || 0,
      );
    if (sumInterest) sumInterest.textContent = formatCurrency(totalInterest);
    if (sumTotalPayment)
      sumTotalPayment.textContent = formatCurrency(totalRepay);
    if (termsBadge) termsBadge.textContent = `${terms} Months`;

    // ── 8. Render ─────────────────────────────────────────────────────
    renderScheduleTable(SAVED_LOAN_DATA.amort_type === "Manual");

    const ledgerBodyEl = document.getElementById("ledger_body");
    if (
      ledgerBodyEl &&
      typeof renderLedgerTable === "function" &&
      paymentLedger.length > 0
    ) {
      renderLedgerTable();
    }

    isHydrating = false;
  };

  const reactiveSelectors = [
    loanTypeSelect,
    collateralSelect,
    amortTypeSelect,
    principalInput,
    termsInput,
    startDateInput,
  ];
  reactiveSelectors.forEach((el) => {
    if (el) {
      el.addEventListener("input", calculateLoanPipeline);
      el.addEventListener("change", calculateLoanPipeline);
    }
  });

  loanTypeSelect.addEventListener("change", handleLoanTypeTransformation);
  collateralSelect.addEventListener("change", handleCollateralToggles);

  hydrateSavedData();
});
