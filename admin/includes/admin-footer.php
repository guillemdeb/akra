<!-- admin/includes/admin-footer.php -->
<script>
// Sidebar toggle mobile
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebar = document.getElementById('sidebar');
if (sidebarToggle) {
    sidebarToggle.style.display = 'flex';
    sidebarToggle.addEventListener('click', () => sidebar?.classList.toggle('open'));
}
// Click outside sidebar
document.addEventListener('click', e => {
    if (window.innerWidth <= 1024 && sidebar && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
        sidebar.classList.remove('open');
    }
});
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
