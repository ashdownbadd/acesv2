document.addEventListener('input', function (e) {

    if (e.target.matches('.principal-input, .interest-input, .term-input')) {

        const P = parseFloat(document.querySelector('.principal-input').value) || 0;
        const R = (parseFloat(document.querySelector('.interest-input').value) || 0) / 100 / 12;
        const N = parseInt(document.querySelector('.term-input').value) || 0;

        if (P > 0 && R > 0 && N > 0) {

            const x = Math.pow(1 + R, N);
            const monthly = (P * x * R) / (x - 1);

            document.querySelector('.amort-display').value = monthly.toFixed(2);

            generateSchedule(P, R, N, monthly);

        }

    }

});

function generateSchedule(principal, monthlyRate, months, monthlyPayment) {

    const tbody = document.querySelector('#scheduleTable tbody');

    tbody.innerHTML = '';

    let balance = principal;

    for (let i = 1; i <= months; i++) {

        let interest = balance * monthlyRate;
        let principalPaid = monthlyPayment - interest;

        balance -= principalPaid;

        const row = `
<tr>
<td>${i}</td>
<td>₱${principalPaid.toFixed(2)}</td>
<td>₱${interest.toFixed(2)}</td>
<td>₱${monthlyPayment.toFixed(2)}</td>
<td>₱${Math.abs(balance).toFixed(2)}</td>
</tr>
`;

        tbody.innerHTML += row;

    }

}