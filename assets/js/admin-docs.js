(function() {

    // Dark mode — altijd als eerste, vóór elke early return
    function applyDarkDocs(dark) {
        document.body.classList.toggle('dark', dark);
        var sidebarBtn = document.getElementById('dark-toggle');
        var navBtn     = document.getElementById('mk-dark-toggle');
        if (sidebarBtn) sidebarBtn.textContent = dark ? '☀️  Light mode' : '🌙  Dark mode';
        if (navBtn)     navBtn.textContent      = dark ? '☀️  Light'      : '🌙  Dark';
        localStorage.setItem('mkbase-dark', dark ? '1' : '0');
    }

    applyDarkDocs(localStorage.getItem('mkbase-dark') === '1');

    function onDarkToggle() { applyDarkDocs(!document.body.classList.contains('dark')); }
    document.addEventListener('click', function(e) {
        if (e.target.id === 'dark-toggle' || e.target.id === 'mk-dark-toggle') onDarkToggle();
    });

    // Scroll progress bar
    var docBar = document.createElement('div');
    docBar.id = 'scroll-progress';
    document.body.insertBefore(docBar, document.body.firstChild);

    // Positioneer fixed elementen na de WP admin sidebar
    function alignToContent() {
        var wc   = document.getElementById('wpcontent');
        var left = wc ? Math.round(wc.getBoundingClientRect().left + window.scrollX) : 0;
        if (docBar) docBar.style.left = left + 'px';
    }
    alignToContent();
    window.addEventListener('resize', alignToContent);

    window.addEventListener('scroll', function() {
        var scrollY   = window.scrollY;
        var maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        var pct       = maxScroll > 0 ? scrollY / maxScroll : 0;
        if (docBar) docBar.style.transform = 'scaleX(' + pct + ')';
    }, { passive: true });

    // Back-to-top knop
    var backTop = document.createElement('button');
    backTop.id        = 'mk-back-top';
    backTop.title     = 'Terug naar boven';
    backTop.innerHTML = '↑';
    document.body.appendChild(backTop);
    backTop.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    window.addEventListener('scroll', function() {
        backTop.classList.toggle('is-visible', window.scrollY > 300);
    }, { passive: true });

    // Copy-knoppen op code-blokken (werkt ook als wrap niet gevonden wordt)
    document.querySelectorAll('#mkbase-docs-content pre').forEach(function(pre) {
        var btn = document.createElement('button');
        btn.className   = 'mk-copy-btn';
        btn.textContent = 'Copy';
        btn.addEventListener('click', function() {
            var code = pre.querySelector('code');
            var text = (code ? code.textContent : pre.textContent).trim();
            navigator.clipboard.writeText(text).then(function() {
                btn.textContent = '✓ Gekopieerd';
                btn.classList.add('copied');
                setTimeout(function() { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 2000);
            });
        });
        pre.style.position = 'relative';
        pre.appendChild(btn);
    });

    // Docs-specifieke logica vereist content + wrap
    var content = document.getElementById('mkbase-docs-content');
    var wrap    = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;

    var form = wrap.querySelector('form');
    if (form) form.style.display = 'none';

    var h1 = wrap.querySelector('h1');
    content.style.display = '';
    wrap.insertBefore(content, h1 ? h1.nextSibling : null);

    // ── Smooth open/close helpers ────────────────────────────
    function smoothOpen(el) {
        el.style.display    = 'block';
        el.style.overflow   = 'hidden';
        el.style.height     = '0px';
        el.style.opacity    = '0';
        el.style.transition = 'height 0.32s cubic-bezier(0.4,0,0.2,1), opacity 0.28s ease';
        requestAnimationFrame(function() {
            el.style.height  = el.scrollHeight + 'px';
            el.style.opacity = '1';
            el.addEventListener('transitionend', function h(e) {
                if (e.propertyName !== 'height') return;
                el.style.height     = '';
                el.style.overflow   = '';
                el.style.transition = '';
                el.style.opacity    = '';
                el.removeEventListener('transitionend', h);
            });
        });
    }

    function smoothClose(el) {
        el.style.height     = el.scrollHeight + 'px';
        el.style.overflow   = 'hidden';
        el.style.transition = 'height 0.28s cubic-bezier(0.4,0,0.2,1), opacity 0.22s ease';
        requestAnimationFrame(function() {
            el.style.height  = '0px';
            el.style.opacity = '0';
            el.addEventListener('transitionend', function h(e) {
                if (e.propertyName !== 'height') return;
                el.style.display    = 'none';
                el.style.height     = '';
                el.style.overflow   = '';
                el.style.transition = '';
                el.style.opacity    = '';
                el.removeEventListener('transitionend', h);
            });
        });
    }

    // ── Sectie-tellers injecteren ────────────────────────────
    document.querySelectorAll('.mkbase-docs-section').forEach(function(section) {
        var items  = section.querySelectorAll('.mkbase-docs-item');
        if (!items.length) return;
        var toggle = section.querySelector('.mkbase-docs-toggle');
        if (!toggle) return;
        var arrow = toggle.querySelector('.mkbase-docs-toggle__arrow');
        var count = document.createElement('span');
        count.className   = 'mk-section-count';
        count.textContent = items.length;
        toggle.insertBefore(count, arrow);
    });

    // ── Zijbalk navigatie met animatie ───────────────────────
    document.querySelectorAll('.mkbase-docs-nav-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.mkbase-docs-nav-item').forEach(function(b) { b.classList.remove('is-active'); });
            document.querySelectorAll('.mkbase-docs-panel').forEach(function(p) { p.classList.remove('is-active'); p.style.display = 'none'; });
            btn.classList.add('is-active');
            var panel = document.querySelector('.mkbase-docs-panel[data-panel="' + btn.getAttribute('data-panel') + '"]');
            if (panel) {
                panel.style.display = 'block';
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() { panel.classList.add('is-active'); });
                });
            }
        });
    });

    // ── Toetsenbord-navigatie ────────────────────────────────
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        var active = document.querySelector('.mkbase-docs-nav-item.is-active');
        if (!active) return;
        var items = Array.from(document.querySelectorAll('.mkbase-docs-nav-item'));
        var idx   = items.indexOf(active);
        var next  = null;
        if (e.key === 'ArrowDown' && idx < items.length - 1) next = items[idx + 1];
        if (e.key === 'ArrowUp'   && idx > 0)                next = items[idx - 1];
        if (next) { e.preventDefault(); next.click(); next.focus(); }
    });

    // ── Accordion secties (smooth) ───────────────────────────
    document.querySelectorAll('.mkbase-docs-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var body   = btn.nextElementSibling;
            var isOpen = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            isOpen ? smoothClose(body) : smoothOpen(body);
        });
    });

})();
