// assets/js/search.js
export function initTableSearch() {
    const searchInput = document.getElementById('tbl-search');
    const tableRows = document.querySelectorAll('.c-table__row');

    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();

        tableRows.forEach(row => {
            // Target only the elements containing the name and ID
            const nameElement = row.querySelector('.c-member-info__name');
            const idElement = row.querySelector('.c-member-info__id');
            
            // Combine their text for searching
            const nameText = nameElement ? nameElement.textContent.toLowerCase() : '';
            const idText = idElement ? idElement.textContent.toLowerCase() : '';
            
            // Show row if query is in name OR in ID, otherwise hide
            const isMatch = nameText.includes(query) || idText.includes(query);
            row.style.display = isMatch ? '' : 'none';
        });
    });
}