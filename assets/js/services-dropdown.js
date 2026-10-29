// Services dropdown — hover-to-open at desktop widths.
//
// The CSS in app.scss already shows the dropdown on `:hover` /
// `:focus-within` of `.services-nav-item` at sm+ so the menu appears
// without JS. That alone, however, doesn't update Bootstrap's
// `aria-expanded` / `.show` state on the toggle button, so screen
// readers wouldn't be told the menu is open.
//
// This helper mirrors the hover state into Bootstrap's dropdown API
// at sm+ only. It is a no-op below sm — the mobile navbar collapses
// into a toggler, and taps continue to open the dropdown through
// Bootstrap's existing `data-toggle="dropdown"` wiring. Escape to
// close, outside-click to close, and keyboard navigation inside the
// menu are all handled by Bootstrap and remain untouched.
//
// Depends on jQuery + Bootstrap 4 (both already imported by app.js).

import $ from 'jquery';

const DESKTOP_BREAKPOINT_PX = 576; // Bootstrap `sm`

$(function () {
    const $navItem = $('.services-nav-item');
    const $toggle = $navItem.find('.services-nav-item__toggle');
    if ($navItem.length === 0 || $toggle.length === 0) {
        return;
    }

    const isDesktop = () => window.matchMedia(`(min-width: ${DESKTOP_BREAKPOINT_PX}px)`).matches;

    $navItem.on('mouseenter', () => {
        if (!isDesktop()) {
            return;
        }
        if (!$toggle.parent().hasClass('show')) {
            $toggle.dropdown('show');
        }
    });

    // Hide immediately when the pointer leaves the Services item.
    // `mouseleave` doesn't bubble, so moving between SERVICES, the
    // caret, and the dropdown items (all descendants of the <li>)
    // does not trigger it — only leaving the item's bounding box does.
    $navItem.on('mouseleave', () => {
        if (!isDesktop()) {
            return;
        }
        $toggle.dropdown('hide');
    });
});
