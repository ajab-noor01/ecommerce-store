document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wishlist-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            const icon = this.querySelector('i');
            const card = this.closest('[data-wishlist-card]');
            const removable = this.dataset.removable === '1';

            this.disabled = true;

            fetch('wishlist-toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + encodeURIComponent(id)
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status === 'added') {
                        btn.classList.add('active');
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                    } else if (data.status === 'removed') {
                        btn.classList.remove('active');
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');

                        // On the wishlist page itself, remove the card immediately.
                        if (removable && card) {
                            card.style.transition = 'opacity .25s ease';
                            card.style.opacity = '0';
                            setTimeout(function () {
                                card.remove();
                                const grid = document.getElementById('wishlistGrid');
                                const emptyState = document.getElementById('wishlistEmpty');
                                if (grid && grid.children.length === 0 && emptyState) {
                                    emptyState.style.display = 'block';
                                }
                            }, 250);
                        }
                    }

                    document.querySelectorAll('.wishlist-count').forEach(function (el) {
                        el.textContent = data.count;
                    });
                })
                .finally(function () {
                    btn.disabled = false;
                });
        });
    });
});