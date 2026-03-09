import { initDropdowns } from './dropdown.js';
import { initThemeToggle } from './theme.js';
import { confirmLogout } from './signout.js';
import { initTableSearch } from './search.js';
import { initSettingsForm, initImagePreview } from './settings.js';

// Expose to window for inline onclick handlers if needed
window.confirmLogout = confirmLogout;

document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
    initThemeToggle();
    initTableSearch();
    // Add initialization for settings if required
    // initSettingsForm(); 
});