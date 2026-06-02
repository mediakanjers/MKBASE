(function() {
    var content = document.getElementById('mkbase-docs-content');
    var wrap    = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;

    var form = wrap.querySelector('form');
    if (form) form.style.display = 'none';

    var h1 = wrap.querySelector('h1');
    content.style.display = '';
    wrap.insertBefore(content, h1 ? h1.nextSibling : null);

    // Zijbalk navigatie
    document.querySelectorAll('.mkbase-docs-nav-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.mkbase-docs-nav-item').forEach(function(b) { b.classList.remove('is-active'); });
            document.querySelectorAll('.mkbase-docs-panel').forEach(function(p) { p.classList.remove('is-active'); });
            btn.classList.add('is-active');
            var panel = document.querySelector('.mkbase-docs-panel[data-panel="' + btn.getAttribute('data-panel') + '"]');
            if (panel) panel.classList.add('is-active');
        });
    });

    // Accordion secties
    document.querySelectorAll('.mkbase-docs-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var body   = btn.nextElementSibling;
            var isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : 'block';
            btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
    });
})();
