import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const reveal = () => {
    const nodes = document.querySelectorAll('.lp-reveal');
    if (!nodes.length) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        nodes.forEach((node) => node.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );

    nodes.forEach((node) => observer.observe(node));
};

document.addEventListener('DOMContentLoaded', reveal);
