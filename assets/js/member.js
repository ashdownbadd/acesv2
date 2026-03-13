document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const saveBtn = document.querySelector('.mv-btn--save');
    const closeBtn = document.querySelector('.mv-btn--close');
    let isDirty = false;
    let isSubmitting = false;

    saveBtn.disabled = true;

    form.addEventListener('input', () => {
        isDirty = true;
        saveBtn.disabled = false;
    });

    form.addEventListener('submit', () => {
        isSubmitting = true;
    });

    closeBtn.addEventListener('click', (e) => {
        if (isDirty) {
            if (confirm("You have unsaved changes. Are you sure you want to leave?")) {
                isDirty = false;
                window.location.href = "index.php?page=dashboard";
            }
        } else {
            window.location.href = "index.php?page=dashboard";
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (isDirty && !isSubmitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

export function initBalanceFormatter() {
    const balanceInput = document.querySelector('.mv-card__balance');
    if (!balanceInput) return;

    balanceInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/[^0-9.]/g, '');
        let parts = value.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        e.target.value = parts.length > 1 ? parts[0] + '.' + parts[1].substring(0, 2) : parts[0];
    });
}

document.addEventListener('DOMContentLoaded', initBalanceFormatter);

function calculateAmortization() {
    const P = parseFloat(document.querySelector('.principal-input').value) || 0;
    const interestRaw = document.querySelector('.interest-input').value.replace('%', '').trim();
    const R = (parseFloat(interestRaw) || 0) / 100;
    const N = parseInt(document.querySelector('.term-input').value) || 0;

    if (P > 0 && N > 0) {
        const monthlyPrincipal = P / N;
        const monthlyInterest = (P * R) / 12;
        const monthlyTotal = monthlyPrincipal + monthlyInterest;

        document.querySelector('.amort-display').value = monthlyTotal.toFixed(2);
        generateSchedule(P, monthlyPrincipal, monthlyInterest, N);
    }
}

function generateSchedule(principal, monthlyPrincipal, monthlyInterest, months) {
    const tbody = document.querySelector('#scheduleTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    let remainingBalance = principal;

    for (let i = 1; i <= months; i++) {
        const isLastMonth = (i === months);
        let pPaid = isLastMonth ? remainingBalance : monthlyPrincipal;
        remainingBalance -= pPaid;

        const row = `
        <tr>
            <td>${i}</td>
            <td>₱${pPaid.toFixed(2)}</td>
            <td>₱${monthlyInterest.toFixed(2)}</td>
            <td>₱${(pPaid + monthlyInterest).toFixed(2)}</td>
            <td>₱${Math.max(0, remainingBalance).toFixed(2)}</td>
        </tr>`;
        tbody.innerHTML += row;
    }
}

document.addEventListener('input', (e) => {
    if (e.target.matches('.principal-input, .interest-input, .term-input')) {
        calculateAmortization();
    }
});

document.addEventListener('change', (e) => {
    if (e.target.matches('.loan-type')) {
        const loanType = e.target.value;
        const interestInput = document.querySelector('.interest-input');
        if (!interestInput) return;

        let interestValue = (loanType === 'Micro-Finance Loan') ? 5 : 2;
        interestInput.value = interestValue.toFixed(2) + " %";
        calculateAmortization();
    }
});

document.querySelector('.interest-input')?.addEventListener('blur', function () {
    let val = this.value.replace('%', '').trim();
    if (!isNaN(val) && val !== '') {
        this.value = parseFloat(val).toFixed(2) + " %";
    }
});