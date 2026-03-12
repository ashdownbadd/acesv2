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
        isSubmitting = true; // Prevents the warning when the user actually saves
    });

    // Handle Close Button specifically
    closeBtn.addEventListener('click', (e) => {
        if (isDirty) {
            // If the user confirms, we manually navigate
            if (confirm("You have unsaved changes. Are you sure you want to leave?")) {
                isDirty = false; // Disable dirty state so 'beforeunload' doesn't fire
                window.location.href = "index.php?page=dashboard";
            }
            // If they cancel, we do nothing and they stay on the page
        } else {
            window.location.href = "index.php?page=dashboard";
        }
    });

    // Browser Refresh/Tab Close Protection
    window.addEventListener('beforeunload', (e) => {
        if (isDirty && !isSubmitting) {
            e.preventDefault();
            e.returnValue = ''; // This triggers the standard browser dialog
        }
    });

    // 5. Browser Refresh/Tab Close Protection
    window.addEventListener('beforeunload', (e) => {
        if (isDirty && !isSubmitting) {
            e.preventDefault();
            e.returnValue = ''; // Triggers the standard browser warning
        }
    });

    // Toast UI Function
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.textContent = message;

        // Style the toast dynamically or add to your CSS
        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '24px',
            right: '24px',
            background: type === 'success' ? '#2ecc71' : '#e74c3c',
            color: '#fff',
            padding: '12px 24px',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            zIndex: '9999',
            fontFamily: 'sans-serif',
            animation: 'slideUp 0.3s ease-out'
        });

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = '0.5s';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }
});

// assets/js/member_view.js

export function initBalanceFormatter() {
    const balanceInput = document.querySelector('.mv-card__balance');

    if (!balanceInput) return;

    balanceInput.addEventListener('input', (e) => {
        // 1. Get current value and remove everything except digits and decimal point
        let value = e.target.value.replace(/[^0-9.]/g, '');

        // 2. Split into integer and decimal parts
        let parts = value.split('.');

        // 3. Add commas to the integer part
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

        // 4. Rejoin and limit to 2 decimal places if they exist
        e.target.value = parts.length > 1 ? parts[0] + '.' + parts[1].substring(0, 2) : parts[0];
    });
}

// Initialize when the DOM is ready
document.addEventListener('DOMContentLoaded', initBalanceFormatter);