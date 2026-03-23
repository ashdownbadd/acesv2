<div class="c-page">
    <div class="c-row">
        <div class="c-section-label">Loan parameters</div>
        <div class="c-grid-4">
            <div class="c-card">
                <div class="c-card__label">Loan type</div>
                <select id="loan_type" class="c-card__input">
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
            <div class="c-card">
                <div class="c-card__label">Collateral</div>
                <select id="collateral" class="c-card__input">
                    <option value="" disabled selected>— Select —</option>
                    <option>Post-Dated Check</option>
                    <option>Real Property</option>
                    <option>Chattels / Movable Assets</option>
                </select>
            </div>
            <div class="c-card">
                <div class="c-card__label">SOA status</div>
                <select id="soa" class="c-card__input">
                    <option value="" disabled selected>— Select —</option>
                    <option>Updated</option>
                    <option>Pending</option>
                    <option>Overdue</option>
                </select>
            </div>
            <div class="c-card">
                <div class="c-card__label">Amortization type</div>
                <select id="amort_type" class="c-card__input">
                    <option value="" disabled selected>— Select —</option>
                    <option value="straight">Straight-line</option>
                    <option value="diminishing">Diminishing balance</option>
                    <option value="manual">Manual</option>
                </select>
            </div>
        </div>
    </div>

    <div class="c-row">
        <div class="c-section-label">Figures</div>
        <div class="c-grid-5">
            <div class="c-card">
                <div class="c-card__label">Principal amount (₱)</div>
                <input type="number" id="principal" class="c-card__input" placeholder="0.00">
            </div>
            <div class="c-card">
                <div class="c-card__label">Interest rate (%)</div>
                <input type="number" id="interest" class="c-card__input" placeholder="0.00">
            </div>
            <div class="c-card">
                <div class="c-card__label">Terms (months)</div>
                <input type="number" id="months" class="c-card__input" placeholder="0">
            </div>
            <div class="c-card">
                <div class="c-card__label">Start date</div>
                <input type="date" id="start_date" class="c-card__input">
            </div>
            <div class="c-card c-card--result">
                <div class="c-card__label">Monthly amortization</div>
                <div class="c-result-value" id="monthly_display">₱ —</div>
                <div class="c-result-sub" id="result_sub">Updates automatically</div>
            </div>
        </div>
    </div>

    <div class="c-table-card">
        <div class="c-table-card__head">
            <span class="c-table-card__title">Amortization schedule</span>
            <div class="c-table-card__actions">
                <button class="c-btn c-btn--export" id="export_btn" onclick="exportCSV()" disabled>Export CSV</button>
                <button class="c-btn c-btn--save" id="save_btn" onclick="saveData()">Save</button>
            </div>
        </div>
        <div class="c-tbl-wrap">
            <table class="c-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Opening Balance</th>
                        <th>Amortization</th>
                        <th>Interest</th>
                        <th>Payment</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody id="sched_body">
                    <tr class="c-empty-row">
                        <td colspan="6">Enter loan details above to generate the schedule</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="module" src="/acesv2/assets/js/amortization.js"></script>