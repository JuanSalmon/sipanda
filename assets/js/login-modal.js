// Floating login card: opens over the dashboard instead of navigating to admin/login.php
(function () {
    const backdrop = document.getElementById('loginModalBackdrop');
    const closeBtn = document.getElementById('loginModalClose');
    const form = document.getElementById('loginModalForm');
    const errorBox = document.getElementById('loginModalError');
    const submitBtn = document.getElementById('loginModalSubmit');
    if (!backdrop || !form) return;

    function openModal(e) {
        if (e) e.preventDefault();
        errorBox.hidden = true;
        backdrop.classList.add('is-open');
        document.body.classList.add('login-modal-open');
        setTimeout(() => document.getElementById('loginUsername')?.focus(), 50);
    }

    function closeModal() {
        backdrop.classList.remove('is-open');
        document.body.classList.remove('login-modal-open');
        form.reset();
        errorBox.hidden = true;
    }

    document.querySelectorAll('[data-open-login]').forEach((el) => {
        el.addEventListener('click', openModal);
    });

    closeBtn?.addEventListener('click', closeModal);
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && backdrop.classList.contains('is-open')) closeModal();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorBox.hidden = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Memproses...';

        try {
            const res = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: document.getElementById('loginUsername').value,
                    password: document.getElementById('loginPassword').value,
                }),
            });
            const data = await res.json();
            if (data.ok) {
                window.location.href = data.redirect || 'admin/dashboard.php';
            } else {
                errorBox.textContent = data.error || 'Login gagal.';
                errorBox.hidden = false;
            }
        } catch (err) {
            errorBox.textContent = 'Terjadi kesalahan jaringan. Coba lagi.';
            errorBox.hidden = false;
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Masuk';
        }
    });
})();