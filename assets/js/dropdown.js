export function initDropdowns() {
    const userChip = document.getElementById('user-chip');
    const chipDrop = document.getElementById('chip-drop');

    if (userChip && chipDrop) {
        userChip.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = chipDrop.style.display === 'block';
            chipDrop.style.display = isVisible ? 'none' : 'block';
            userChip.classList.toggle('c-chip--active', !isVisible);
        });

        document.addEventListener('click', () => {
            chipDrop.style.display = 'none';
            userChip.classList.remove('c-chip--active');
        });
    }
}