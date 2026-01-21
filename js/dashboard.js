/**
 * Dashboard - AJAX Functions
 * Product Rejection & Review Deletion
 */

// Notification helper
function showNotification(message, type = 'success') {
    const noti = document.getElementById('notification');
    noti.textContent = message;
    noti.className = type;
    noti.style.display = 'block';
    setTimeout(() => { noti.style.display = 'none'; }, 4500);
}

// Product Reject - AJAX
document.querySelectorAll('.btn-reject').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (!confirm('Reject & permanently delete this product?')) return;

        const productId = this.getAttribute('data-product-id');
        const row = this.closest('tr');

        try {
            const response = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=reject_product&product_id=${encodeURIComponent(productId)}`
            });

            const data = await response.json();

            if (data.success) {
                showNotification(data.message, 'success');
                row.remove();
            } else {
                showNotification(data.message || 'Error occurred', 'error');
            }
        } catch (err) {
            showNotification('Network error: ' + err.message, 'error');
        }
    });
});

// Review Delete - AJAX
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', async function() {
        const from = this.getAttribute('data-from');
        if (!confirm(`Delete review/message from ${from}?`)) return;

        const row = this.closest('tr');

        try {
            const response = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_review&rev_from=${encodeURIComponent(from)}`
            });

            const data = await response.json();

            if (data.success) {
                showNotification(data.message, 'success');
                row.remove();
            } else {
                showNotification(data.message || 'Error occurred', 'error');
            }
        } catch (err) {
            showNotification('Network error: ' + err.message, 'error');
        }
    });
});
