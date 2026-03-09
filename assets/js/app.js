// assets/js/app.js
import { initDropdowns } from './dropdown.js';
import { initThemeToggle } from './theme.js';
import { confirmLogout } from './signout.js';
import { initTableSearch } from './search.js';
import { initSettingsForm, initImagePreview } from './settings.js';

window.confirmLogout = confirmLogout;

document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
    initThemeToggle();
    initTableSearch();
});

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('settings-form')) {
        initSettingsForm();
        initImagePreview();
    }
});