document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const closeBtn = document.querySelector('.mv-btn--close');
    let isDirty = false;
    let isSubmitting = false;

    // 1. Success Toast Logic
    const toastTrigger = document.getElementById('toast-trigger');
    if (toastTrigger) {
        showToast(toastTrigger.dataset.message, 'success');
    }

    // 2. Track Changes
    form.addEventListener('input', () => {
        isDirty = true;
    });

    // 3. Handle Form Submission
    form.addEventListener('submit', () => {
        isSubmitting = true;
    });

    // 4. Close Button & Navigation Logic
    closeBtn.addEventListener('click', (e) => {
        if (isDirty) {
            const confirmLeave = confirm("You have unsaved changes. Are you sure you want to leave?");
            if (!confirmLeave) {
                e.preventDefault();
                return;
            }
        }
        window.location.href = "index.php?page=dashboard";
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