// assets/js/search.js
export function initTableSearch() {
    const searchInput = document.getElementById('tbl-search');
    const tableRows = document.querySelectorAll('.c-table__row');

    if (!searchInput) return;

    searchInput.addEventListener('keyup', () => {
        const query = searchInput.value.toLowerCase();

        tableRows.forEach(row => {
            // Get all text content in the row and convert to lowercase
            const text = row.textContent.toLowerCase();
            
            // Toggle display: show if it contains the query, hide if not
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}