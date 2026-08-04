// SIPANDA PTM - Admin: upload Excel via AJAX
const form = document.getElementById('uploadForm');
const resultBox = document.getElementById('uploadResult');

form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button');
    btn.disabled = true;
    btn.textContent = 'Memproses...';
    resultBox.innerHTML = '';

    try {
        const formData = new FormData(form);
        const res = await fetch('upload_process.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            resultBox.innerHTML = `<div class="alert-success">${data.message}</div>`;
            if (data.errors && data.errors.length) {
                resultBox.innerHTML += `<div class="alert-warning">Baris dilewati:<br>${data.errors.join('<br>')}</div>`;
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            resultBox.innerHTML = `<div class="alert-error">${data.message}</div>`;
        }
    } catch (err) {
        resultBox.innerHTML = `<div class="alert-error">Terjadi kesalahan: ${err.message}</div>`;
    } finally {
        btn.disabled = false;
        btn.textContent = 'Upload & Proses';
    }
});
