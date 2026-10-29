// Review carousels — prefers-reduced-motion handling.
//
// Both the homepage sidebar (`#cg-review-carousel`) and the shared
// service-landing social-proof block (`#service-review-carousel`)
// mark themselves up as Bootstrap 4 carousels with `data-ride`,
// `data-interval="6000"`, and `data-pause="hover"`. Bootstrap handles
// 6s auto-advance, the fade transition (`.carousel-fade`), and the
// hover-pause by itself. The only behavior Bootstrap doesn't cover is
// `prefers-reduced-motion`, which is handled here.
//
// On load, if the user prefers reduced motion we tell every review
// carousel on the page to never auto-advance. The companion SCSS
// rule in `assets/css/parts/_fencing-landing.scss` and `.../app.scss`
// kills the fade transition under the same media query so the
// carousel does not animate at all. Users can still change slides by
// clicking indicators or controls — only the automatic motion stops.
//
// Module is deliberately no-op outside DOMContentLoaded + jQuery; it
// matches the existing pattern used by services-cta.js and
// services-dropdown.js.

import $ from 'jquery';

const CAROUSEL_SELECTOR = '#cg-review-carousel, #service-review-carousel';

function applyReducedMotion() {
    const mq = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
    if (!mq || !mq.matches) {
        return;
    }
    $(CAROUSEL_SELECTOR).each(function () {
        // `interval: false` disables the auto-advance timer; `pause: true`
        // keeps it paused regardless of hover state. Clicks on indicators
        // or arrow controls (if present) still work.
        $(this).carousel({ interval: false, pause: true });
        $(this).carousel('pause');
    });
}

$(function () {
    applyReducedMotion();
});
