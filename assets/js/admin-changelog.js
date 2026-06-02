(function() {
    // Verplaats de changelog-content naar de ACF wrap en verberg het formulier
    var content = document.getElementById('mkbase-changelog-content');
    var wrap    = document.querySelector('.acf-settings-wrap');
    if (!content || !wrap) return;

    var form = wrap.querySelector('form');
    if (form) form.style.display = 'none';

    var h1 = wrap.querySelector('h1');
    content.style.display = '';
    wrap.insertBefore(content, h1 ? h1.nextSibling : null);

    // Filterstabs
    document.querySelectorAll('.mkbase-filter-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.mkbase-filter-tab').forEach(function(t) { t.classList.remove('is-active'); });
            tab.classList.add('is-active');
            var filter = tab.getAttribute('data-filter');

            document.querySelectorAll('#mkbase-changelog-content li').forEach(function(li) {
                if (filter === 'all') {
                    li.style.display = '';
                } else {
                    li.style.display = li.querySelector('.mkbase-type-' + filter) ? '' : 'none';
                }
            });

            document.querySelectorAll('.mkbase-version-body').forEach(function(body) {
                var allItems     = body.querySelectorAll('li');
                var visibleItems = Array.prototype.filter.call(allItems, function(li) { return li.style.display !== 'none'; });
                body.closest('.mkbase-version-block').style.display = (allItems.length > 0 && visibleItems.length === 0) ? 'none' : '';
            });
        });
    });

    // Versie accordion
    document.querySelectorAll('.mkbase-version-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var body   = btn.closest('.mkbase-version-block').querySelector('.mkbase-version-body');
            var icon   = btn.querySelector('.mkbase-version-toggle-icon');
            var isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : 'block';
            btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            icon.className = 'mkbase-version-toggle-icon dashicons ' + (isOpen ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2');
        });
    });

    // Oudere versies toggle
    var olderToggle  = document.getElementById('mkbase-older-toggle');
    var olderSection = document.getElementById('mkbase-changelog-older');
    if (olderToggle && olderSection) {
        olderToggle.addEventListener('click', function() {
            var isOpen = olderSection.style.display !== 'none';
            olderSection.style.display = isOpen ? 'none' : 'block';
            olderToggle.querySelector('.mkbase-older-label').textContent = isOpen ? 'Bekijk oudere versies' : 'Verberg oudere versies';
            olderToggle.querySelector('.dashicons').className = 'dashicons ' + (isOpen ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2');
        });
    }
})();
