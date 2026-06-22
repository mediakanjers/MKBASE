(function() {

    // Dark mode — altijd als eerste
    var darkToggle = document.getElementById('mk-dark-toggle');
    function applyDark(dark) {
        document.body.classList.toggle('dark', dark);
        if (darkToggle) darkToggle.textContent = dark ? '☀️  Light' : '🌙  Dark';
        localStorage.setItem('mkbase-dark', dark ? '1' : '0');
    }
    applyDark(localStorage.getItem('mkbase-dark') === '1');
    if (darkToggle) darkToggle.addEventListener('click', function() {
        applyDark(!document.body.classList.contains('dark'));
    });

    // Scroll progress bar
    var bar = document.createElement('div');
    bar.id = 'scroll-progress';
    document.body.insertBefore(bar, document.body.firstChild);

    // Mini-header
    var miniHeader = document.createElement('div');
    miniHeader.id = 'mini-header';
    miniHeader.setAttribute('style', '');
    miniHeader.innerHTML =
        '<div id="mini-header__dot"></div>' +
        '<span id="mini-header__num">MKBase</span>' +
        '<span id="mini-header__title">Changelog</span>' +
        '<span id="mini-header__progress"></span>';
    document.body.insertBefore(miniHeader, bar.nextSibling);

    // Scroll handler: progress bar + mini-header + versie bijhouden
    var hNum   = document.getElementById('mini-header__num');
    var hTitle = document.getElementById('mini-header__title');
    var hProg  = document.getElementById('mini-header__progress');
    var cover  = document.querySelector('.mk-cover');

    function updateScroll() {
        var scrollY   = window.scrollY;
        var maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        var pct       = maxScroll > 0 ? scrollY / maxScroll * 100 : 0;
        bar.style.transform = 'scaleX(' + (pct / 100) + ')';
        if (hProg) hProg.textContent = Math.round(pct) + '%';

        // Mini-header zichtbaar na de cover
        var coverBottom = cover ? cover.offsetTop + cover.offsetHeight : 0;
        miniHeader.classList.toggle('is-visible', scrollY > coverBottom + 40);

        // Huidige versie bijhouden
        var currentVersion = null;
        document.querySelectorAll('.mkbase-version-block').forEach(function(block) {
            if (block.offsetTop <= scrollY + 100) currentVersion = block;
        });

        // Huidige h3 sectie bijhouden
        var currentH3 = null;
        document.querySelectorAll('#mkbase-changelog-content .mkbase-version-body h3').forEach(function(h) {
            if (h.offsetParent !== null && h.offsetTop <= scrollY + 100) currentH3 = h;
        });

        if (currentVersion) {
            var badgeEl = currentVersion.querySelector('.mk-version-badge');
            if (badgeEl && hNum) hNum.textContent = badgeEl.textContent.trim();
        }

        if (currentH3 && hTitle) {
            hTitle.textContent = currentH3.textContent.replace(/^\s*/, '').trim();
        } else if (currentVersion) {
            var titleEl = currentVersion.querySelector('.mkbase-version-title');
            if (titleEl && hTitle) hTitle.textContent = titleEl.textContent.trim();
        }
    }

    // Positioneer fixed elementen na de WP admin sidebar
    function alignToContent() {
        var wc   = document.getElementById('wpcontent');
        var left = wc ? Math.round(wc.getBoundingClientRect().left + window.scrollX) : 0;
        if (bar)        { bar.style.left        = left + 'px'; }
        if (miniHeader) { miniHeader.style.left = left + 'px'; }
    }
    alignToContent();
    window.addEventListener('resize', alignToContent);

    window.addEventListener('scroll', updateScroll, { passive: true });
    updateScroll();

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

    // Content + wrap
    var content = document.getElementById('mkbase-changelog-content');
    var wrap    = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;

    var form = wrap.querySelector('form');
    if (form) form.style.display = 'none';
    content.style.display = '';
    var h1 = wrap.querySelector('h1');
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

    // ── Versie-badges injecteren ─────────────────────────────
    document.querySelectorAll('.mkbase-version-toggle').forEach(function(btn) {
        var titleEl = btn.querySelector('.mkbase-version-title');
        if (!titleEl) return;
        var match = titleEl.textContent.trim().match(/v?(\d+\.\d+(?:\.\d+)?)/);
        var num   = match ? match[1] : titleEl.textContent.trim().substring(0, 5);
        var badge = document.createElement('span');
        badge.className   = 'mk-version-badge';
        badge.textContent = 'v' + num;
        btn.insertBefore(badge, btn.firstChild);
    });

    // ── Copy-knoppen ─────────────────────────────────────────
    document.querySelectorAll('#mkbase-changelog-content pre').forEach(function(pre) {
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

    // ── Lege-staat element aanmaken ──────────────────────────
    var emptyState = document.createElement('div');
    emptyState.id  = 'mk-changelog-empty';
    emptyState.style.display = 'none';
    emptyState.innerHTML =
        '<div class="mk-empty-state">' +
        '<div class="mk-empty-state__icon">🔍</div>' +
        '<div class="mk-empty-state__title">Geen items gevonden</div>' +
        '<div class="mk-empty-state__sub">Er zijn geen changelog-items met dit filter. Probeer een andere categorie.</div>' +
        '</div>';
    var filtersEl = document.getElementById('mkbase-changelog-filters');
    if (filtersEl) filtersEl.parentNode.insertBefore(emptyState, filtersEl.nextSibling);

    // ── Filterstabs ──────────────────────────────────────────
    document.querySelectorAll('.mkbase-filter-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.mkbase-filter-tab').forEach(function(t) { t.classList.remove('is-active'); });
            tab.classList.add('is-active');
            var filter = tab.getAttribute('data-filter');

            document.querySelectorAll('#mkbase-changelog-content li').forEach(function(li) {
                li.style.display = (filter === 'all' || li.querySelector('.mkbase-type-' + filter)) ? '' : 'none';
            });

            var anyVisible = false;
            document.querySelectorAll('.mkbase-version-body').forEach(function(body) {
                var all     = body.querySelectorAll('li');
                var visible = Array.prototype.filter.call(all, function(li) { return li.style.display !== 'none'; });
                var block   = body.closest('.mkbase-version-block');
                var show    = !(all.length > 0 && visible.length === 0);
                block.style.display = show ? '' : 'none';
                if (show) anyVisible = true;
            });

            emptyState.style.display = anyVisible ? 'none' : 'block';
        });
    });

    // ── Versie accordion (smooth) ────────────────────────────
    document.querySelectorAll('.mkbase-version-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var body   = btn.closest('.mkbase-version-block').querySelector('.mkbase-version-body');
            var icon   = btn.querySelector('.mkbase-version-toggle-icon');
            var isOpen = body.style.display !== 'none' && body.style.height !== '0px';
            btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            icon.className = 'mkbase-version-toggle-icon dashicons ' +
                (isOpen ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2');
            isOpen ? smoothClose(body) : smoothOpen(body);
        });
    });

    // ── Fade-in versie blokken bij inview ────────────────────
    var blocks = document.querySelectorAll('.mkbase-version-block');
    blocks.forEach(function(b) { b.classList.add('fade-ready'); });
    if ('IntersectionObserver' in window) {
        var vio = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) { e.target.classList.add('is-visible'); vio.unobserve(e.target); }
            });
        }, { threshold: 0.04 });
        blocks.forEach(function(b) { vio.observe(b); });
    } else {
        blocks.forEach(function(b) { b.classList.add('is-visible'); });
    }

    // ── Oudere versies toggle (smooth) ───────────────────────
    var olderToggle  = document.getElementById('mkbase-older-toggle');
    var olderSection = document.getElementById('mkbase-changelog-older');
    if (olderToggle && olderSection) {
        olderToggle.addEventListener('click', function() {
            var isOpen = olderSection.style.display !== 'none';
            olderToggle.querySelector('.mkbase-older-label').textContent =
                isOpen ? 'Bekijk oudere versies' : 'Verberg oudere versies';
            olderToggle.querySelector('.dashicons').className =
                'dashicons ' + (isOpen ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2');
            isOpen ? smoothClose(olderSection) : smoothOpen(olderSection);
        });
    }

})();
