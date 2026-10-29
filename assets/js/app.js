import '../css/app.scss';

import $ from 'jquery';
window.$ = $;
window.jQuery = $;

import 'popper.js';
import 'bootstrap';

// Quote form submit handler. Loaded here (not in landing-page.js)
// because base.html.twig loads the `app` entry on every page but has
// never loaded `landing-page`, and the quote form is rendered on
// every page via templates/components/forms/quote-request.html.twig.
import './quote-form.js';

// /services page: fixed "GET QUOTE" bottom bar. Imported here (not
// in a page-specific entry) because base.html.twig loads `app` on
// every page and the module no-ops when the bar isn't in the DOM.
import './services-cta.js';

// Services dropdown — hover-to-open at desktop widths. No-ops when
// the dropdown isn't in the DOM. See the module for details.
import './services-dropdown.js';

// Click-to-load YouTube facade. No-ops when no `.youtube-facade`
// elements are in the DOM. See the module for details.
import './youtube-facade.js';

// Review carousels — pauses auto-advance under
// `prefers-reduced-motion: reduce`. No-ops otherwise; Bootstrap
// drives the normal 6s rotation.
import './reviews-carousel.js';
