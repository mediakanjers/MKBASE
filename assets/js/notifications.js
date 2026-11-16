/**
 * Meldingen — zie inc/notifications.php en template-parts/notifications/notifications.php.
 * Weggeklikte meldingen verbergen, ruimte maken voor de balken, later verschijnen (na seconden of
 * scrollen), de pop-up openen en automatisch verdwijnen (pauzeert zolang de bezoeker erop staat).
 * Zet ook CSS-variabelen op <html> die het child theme kan gebruiken:
 * --mk-notification-height (bovenaan), --mk-notification-bottom-height (onderaan).
 */
(function () {
    var PREFIX = 'mk-melding-';
    var DAG    = 86400000;
    var root   = document.documentElement;
    var stil   = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function opslag(opnieuw) {
        try {
            return opnieuw === 'sessie' ? window.sessionStorage : window.localStorage;
        } catch (e) {
            return null; // Opslag geblokkeerd: melding gewoon tonen
        }
    }

    // "Op de volgende pagina" onthoudt niets: de melding komt bij elke pagina terug
    function isWeggeklikt(melding) {
        var opnieuw = melding.dataset.opnieuw;
        if (opnieuw === 'pagina') return false;
        var store   = opnieuw && opslag(opnieuw);
        if (!store) return false;

        var moment = null;
        try { moment = store.getItem(PREFIX + melding.dataset.id); } catch (e) {}
        if (!moment) return false;

        if (opnieuw === 'dagen') {
            var dagen = parseInt(melding.dataset.dagen, 10) || 7;
            return Date.now() - parseInt(moment, 10) < dagen * DAG;
        }
        return true;
    }

    // Alleen bij "Sluitbaar": anders komt de melding op de volgende pagina gewoon terug
    function onthoud(melding) {
        if (!melding.dataset.opnieuw || melding.dataset.opnieuw === 'pagina') return;
        var store = opslag(melding.dataset.opnieuw);
        try { if (store) store.setItem(PREFIX + melding.dataset.id, String(Date.now())); } catch (e) {}
    }

    function verwijder(melding) {
        var groep = melding.parentNode;
        if (melding.tagName === 'DIALOG') {
            if (melding.open) melding.close();
            root.classList.remove('mk-notification-scroll-blok');
        }
        melding.remove();
        if (groep && groep.classList && groep.classList.contains('mk-notifications') && !groep.querySelector('.mk-notification')) groep.remove();
        var blokken = document.querySelector('.mk-notification-blokken');
        if (blokken && !blokken.querySelector('.mk-notification')) blokken.remove();
    }

    // Direct bij het laden, vóór de eerste keer tekenen
    document.querySelectorAll('.mk-notification').forEach(function (melding) {
        if (isWeggeklikt(melding)) verwijder(melding);
    });

    var eigenCss = !!document.querySelector('.mk-notifications[data-eigen-css]');
    var header   = document.querySelector('.mk-header');

    function hoogte(selector) {
        var groep = document.querySelector(selector);
        return groep ? groep.offsetHeight : 0;
    }

    // Niet bij scrollen: de balk wordt dan kleiner en de header zou verspringen
    function ruimte() {
        var vast  = hoogte('.mk-notifications--boven');
        var boven = vast + hoogte('.mk-notifications--boven-scrollt');
        var onder = hoogte('.mk-notifications--onder');

        root.style.setProperty('--mk-notification-fixed-height', vast + 'px');
        root.style.setProperty('--mk-notification-height', boven + 'px');
        root.style.setProperty('--mk-notification-bottom-height', onder + 'px');

        if (eigenCss) return;
        if (header) header.style.marginTop = boven ? boven + 'px' : '';
        document.body.style.paddingBottom = onder ? onder + 'px' : '';
    }

    // ── Sluiten (kruisje, Esc, klik naast de pop-up, of automatisch) ──
    function sluit(melding, bewaar) {
        if (!melding || melding.classList.contains('is-closing')) return;
        if (bewaar) onthoud(melding);
        melding.classList.add('is-closing');
        setTimeout(function () {
            verwijder(melding);
            ruimte();
        }, stil ? 0 : 200);
    }

    document.addEventListener('click', function (event) {
        var knop = event.target.closest('.mk-notification__close');
        if (knop) { sluit(knop.closest('.mk-notification'), true); return; }
        // Klik op de donkere achtergrond van de pop-up (de klik landt dan op <dialog> zelf)
        if (event.target.matches && event.target.matches('dialog.mk-notification')) {
            var r = event.target.getBoundingClientRect();
            var buiten = event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom;
            if (buiten) sluit(event.target, true);
        }
    });

    // ── Automatisch verdwijnen, pauze bij muis of toetsenbord erop ──
    function autoVerdwijnen(melding) {
        var sec = parseInt(melding.dataset.auto, 10);
        if (!sec) return;
        var rest = sec * 1000, start = 0, timer = null;
        var balkje = melding.querySelector('.mk-notification__timer');
        if (balkje) balkje.style.animationDuration = sec + 's';

        function loop() {
            start = Date.now();
            melding.classList.remove('is-paused');
            timer = setTimeout(function () { sluit(melding, true); }, rest);
        }
        function pauze() {
            if (!timer) return;
            clearTimeout(timer);
            timer = null;
            rest -= Date.now() - start;
            melding.classList.add('is-paused');
        }
        melding.addEventListener('mouseenter', pauze);
        melding.addEventListener('mouseleave', function () { if (!melding.contains(document.activeElement)) loop(); });
        melding.addEventListener('focusin', pauze);
        melding.addEventListener('focusout', function (e) { if (!melding.contains(e.relatedTarget) && !melding.matches(':hover')) loop(); });
        loop();
    }

    // ── Tonen ──
    function toon(melding) {
        if (!melding.isConnected) return;
        if (melding.tagName === 'DIALOG') {
            var backdrop = melding.dataset.backdrop !== '0';
            try {
                if (backdrop && melding.showModal) melding.showModal(); else melding.show();
            } catch (e) {
                melding.setAttribute('open', '');
            }
            melding.classList.toggle('is-modal', backdrop);
            // Pagina erachter stilzetten (staat op <html>, wordt bij sluiten weer weggehaald)
            if (melding.dataset.scrollBlok === '1') {
                root.classList.add('mk-notification-scroll-blok');
                melding.addEventListener('close', function () { root.classList.remove('mk-notification-scroll-blok'); });
            }
            // Esc sluit (bij showModal via het cancel-event, anders zelf afvangen)
            melding.addEventListener('cancel', function (e) { e.preventDefault(); sluit(melding, true); });
            if (!backdrop) {
                document.addEventListener('keydown', function esc(e) {
                    if (e.key === 'Escape' && melding.isConnected) { sluit(melding, true); document.removeEventListener('keydown', esc); }
                });
            }
        } else {
            melding.hidden = false;
        }
        melding.classList.add('is-shown');
        ruimte();
        autoVerdwijnen(melding);
    }

    function wachtOpScroll(melding, px) {
        function check() {
            if (window.scrollY < px) return;
            window.removeEventListener('scroll', check);
            toon(melding);
        }
        window.addEventListener('scroll', check, { passive: true });
        check();
    }

    document.querySelectorAll('.mk-notification').forEach(function (melding) {
        var sec = parseInt(melding.dataset.tonenSec, 10);
        var px  = parseInt(melding.dataset.tonenPx, 10);
        if (sec) setTimeout(function () { toon(melding); }, sec * 1000);
        else if (px) wachtOpScroll(melding, px);
        else toon(melding);
    });

    ruimte();
    window.addEventListener('load', ruimte);
    window.addEventListener('resize', ruimte);

    window.addEventListener('scroll', function () {
        var gescrold = window.scrollY > 100;
        document.querySelectorAll('.mk-notification--balk').forEach(function (melding) {
            melding.classList.toggle('scrolled', gescrold);
        });
    }, { passive: true });
})();
