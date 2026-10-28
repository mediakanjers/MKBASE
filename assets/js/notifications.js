/**
 * Meldingen — zie inc/notifications.php.
 * Weggeklikte meldingen verbergen, ruimte maken voor de balken en ze kleiner maken na scrollen.
 * Zet ook CSS-variabelen op <html> die het child theme kan gebruiken:
 * --mk-notification-height (bovenaan), --mk-notification-bottom-height (onderaan).
 */
(function () {
    var PREFIX = 'mk-melding-';
    var DAG    = 86400000;
    var root   = document.documentElement;

    function opslag(opnieuw) {
        try {
            return opnieuw === 'sessie' ? window.sessionStorage : window.localStorage;
        } catch (e) {
            return null; // Opslag geblokkeerd: melding gewoon tonen
        }
    }

    function isWeggeklikt(melding) {
        var opnieuw = melding.dataset.opnieuw;
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

    function onthoud(melding) {
        var store = opslag(melding.dataset.opnieuw);
        try { if (store) store.setItem(PREFIX + melding.dataset.id, String(Date.now())); } catch (e) {}
    }

    function verwijder(melding) {
        var groep = melding.parentNode;
        melding.remove();
        if (groep && !groep.querySelector('.mk-notification')) groep.remove();
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

    ruimte();
    window.addEventListener('load', ruimte);
    window.addEventListener('resize', ruimte);

    document.addEventListener('click', function (event) {
        var knop = event.target.closest('.mk-notification__close');
        if (!knop) return;

        var melding = knop.closest('.mk-notification');
        onthoud(melding);
        melding.classList.add('is-closing');
        setTimeout(function () {
            verwijder(melding);
            ruimte();
        }, 200);
    });

    window.addEventListener('scroll', function () {
        var gescrold = window.scrollY > 100;
        document.querySelectorAll('.mk-notification').forEach(function (melding) {
            melding.classList.toggle('scrolled', gescrold);
        });
    }, { passive: true });
})();
