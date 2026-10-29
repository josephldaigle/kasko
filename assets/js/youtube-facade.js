// Click-to-load YouTube facade.
//
// Looks for any `.youtube-facade[data-video-id]` on the page and
// replaces it with a YouTube iframe on first click. The facade
// renders a static thumbnail + play button (see `.youtube-facade*`
// rules in assets/css/parts/_service-landing.scss) so no third-party
// player code loads on page view — the iframe is injected only when
// the visitor opts in.
//
// Autoplay fires once the iframe loads because the click qualifies
// as a user gesture; modern browsers allow autoplay with sound in
// that case.
//
// Module no-ops when no `.youtube-facade` elements exist in the DOM,
// so it is safe to import from the site-wide `app.js` entry.

import $ from 'jquery';

$(function () {
    $(document).on('click', '.youtube-facade', function (event) {
        event.preventDefault();

        const $facade = $(event.currentTarget);
        const videoId = $facade.data('video-id');
        if (!videoId) {
            return;
        }

        const src = `https://www.youtube.com/embed/${encodeURIComponent(videoId)}?autoplay=1&rel=0`;
        const $iframe = $('<iframe>', {
            class: 'embed-responsive-item',
            src: src,
            title: $facade.attr('aria-label') || 'YouTube video',
            allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
            allowfullscreen: true,
            frameborder: '0',
        });

        $facade.replaceWith($iframe);
    });
});
