/* Mediakanjers → Diagnose: de inhoud (in de footer geprint, inc/diagnostics.php) op zijn plek zetten.
   MK-balk, stijl en donkere modus komen uit mk-admin.js / mk-admin.css. */
(function () {
    var content = document.getElementById('mkbase-diagnose-content');
    var wrap = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;

    var form = wrap.querySelector('form');
    if (form) form.hidden = true;
    var anchor = wrap.querySelector('.mk-intro') || wrap.querySelector('h1');
    wrap.insertBefore(content, anchor ? anchor.nextSibling : null);
    content.style.display = '';
})();
