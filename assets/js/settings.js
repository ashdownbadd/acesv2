// /acesv2/assets/js/settings.js

// Simple toast notification helper
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'c-toast';
    toast.innerText = message;
    document.body.appendChild(toast);

    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

export function initSettingsForm() {
    const form = document.getElementById('settings-form');
    const saveBtn = document.getElementById('save-btn');
    if (!form) return;

    // Check for success status in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        showToast('Profile updated successfully!');
    }

    const inputs = form.querySelectorAll('input');
    const originalValues = {};
    let isDirty = false;

    inputs.forEach(input => {
        if (input.type !== 'file') {
            originalValues[input.name] = input.value;
        }
    });

    const checkChanges = () => {
        let hasChanges = false;
        
        inputs.forEach(input => {
            if (input.type !== 'file' && input.value !== originalValues[input.name]) {
                hasChanges = true;
            }
        });

        const fileInput = document.getElementById('avatar-upload');
        if (fileInput && fileInput.files.length > 0) {
            hasChanges = true;
        }

        isDirty = hasChanges;

        if (hasChanges) {
            saveBtn.classList.remove('is-disabled');
            saveBtn.disabled = false;
        } else {
            saveBtn.classList.add('is-disabled');
            saveBtn.disabled = true;
        }
    };

    window.addEventListener('beforeunload', (e) => {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    form.addEventListener('submit', (e) => {
        if (!confirm("Are you sure you want to save these changes?")) {
            e.preventDefault();
        } else {
            isDirty = false; 
        }
    });

    form.addEventListener('input', checkChanges);
}

export function initImagePreview() {
    const avatarInput = document.getElementById('avatar-upload');
    const avatarImg = document.querySelector('.c-avatar-img');
    const placeholder = document.querySelector('.c-avatar-placeholder');
    const saveBtn = document.getElementById('save-btn');

    if (avatarInput) {
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    avatarImg.src = event.target.result;
                    avatarImg.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                    saveBtn.classList.remove('is-disabled');
                    saveBtn.disabled = false;
                };
                reader.readAsDataURL(file);
            }
        });
    }
}