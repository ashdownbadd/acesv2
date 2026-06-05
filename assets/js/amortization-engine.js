/**
 * ACESv3 - Loan Amortization Reactive Client Engine with Auto-Hydration & Persistence
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Elements Handle References ---
    const form = document.getElementById('amortizationForm');
    const loanTypeSelect = document.getElementById('loan_type');
    const collateralSelect = document.getElementById('collateral');
    const amortTypeSelect = document.getElementById('amort_type');
    const principalInput = document.getElementById('principal');
    const interestInput = document.getElementById('interest_rate');
    const termsInput = document.getElementById('terms');
    const startDateInput = document.getElementById('start_date');

    // Real Property Conditional Fields
    const rpSection = document.getElementById('real_property_section');
    const rpTct = document.getElementById('rp_tct');
    const rpTax = document.getElementById('rp_tax');
    const rpPayments = document.getElementById('rp_payments');
    const fileUndertaking = document.getElementById('file_undertaking');
    const fileDeed = document.getElementById('file_deed');

    // Calculated Static Displays
    const feeProcessing = document.getElementById('fee_processing');
    const feeInsurance = document.getElementById('fee_insurance');
    const feeNotarial = document.getElementById('fee_notarial');

    // Summary Outputs
    const sumAmortization = document.getElementById('sum_amortization');
    const sumNetProceeds = document.getElementById('sum_net_proceeds');
    const sumInterest = document.getElementById('sum_interest');
    const sumTotalPayment = document.getElementById('sum_total_payment');
    const termsBadge = document.getElementById('terms_badge');
    const scheduleBody = document.getElementById('schedule_body');
    const saveButton = document.getElementById('btn_save_loan');

    let scheduleArray = [];
    let isHydrating = false; // Prevents calculations from running during startup load loops

    const formatCurrency = (num) => {
        return '₱' + parseFloat(num || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

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
            rpSection.style.display = 'block';
        } else {
            rpSection.style.display = 'none';
            if (!isHydrating) {
                rpTct.value = ''; rpTax.value = ''; rpPayments.value = '';
            }
        }
    };

    // --- Main Calculation Architecture Pipeline ---
    const calculateLoanPipeline = () => {
        if (isHydrating) return;

        const principal = parseFloat(principalInput.value) || 0;
        const interestRatePercent = parseFloat(interestInput.value) || 0;
        const terms = parseInt(termsInput.value) || 0;
        const start = startDateInput.value;
        const amortType = amortTypeSelect.value;

        if (principal <= 0 || terms <= 0 || !amortType) {
            clearSummaryAndSchedule();
            return;
        }

        const processingFeeValue = principal * 0.02;
        const insuranceFeeValue = ((principal + 1000) * 1.2) / terms;
        const notarialFeeValue = 400.00;

        feeProcessing.value = formatCurrency(processingFeeValue);
        feeInsurance.value = formatCurrency(insuranceFeeValue);
        feeNotarial.value = formatCurrency(notarialFeeValue);

        const netProceedsValue = principal - (processingFeeValue + insuranceFeeValue + notarialFeeValue);
        sumNetProceeds.textContent = formatCurrency(netProceedsValue);
        termsBadge.textContent = `${terms} Months`;

        scheduleArray = [];
        let totalInterestCalculated = 0;
        let totalRepaymentCalculated = 0;
        let amortPerPeriodValue = 0;

        const monthlyRate = (interestRatePercent / 100);
        let baseDate = start ? new Date(start) : new Date();

        if (amortType === 'Straight-line') {
            const monthlyPrincipal = principal / terms;
            const monthlyInterest = principal * monthlyRate;
            amortPerPeriodValue = monthlyPrincipal + monthlyInterest;
            let remainingBalance = principal;

            for (let i = 1; i <= terms; i++) {
                remainingBalance -= monthlyPrincipal;
                if (i === terms || remainingBalance < 0) remainingBalance = 0;
                baseDate.setMonth(baseDate.getMonth() + 1);
                
                scheduleArray.push({
                    period: i,
                    dueDate: baseDate.toISOString().split('T')[0],
                    amortization: amortPerPeriodValue,
                    principalComponent: monthlyPrincipal,
                    interestComponent: monthlyInterest,
                    remainingPrincipal: remainingBalance
                });
                totalInterestCalculated += monthlyInterest;
            }
            totalRepaymentCalculated = principal + totalInterestCalculated;

        } else if (amortType === 'Diminishing balance') {
            if (monthlyRate > 0) {
                amortPerPeriodValue = (principal * monthlyRate * Math.pow(1 + monthlyRate, terms)) / (Math.pow(1 + monthlyRate, terms) - 1);
            } else {
                amortPerPeriodValue = principal / terms;
            }
            let remainingBalance = principal;

            for (let i = 1; i <= terms; i++) {
                const currentPeriodInterest = remainingBalance * monthlyRate;
                let currentPeriodPrincipal = amortPerPeriodValue - currentPeriodInterest;
                remainingBalance -= currentPeriodPrincipal;

                if (i === terms || remainingBalance < 0) {
                    currentPeriodPrincipal += remainingBalance;
                    remainingBalance = 0;
                }
                baseDate.setMonth(baseDate.getMonth() + 1);

                scheduleArray.push({
                    period: i,
                    dueDate: baseDate.toISOString().split('T')[0],
                    amortization: currentPeriodPrincipal + currentPeriodInterest,
                    principalComponent: currentPeriodPrincipal,
                    interestComponent: currentPeriodInterest,
                    remainingPrincipal: remainingBalance
                });
                totalInterestCalculated += currentPeriodInterest;
            }
            totalRepaymentCalculated = principal + totalInterestCalculated;

        } else if (amortType === 'Manual') {
            const uniformPrincipal = principal / terms;
            const uniformInterest = principal * monthlyRate;
            amortPerPeriodValue = uniformPrincipal + uniformInterest;
            let remainingBalance = principal;

            for (let i = 1; i <= terms; i++) {
                remainingBalance -= uniformPrincipal;
                if (i === terms || remainingBalance < 0) remainingBalance = 0;
                baseDate.setMonth(baseDate.getMonth() + 1);

                scheduleArray.push({
                    period: i,
                    dueDate: baseDate.toISOString().split('T')[0],
                    amortization: uniformPrincipal + uniformInterest,
                    principalComponent: uniformPrincipal,
                    interestComponent: uniformInterest,
                    remainingPrincipal: remainingBalance
                });
                totalInterestCalculated += uniformInterest;
            }
            totalRepaymentCalculated = principal + totalInterestCalculated;
        }

        sumAmortization.textContent = formatCurrency(amortPerPeriodValue);
        sumInterest.textContent = formatCurrency(totalInterestCalculated);
        sumTotalPayment.textContent = formatCurrency(totalRepaymentCalculated);

        renderScheduleTable(amortType === 'Manual');
    };

    const renderScheduleTable = (isEditable = false) => {
        scheduleBody.innerHTML = '';
        if (scheduleArray.length === 0) {
            scheduleBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--t3);padding:30px;">Fill out parameters above to dynamically compute schedule data.</td></tr>`;
            return;
        }

        scheduleArray.forEach((row, index) => {
            const tr = document.createElement('tr');
            if (isEditable) {
                tr.innerHTML = `
                    <td>Month ${row.period}</td>
                    <td><input type="date" value="${row.dueDate}" class="editable-cell" data-index="${index}" data-field="dueDate" style="width:120px;"></td>
                    <td><input type="number" step="0.01" value="${parseFloat(row.amortization).toFixed(2)}" class="editable-cell" data-index="${index}" data-field="amortization"></td>
                    <td><input type="number" step="0.01" value="${parseFloat(row.principalComponent).toFixed(2)}" class="editable-cell" data-index="${index}" data-field="principalComponent"></td>
                    <td><input type="number" step="0.01" value="${parseFloat(row.interestComponent).toFixed(2)}" class="editable-cell" data-index="${index}" data-field="interestComponent"></td>
                    <td>${formatCurrency(row.remainingPrincipal)}</td>`;
            } else {
                tr.innerHTML = `
                    <td>Month ${row.period}</td>
                    <td style="color:var(--t3)">${row.dueDate}</td>
                    <td style="font-weight:600; color:var(--gold)">${formatCurrency(row.amortization)}</td>
                    <td>${formatCurrency(row.principalComponent)}</td>
                    <td>${formatCurrency(row.interestComponent)}</td>
                    <td style="font-weight:500">${formatCurrency(row.remainingPrincipal)}</td>`;
            }
            scheduleBody.appendChild(tr);
        });

        if (isEditable) bindManualMutationListeners();
    };

    const bindManualMutationListeners = () => {
        const cells = scheduleBody.querySelectorAll('.editable-cell');
        cells.forEach(cell => {
            cell.addEventListener('input', (e) => {
                const idx = parseInt(e.target.getAttribute('data-index'));
                const field = e.target.getAttribute('data-field');
                const val = e.target.value;

                if (field === 'dueDate') {
                    scheduleArray[idx].dueDate = val;
                } else {
                    scheduleArray[idx][field] = parseFloat(val) || 0;
                    if (field === 'principalComponent' || field === 'interestComponent') {
                        scheduleArray[idx].amortization = scheduleArray[idx].principalComponent + scheduleArray[idx].interestComponent;
                        const rowAmortInput = scheduleBody.querySelector(`.editable-cell[data-index="${idx}"][data-field="amortization"]`);
                        if (rowAmortInput) rowAmortInput.value = scheduleArray[idx].amortization.toFixed(2);
                    }
                }
                updateSummaryFromManualState();
            });
        });
    };

    const updateSummaryFromManualState = () => {
        let runningInterest = 0, runningTotal = 0;
        scheduleArray.forEach(row => {
            runningInterest += row.interestComponent;
            runningTotal += row.amortization;
        });
        sumInterest.textContent = formatCurrency(runningInterest);
        sumTotalPayment.textContent = formatCurrency(runningTotal);
    };

    const clearSummaryAndSchedule = () => {
        feeProcessing.value = '₱0.00'; feeInsurance.value = '₱0.00';
        sumAmortization.textContent = '₱0.00'; sumNetProceeds.textContent = '₱0.00';
        sumInterest.textContent = '₱0.00'; sumTotalPayment.textContent = '₱0.00';
        termsBadge.textContent = '0 Months'; scheduleArray = [];
        renderScheduleTable();
    };

   // ── INTERCEPT & ASYNC PERSISTENCE CALL ───────────────────────────
    saveButton.addEventListener('click', async () => {
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        if (scheduleArray.length === 0) {
            alert('Please generate a valid amortization schedule before saving.');
            return;
        }

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
                tct_no: rpTct.value || null,
                tax_dec_no: rpTax.value || null,
                rp_status: rpPayments.value || null
            },
            schedule: scheduleArray
        };

        saveButton.disabled = true;
        saveButton.textContent = 'Saving Configurations...';

        try {
            const response = await fetch(`amortization.php?action=save_loan`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();

            if (result.success) {
                alert(result.message);
            } else {
                alert('Database Error: ' + result.error);
            }
        } catch (err) {
            console.error(err);
            alert('Network Connection Error occurred while saving configuration.');
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'Save Loan Configuration';
        }
    }); // <--- ETO YUNG NAWALA KANINA! Isinasara nito ang saveButton click listener.


    // ── INITIALIZATION HYDRATION MATRIX (ON REFRESH) ─────────────────
    const hydrateSavedData = () => {
        if (!SAVED_LOAN_DATA || Object.keys(SAVED_LOAN_DATA).length === 0) return;

        isHydrating = true;

        // Repopulate Core Input Elements
        loanTypeSelect.value = SAVED_LOAN_DATA.loan_type;
        collateralSelect.value = SAVED_LOAN_DATA.collateral;
        soa_status.value = SAVED_LOAN_DATA.soa_status;
        amortTypeSelect.value = SAVED_LOAN_DATA.amort_type;
        principalInput.value = SAVED_LOAN_DATA.principal_amount;
        interestInput.value = SAVED_LOAN_DATA.interest_rate;
        termsInput.value = SAVED_LOAN_DATA.terms;
        startDateInput.value = SAVED_LOAN_DATA.start_date;

        // Repopulate Conditional Real Property Options
        handleCollateralToggles();
        if (SAVED_LOAN_DATA.collateral === "Real Property") {
            rpTct.value = SAVED_LOAN_DATA.tct_no || '';
            rpTax.value = SAVED_LOAN_DATA.tax_dec_no || '';
            rpPayments.value = SAVED_LOAN_DATA.rp_status || '';
        }

        // Calculate static text fees fields
        const principal = parseFloat(SAVED_LOAN_DATA.principal_amount);
        const terms = parseInt(SAVED_LOAN_DATA.terms);
        feeProcessing.value = formatCurrency(principal * 0.02);
        feeInsurance.value = formatCurrency(((principal + 1000) * 1.2) / terms);
        feeNotarial.value = formatCurrency(400.00);
        sumNetProceeds.textContent = formatCurrency(principal - ((principal * 0.02) + (((principal + 1000) * 1.2) / terms) + 400.00));
        termsBadge.textContent = `${terms} Months`;

        // Map and Load saved breakdown array rows directly from DB context safely
        if (SAVED_SCHEDULE_DATA && SAVED_SCHEDULE_DATA.length > 0) {
            scheduleArray = SAVED_SCHEDULE_DATA.map(row => ({
                period: parseInt(row.period),
                dueDate: row.due_date,
                amortization: parseFloat(row.amortization),
                principalComponent: parseFloat(row.principal_component),
                interestComponent: parseFloat(row.interest_component),
                remainingPrincipal: parseFloat(row.remaining_principal)
            }));

            // Re-render display states and recompute accurate metrics
            let totalInterest = 0, totalRepay = 0;
            scheduleArray.forEach(r => {
                totalInterest += r.interestComponent;
                totalRepay += r.amortization;
            });

            sumAmortization.textContent = formatCurrency(scheduleArray[0]?.amortization || 0);
            sumInterest.textContent = formatCurrency(totalInterest);
            sumTotalPayment.textContent = formatCurrency(totalRepay);

            renderScheduleTable(SAVED_LOAN_DATA.amort_type === 'Manual');
        }

        isHydrating = false;
    };

    // --- Register Operational Event Listeners ---
    const reactiveSelectors = [loanTypeSelect, collateralSelect, amortTypeSelect, principalInput, termsInput, startDateInput];
    reactiveSelectors.forEach(el => {
        if (el) {
            el.addEventListener('input', calculateLoanPipeline);
            el.addEventListener('change', calculateLoanPipeline);
        }
    });

    loanTypeSelect.addEventListener('change', handleLoanTypeTransformation);
    collateralSelect.addEventListener('change', handleCollateralToggles);

    // Run hydration sequence automatically on initialization
    hydrateSavedData();
});