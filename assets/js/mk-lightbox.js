/**
 * MKBase lightbox — eigen laag over Tobii (assets/vendor/tobii).
 *
 * Blokken en child themes gebruiken alleen deze data-attributen en de handle
 * 'mk-lightbox', nooit Tobii zelf. Zo kan de library eronder later vervangen
 * worden zonder dat blokken of sites aangepast hoeven te worden.
 *
 *   <a href="foto.jpg" data-mk-lightbox="groep">                                   Foto
 *   <a href="https://youtu.be/ID" data-mk-lightbox data-mk-lightbox-type="video">  YouTube, Vimeo of eigen .mp4
 *   <a href="https://..." data-mk-lightbox data-mk-lightbox-type="iframe">          Iframe
 *   <a href="#element-id" data-mk-lightbox data-mk-lightbox-type="html">            Eigen HTML-content
 *
 * Links met dezelfde waarde voor data-mk-lightbox vormen samen een galerij; zonder
 * waarde opent een link los. Eigen HTML-content wordt verplaatst (niet gekopieerd),
 * dus formulieren en scripts daarin blijven werken.
 */
(function() {
    var config = window.mkLightboxConfig || {};
    var uid    = 0;

    // YouTube via youtube-nocookie en Vimeo met dnt=1 (privacyvriendelijk), anders null
    function videoEmbedUrl(url) {
        var youtube = url.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/);
        if (youtube) return 'https://www.youtube-nocookie.com/embed/' + youtube[1] + '?autoplay=1&rel=0';

        var vimeo = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        if (vimeo) return 'https://player.vimeo.com/video/' + vimeo[1] + '?autoplay=1&dnt=1';

        return null;
    }

    // Vertaalt de mk-lightbox-attributen naar wat Tobii verwacht
    function prepare(el) {
        var type = el.getAttribute('data-mk-lightbox-type') || 'image';
        var href = el.getAttribute('href');

        el.setAttribute('data-group', el.getAttribute('data-mk-lightbox') || 'mk-lightbox-' + (++uid));

        if (type === 'iframe' || type === 'html') {
            el.setAttribute('data-type', type);
        } else if (type === 'video') {
            var embedUrl = videoEmbedUrl(href);

            if (embedUrl) {
                el.setAttribute('data-type', 'iframe');
                el.setAttribute('data-target', embedUrl);
                el.setAttribute('data-allow', 'autoplay; fullscreen; picture-in-picture; encrypted-media');
            } else {
                // Eigen videobestand als HTML-content; Tobii start en pauzeert de video zelf
                var wrap  = document.createElement('div');
                var video = document.createElement('video');
                wrap.id        = 'mk-lightbox-video-' + (++uid);
                wrap.className = 'mk-lightbox-video';
                video.src         = href;
                video.controls    = true;
                video.playsInline = true;
                wrap.appendChild(video);
                document.body.appendChild(wrap);

                el.setAttribute('data-type', 'html');
                el.setAttribute('data-target', '#' + wrap.id);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var els = document.querySelectorAll('[data-mk-lightbox]');
        if (!els.length) return;

        els.forEach(prepare);

        // theme: eigen class .mk-lightbox op de lightbox, voor de stylinghandvatten in mk-lightbox.css
        new Tobii(Object.assign({ selector: '[data-mk-lightbox]', theme: 'mk-lightbox' }, config.labels));
    });
})();
