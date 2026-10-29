/**
 * /services page: fixed "GET QUOTE" bottom CTA bar.
 *
 * - Smooth-scrolls to the on-page #quote section when clicked (prevents
 *   the default hash jump + history entry).
 * - Hides the bar once the quote section intersects the viewport so it
 *   never covers the quote form itself or the footer below it; shows
 *   the bar again when the user scrolls back above the quote section.
 *
 * Imported from assets/js/app.js, which base.html.twig loads on every
 * page. The module no-ops when .services-cta-bar isn't in the DOM, so
 * it's safe to ship site-wide without a route check.
 */

function initServicesCtaBar() {
    const bar = document.querySelector('.services-cta-bar');
    const quote = document.getElementById('quote');
    if (!bar || !quote) {
        return;
    }

    bar.addEventListener('click', (event) => {
        event.preventDefault();
        quote.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Hide the bar while the quote section is on screen. Fall back to
    // leaving the bar visible on browsers without IntersectionObserver
    // (clicking still works via the handler above).
    if (typeof IntersectionObserver === 'function') {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    bar.classList.toggle('services-cta-bar--hidden', entry.isIntersecting);
                });
            },
            { threshold: 0.01 }
        );
        observer.observe(quote);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initServicesCtaBar);
} else {
    initServicesCtaBar();
}
