// assets/js/app.js
import { initDropdowns } from './dropdown.js';
import { initThemeToggle } from './theme.js';
import { confirmLogout } from './signout.js';
import { initTableSearch } from './search.js';

window.confirmLogout = confirmLogout;

document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
    initThemeToggle();
    initTableSearch();
});