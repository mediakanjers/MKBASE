/**
 * Video pas laden na een klik — zie mkbase_video() in inc/helpers/video.php.
 * Vervangt de afspeelknop door de iframe uit de <template>.
 */
document.addEventListener('click', function (event) {
    var button = event.target.closest('.mk-video__play');
    if (!button) return;

    var video    = button.closest('.mk-video');
    var template = video.querySelector('template');
    if (!template) return;

    video.replaceChildren(template.content.cloneNode(true));
    video.classList.add('is-playing');

    var iframe = video.querySelector('iframe');
    if (iframe) iframe.focus();
});
