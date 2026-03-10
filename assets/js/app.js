import { initDropdowns } from './dropdown.js';
import { initThemeToggle } from './theme.js';
import { confirmLogout } from './signout.js';
import { initTableSearch } from './search.js';
import { initSettingsForm, initImagePreview } from './settings.js';
import { showSignature } from './signature.js';

window.confirmLogout = confirmLogout;

document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
    initThemeToggle();
    initTableSearch(); // Initialize it here
    initSettingsForm();
    initImagePreview();
    showSignature();
});