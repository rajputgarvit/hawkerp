document.addEventListener('DOMContentLoaded', function () {
    // Sticky Navbar
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Pricing Toggle
    const toggle = document.getElementById('billingToggle');
    const monthlyLabel = document.getElementById('monthlyLabel');
    const annualLabel = document.getElementById('annualLabel');
    const amounts = document.querySelectorAll('.amount');
    let isAnnual = false;

    if (toggle) {
        toggle.addEventListener('click', () => {
            isAnnual = !isAnnual;
            toggle.classList.toggle('active');

            if (isAnnual) {
                monthlyLabel.classList.remove('active');
                annualLabel.classList.add('active');
            } else {
                monthlyLabel.classList.add('active');
                annualLabel.classList.remove('active');
            }

            // Update prices with animation
            amounts.forEach(amount => {
                amount.style.opacity = '0';
                setTimeout(() => {
                    amount.textContent = isAnnual
                        ? amount.dataset.annual.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                        : amount.dataset.monthly.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    amount.style.opacity = '1';
                }, 200);
            });
        });
    }

    // Smooth Scroll for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Intersection Observer for Scroll Animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Animate elements on scroll
    const animatedElements = document.querySelectorAll('.bento-card, .price-card, .section-header');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });
});
