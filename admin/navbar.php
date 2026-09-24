<?php
// admin/navbar.php
$admin = current_admin();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-nav">
    <div class="admin-nav-inner">
        <a href="index.php" class="admin-brand">
            <img src="../embed/logo-barantin.png" alt="Logo Barantin">
            <div class="admin-brand-text">
                <strong>Badan Karantina Indonesia</strong>
                <span>BKHIT Kalimantan Selatan</span>
            </div>
        </a>

        <div class="admin-nav-links">
            <a href="index.php" class="admin-nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Dashboard</a>

            <div class="admin-nav-dropdown">
                <span class="admin-nav-link <?= in_array($currentPage, ['upload_hewan.php', 'upload_ikan.php', 'upload_tumbuhan.php']) ? 'active' : '' ?>">Upload Excel</span>
                <div class="admin-nav-dropdown-panel">
                    <div class="admin-nav-dropdown-label">Kategori Komoditas</div>
                    <a href="upload_hewan.php" class="admin-nav-dropdown-item"><span class="dot dot-hewan"></span> Hewan (KH)</a>
                    <a href="upload_ikan.php" class="admin-nav-dropdown-item"><span class="dot dot-ikan"></span> Ikan (KI)</a>
                    <a href="upload_tumbuhan.php" class="admin-nav-dropdown-item"><span class="dot dot-tumbuhan"></span> Tumbuhan (KT)</a>
                </div>
            </div>
            <a href="kelola_data.php" class="admin-nav-link <?= $currentPage === 'kelola_data.php' ? 'active' : '' ?>">Kelola & Publish Data</a>
            <?php if (is_admin_role()): ?>
                <a href="users.php" class="admin-nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">Kelola Pengguna</a>
            <?php endif; ?>
            <a href="../index.php" target="_self" class="admin-nav-link accent">Lihat Board Publik</a>
        </div>

        <div class="admin-nav-user">
            <span>Masuk sebagai <strong><?= htmlspecialchars($admin['nama']) ?></strong></span>
            <form method="POST" action="logout.php" data-confirm="Yakin ingin logout?" style="display: inline;">
                <?= csrf_field() ?>
                <button type="submit" class="admin-logout">Logout</button>
            </form>
        </div>
    </div>

    <div class="admin-subnav">
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="upload_hewan.php" class="<?= $currentPage === 'upload_hewan.php' ? 'active' : '' ?>">Hewan</a>
        <a href="upload_ikan.php" class="<?= $currentPage === 'upload_ikan.php' ? 'active' : '' ?>">Ikan</a>
        <a href="upload_tumbuhan.php" class="<?= $currentPage === 'upload_tumbuhan.php' ? 'active' : '' ?>">Tumbuhan</a>
        <a href="kelola_data.php" class="<?= $currentPage === 'kelola_data.php' ? 'active' : '' ?>">Kelola Data</a>
    </div>
</nav>

<div class="admin-confirm-backdrop" id="adminConfirmModal" hidden>
    <div class="admin-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle" aria-describedby="adminConfirmMessage">
        <div class="admin-confirm-icon" aria-hidden="true">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.8 17a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
        </div>
        <div class="admin-confirm-content">
            <h2 id="adminConfirmTitle">Konfirmasi</h2>
            <p id="adminConfirmMessage"></p>
        </div>
        <div class="admin-confirm-actions">
            <button type="button" class="btn btn-secondary" id="adminConfirmCancel">Batal</button>
            <button type="button" class="btn btn-primary" id="adminConfirmAccept">Lanjutkan</button>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('adminConfirmModal');
    const message = document.getElementById('adminConfirmMessage');
    const cancelButton = document.getElementById('adminConfirmCancel');
    const acceptButton = document.getElementById('adminConfirmAccept');
    let pendingAction = null;

    function closeModal() {
        modal.hidden = true;
        pendingAction = null;
    }

    function openModal(text, action, tone) {
        message.textContent = text;
        acceptButton.classList.toggle('btn-danger', tone === 'danger');
        acceptButton.classList.toggle('btn-primary', tone !== 'danger');
        pendingAction = action;
        modal.hidden = false;
        acceptButton.focus();
    }

    document.addEventListener('click', function(event) {
        const trigger = event.target.closest('[data-confirm]');
        if (!trigger) return;

        // Form confirmation is handled by the submit listener below.
        if (trigger.matches('form')) return;

        event.preventDefault();
        openModal(trigger.dataset.confirm, function() {
            if (trigger.matches('a')) {
                window.location.href = trigger.href;
            } else if (trigger.matches('form')) {
                trigger.dataset.confirmed = 'true';
                trigger.requestSubmit();
            } else if (trigger.form) {
                trigger.form.requestSubmit(trigger);
            }
        }, trigger.dataset.confirmTone || 'primary');
    });

    document.addEventListener('submit', function(event) {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        openModal(form.dataset.confirm, function() {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }, form.dataset.confirmTone || 'primary');
    });

    cancelButton.addEventListener('click', closeModal);
    acceptButton.addEventListener('click', function() {
        const action = pendingAction;
        closeModal();
        if (action) action();
    });

    modal.addEventListener('click', function(event) {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', function(event) {
        if (!modal.hidden && event.key === 'Escape') closeModal();
    });
})();
</script>

