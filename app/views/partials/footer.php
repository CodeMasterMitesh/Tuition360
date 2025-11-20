<footer class="footer bg-light border-top">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <span class="text-muted d-block w-100 text-center">&copy; <?= date('Y') ?> Tuition360. All rights reserved.</span>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="../../../public/assets/js/export-print.js"></script>
<script src="../../../public/assets/js/crud-helpers.js"></script>
<script>
    // Add active class to current page in navbar
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.href;
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

        navLinks.forEach(link => {
            if (link.href === currentPage) {
                link.classList.add('active');
            }
        });

        // Add animation to cards on scroll
        const cards = document.querySelectorAll('.card');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1
        });

        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    });
</script>