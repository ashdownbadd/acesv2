export function confirmLogout() {
    const confirmed = confirm("Are you sure you want to sign out?");
    if (confirmed) {
        window.location.href = 'index.php?action=logout';
    }
}