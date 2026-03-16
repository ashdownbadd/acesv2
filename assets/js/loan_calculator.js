document.addEventListener('input', function (e) {
    if (e.target.matches('.principal-input, .interest-input, .term-input')) {
        const P = parseFloat(document.querySelector('.principal-input').value) || 0;
        // Divide by 100 here to get 0.02 from 2
        const rate = (parseFloat(document.querySelector('.interest-input').value) || 0) / 100;
        const N = parseInt(document.querySelector('.term-input').value) || 0;

        if (P > 0 && N > 0) {
            // 1. Fixed Interest = Principal * Rate (e.g., 5000 * 0.02 = 100)
            const monthlyInterest = P * rate; 
            
            // 2. Monthly Principal = Principal / Terms
            const monthlyPrincipal = P / N;
            
            // 3. Total Monthly Payment
            const monthlyPayment = monthlyPrincipal + monthlyInterest;

            document.querySelector('.amort-display').value = monthlyPayment.toFixed(2);
            
            generateFlatSchedule(P, N, monthlyPayment, monthlyInterest, monthlyPrincipal);
        }
    }
});

function generateFlatSchedule(principal, months, monthlyPayment, monthlyInterest, monthlyPrincipal) {
    const tbody = document.querySelector('#scheduleTable tbody');
    tbody.innerHTML = '';

    let balance = principal;

    for (let i = 1; i <= months; i++) {
        balance -= monthlyPrincipal;

        const row = `
            <tr>
                <td>${i}</td>
                <td>₱${monthlyPrincipal.toFixed(2)}</td>
                <td>₱${monthlyInterest.toFixed(2)}</td>
                <td>₱${monthlyPayment.toFixed(2)}</td>
                <td>₱${Math.max(0, balance).toFixed(2)}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    }
}