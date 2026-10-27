(function() {

    // Dark mode — zelfde patroon als admin-docs.js/admin-changelog.js
    function applyDarkDiagnostics(dark) {
        document.body.classList.toggle('dark', dark);
        var navBtn = document.getElementById('mk-dark-toggle');
        if (navBtn) navBtn.textContent = dark ? '☀️  Light' : '🌙  Dark';
        localStorage.setItem('mkbase-dark', dark ? '1' : '0');
    }

    applyDarkDiagnostics(localStorage.getItem('mkbase-dark') === '1');

    document.addEventListener('click', function(e) {
        if (e.target.id === 'mk-dark-toggle') {
            applyDarkDiagnostics(!document.body.classList.contains('dark'));
        }
    });

    var content = document.getElementById('mkbase-diagnose-content');
    var wrap    = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;

    var form = wrap.querySelector('form');
    if (form) form.style.display = 'none';

    var h1 = wrap.querySelector('h1');
    content.style.display = '';
    wrap.insertBefore(content, h1 ? h1.nextSibling : null);

})();
