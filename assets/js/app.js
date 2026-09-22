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
