(function () {
    var done = false;
    var observer;

    function tryInit() {
        if (done) return;

        var wrap = document.querySelector('#wpbody-content .wrap');
        if (!wrap) return;

        var allNotices = Array.prototype.slice.call(
            wrap.querySelectorAll('.notice:not(.inline), .updated:not(.inline)')
        ).filter(function (n) {
            return !n.closest('.mk-notices-wrapper');
        });

        if (allNotices.length === 0) return;

        done = true;
        if (observer) observer.disconnect();

        var stored = sessionStorage.getItem('mk_notices_collapsed');
        var isCollapsed = stored === null ? true : stored === 'true';

        var wrapper = document.createElement('div');
        wrapper.className = 'mk-notices-wrapper';

        var header = document.createElement('div');
        header.className = 'mk-notices-header';

        var label = document.createElement('span');
        label.className = 'mk-notices-label';
        label.textContent = allNotices.length + (allNotices.length !== 1 ? ' Beheerdersmeldingen' : ' Beheerdersmelding');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mk-notices-toggle button';

        var inner = document.createElement('div');
        inner.className = 'mk-notices-inner';

        header.appendChild(label);
        header.appendChild(btn);
        wrapper.appendChild(header);
        wrapper.appendChild(inner);

        wrap.insertBefore(wrapper, wrap.firstChild);

        allNotices.forEach(function (notice) {
            inner.appendChild(notice);
        });

        function update() {
            if (isCollapsed) {
                inner.style.display = 'none';
                btn.textContent = 'Toon meldingen';
            } else {
                inner.style.display = 'block';
                btn.textContent = 'Inklappen';
            }
        }

        btn.addEventListener('click', function () {
            isCollapsed = !isCollapsed;
            sessionStorage.setItem('mk_notices_collapsed', isCollapsed ? 'true' : 'false');
            update();
        });

        update();
    }

    var wpbody = document.getElementById('wpbody-content');
    if (wpbody && window.MutationObserver) {
        observer = new MutationObserver(tryInit);
        observer.observe(wpbody, { childList: true, subtree: true });
    }

    setTimeout(tryInit, 0);
    window.addEventListener('load', function () {
        tryInit();
        if (!done && observer) observer.disconnect();
    });
})();
