/* Mediakanjers → Openingstijden (inc/openingstijden/admin.php).
   Vier tabbladen (Overzicht · Vaste week · Feestdagen · Afwijkend) over de veldgroepen, de roosterkeuze
   en het tweede tijdvak in de vaste week met een live voorbeeld, "Valt op" bij de feestdagen en de
   status en overlap bij de afwijkende dagen. MK-balk, opslaan en donker: mk-admin.js. */
(function ($) {
    'use strict';
    if (!window.acf) return;

    var cfg = window.mkOtAdmin || {};
    var DAGEN = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
    var KORT = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];
    var MAANDEN = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
    var TABS = [
        { id: 'overzicht', label: 'Overzicht', groepen: ['overzicht', 'algemeen'] },
        { id: 'week', label: 'Vaste week', groepen: ['week'] },
        { id: 'feestdagen', label: 'Feestdagen', groepen: ['feestdagen'] },
        { id: 'afwijkend', label: 'Afwijkend', groepen: ['afwijkend'] }
    ];

    function el(tag, cls, html) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (html != null) n.innerHTML = html;
        return n;
    }
    function box(naam) { return document.getElementById('acf-group_mkbase_ot_' + naam); }
    function veld(scope, key) { return scope.querySelector('[data-key="' + key + '"]'); }
    function input(scope, key) { var v = veld(scope, key); return v ? v.querySelector('input:not([type=hidden]), select') : null; }
    function waarde(scope, key) { var i = input(scope, key); return i ? i.value.trim() : ''; }
    function keuze(scope, key) { var i = scope.querySelector('[data-key="' + key + '"] input:checked'); return i ? i.value : ''; }
    function zichtbaar(v) { return v && !v.classList.contains('acf-hidden') && !v.closest('.acf-hidden'); }
    function wijzig(i) { $(i).trigger('input').trigger('change'); }

    // ── Datums ────────────────────────────────────────────────
    function datum(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
    function plus(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
    function kort(d) {
        var t = KORT[(d.getDay() + 6) % 7] + ' ' + d.getDate() + ' ' + MAANDEN[d.getMonth()];
        return d.getFullYear() !== vandaag.getFullYear() ? t + ' ' + d.getFullYear() : t;
    }
    var vandaag = datum(cfg.vandaag || iso(new Date()));
    var weekStart = datum(cfg.weekStart || cfg.vandaag || iso(new Date()));
    var zondag = plus(weekStart, 6);
    var vooruit = parseInt(cfg.vooruit, 10) || 30; // wp_localize_script geeft tekst
    var grens = plus(vandaag, vooruit);

    // ── Tijden tonen zoals op de website ──────────────────────
    function tijd(t) {
        if (cfg.notatie === 'hh') return t;
        var k = t.replace(/^0(\d)/, '$1');
        return cfg.notatie === 'uur' ? k.replace(':', '.') + ' uur' : k;
    }
    function vak(v) {
        return cfg.notatie === 'uur' ? v[0].replace(/^0(\d)/, '$1').replace(':', '.') + ' – ' + tijd(v[1]) : tijd(v[0]) + ' – ' + tijd(v[1]);
    }
    function norm(t) {
        var m = /^(\d{1,2})[:.]?(\d{2})$/.exec((t || '').trim());
        return m && +m[1] < 25 && +m[2] < 60 ? ('0' + m[1]).slice(-2) + ':' + m[2] : '';
    }

    // =========================================================
    // Tabbladen
    // =========================================================
    var eerste = box('overzicht');
    if (!eerste) return;
    var nav = el('nav', 'mk-ot-tabs');
    nav.setAttribute('aria-label', 'Onderdelen');
    TABS.forEach(function (t) {
        var a = el('a');
        a.href = '#' + t.id;
        a.textContent = t.label;
        a.setAttribute('data-mk-ot-tab', t.id);
        nav.appendChild(a);
    });
    // Roosterkeuze naast de tabbladen (bij Overzicht en Vaste week), zoals in het ontwerp
    var keuzeBalk = el('div', 'mk-ot-roosterkeuze');
    var kopRij = el('div', 'mk-ot-kop');
    kopRij.appendChild(keuzeBalk);
    kopRij.appendChild(nav);
    eerste.parentNode.insertBefore(kopRij, eerste);
    var huidigTab = 'overzicht';

    function toon(id) {
        if (!TABS.some(function (t) { return t.id === id; })) id = 'overzicht';
        huidigTab = id;
        keuzeBalk.hidden = id !== 'overzicht' && id !== 'week';
        var wegKnop = keuzeBalk.querySelector('.mk-btn--danger');
        if (wegKnop) wegKnop.hidden = id !== 'week';
        TABS.forEach(function (t) {
            t.groepen.forEach(function (g) { var b = box(g); if (b) b.hidden = t.id !== id; });
        });
        nav.querySelectorAll('a').forEach(function (a) {
            if (a.getAttribute('data-mk-ot-tab') === id) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
        });
        if (window.history.replaceState) window.history.replaceState(null, '', '#' + id);
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('[data-mk-ot-tab]');
        if (!a) return;
        e.preventDefault();
        toon(a.getAttribute('data-mk-ot-tab'));
    });
    toon(window.location.hash.slice(1));

    // Een fout bij opslaan in een ander tabblad of rooster: daarheen
    acf.addAction('validation_failure', function () {
        setTimeout(function () {
            var fout = document.querySelector('.postbox .acf-field.acf-error, .postbox .acf-error-message');
            var pb = fout && fout.closest('.postbox');
            if (!pb) return;
            TABS.forEach(function (t) { if (t.groepen.some(function (g) { return pb.id === 'acf-group_mkbase_ot_' + g; })) toon(t.id); });
            var rij = fout.closest('.mk-ot-roosters .acf-row');
            if (rij) kiesRooster(rijen().indexOf(rij));
        }, 30);
    });

    // =========================================================
    // Vaste week: één rooster tegelijk
    // =========================================================
    var rep = document.querySelector('[data-key="field_mkbase_ot_roosters"]');
    var actief = 0;
    var voorbeeld = el('aside', 'mk-ot-live', '<h3 class="mk-h2">Live voorbeeld</h3><p class="mk-hint">Loopt mee terwijl je typt. Feestdagen en afwijkingen komen er op de website vanzelf bij.</p><div class="mk-ot-live__frame"></div>');

    function rijen() {
        return rep ? Array.prototype.slice.call(rep.querySelectorAll(':scope > .acf-input > .acf-repeater > .acf-table > tbody > .acf-row:not(.acf-clone)')) : [];
    }
    function roosterVeld() { return rep ? acf.getField($(rep)) : null; }
    function naamVan(rij) { return waarde(rij, 'field_mkbase_ot_r_naam') || 'Rooster'; }

    function bouwKeuze() {
        var r = rijen();
        keuzeBalk.innerHTML = '';
        var groep = el('div', 'mk-segmented');
        groep.setAttribute('role', 'group');
        groep.setAttribute('aria-label', 'Rooster');
        r.forEach(function (rij, i) {
            var b = el('button');
            b.type = 'button';
            b.textContent = naamVan(rij);
            b.setAttribute('aria-pressed', i === actief ? 'true' : 'false');
            b.addEventListener('click', function () { kiesRooster(i); });
            groep.appendChild(b);
        });
        var nieuw = el('button', 'mk-ot-plus', '+ Rooster');
        nieuw.type = 'button';
        nieuw.addEventListener('click', nieuwRooster);
        groep.appendChild(nieuw);
        keuzeBalk.appendChild(groep);

        if (r.length > 1) {
            var weg = el('button', 'mk-btn mk-btn--danger mk-btn--sm', 'Rooster verwijderen');
            weg.type = 'button';
            weg.hidden = huidigTab !== 'week';
            weg.addEventListener('click', function () { verwijderRooster(r[actief]); });
            keuzeBalk.appendChild(weg);
        }
    }

    function verwijderRooster(rij) {
        var doe = function () {
            var f = roosterVeld();
            if (f && f.remove) f.remove($(rij)); else $(rij).find('[data-event="remove-row"]').first().trigger('click');
            actief = 0;
            setTimeout(function () { verversWeek(); roostersLijst(); }, 400);
        };
        if (window.mkDialog) {
            window.mkDialog({
                title: 'Rooster "' + naamVan(rij) + '" verwijderen?',
                text: 'Het rooster verdwijnt pas echt na Opslaan. Staat het nog in een blok, dan krijg je bij opslaan te zien waar.',
                buttons: [{ label: 'Verwijderen', variant: 'danger', onClick: doe }, { label: 'Annuleren', variant: 'secondary' }]
            });
        } else if (window.confirm('Rooster verwijderen?')) {
            doe();
        }
    }

    // Overzicht → Roosters: naam aanpassen, "Gebruiken voor Google" en verwijderen, rechtstreeks in de lijst
    // (gekoppeld aan de rij bij Vaste week via de volgorde; nieuwe roosters staan er na Opslaan bij)
    function roostersLijst() {
        var google = document.querySelector('[data-key="field_mkbase_ot_google"] select');
        var r = rijen();
        document.querySelectorAll('.mk-ot-roosters li[data-mk-ot-rooster]').forEach(function (li) {
            var rij = r[+li.getAttribute('data-mk-ot-rooster')];
            var id = li.getAttribute('data-id');
            if (!rij || !rij.isConnected || rij.classList.contains('acf-clone')) { li.classList.add('is-weg'); return; }
            if (!li.querySelector('.mk-ot-roosters__naam')) {
                var hint = li.querySelector('.mk-hint');
                var label = el('label', 'mk-ot-roosters__naam');
                label.appendChild(el('span', 'mk-ot-roosters__label')).textContent = 'Naam';
                var inp = el('input');
                inp.type = 'text';
                inp.value = naamVan(rij);
                inp.addEventListener('input', function () {
                    var bron = input(rij, 'field_mkbase_ot_r_naam');
                    if (bron) { bron.value = inp.value; wijzig(bron); }
                    bouwKeuze();
                    werkVoorbeeldBij();
                });
                label.appendChild(inp);
                var sterk = li.querySelector('strong');
                if (sterk) sterk.replaceWith(label); else li.insertBefore(label, li.firstChild);
                if (hint) label.insertAdjacentElement('afterend', hint);
            }
            var rechts = li.querySelector('.mk-ot-roosters__rechts');
            if (!rechts) { rechts = el('span', 'mk-ot-roosters__rechts'); li.appendChild(rechts); }
            li.querySelectorAll(':scope > .mk-pill').forEach(function (p) { p.remove(); });
            rechts.innerHTML = '';
            if (google && google.value === id) {
                rechts.appendChild(el('span', 'mk-pill mk-pill--gepland')).textContent = 'Gebruikt voor Google';
            } else if (google) {
                var g = el('button', 'mk-btn mk-btn--secondary mk-btn--sm', 'Gebruiken voor Google');
                g.type = 'button';
                g.addEventListener('click', function () { $(google).val(id).trigger('change'); roostersLijst(); });
                rechts.appendChild(g);
            }
            if (r.length > 1) {
                var weg = el('button', 'mk-ot-afw-kop__weg', '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>');
                weg.type = 'button';
                weg.setAttribute('aria-label', 'Rooster "' + naamVan(rij) + '" verwijderen');
                weg.addEventListener('click', function () { verwijderRooster(rij); });
                rechts.appendChild(weg);
            }
        });
        var googleLeeg = document.querySelector('.mk-ot-google-leeg');
        if (googleLeeg && google) googleLeeg.hidden = !!google.value;
        var ul = document.querySelector('ul.mk-ot-roosters');
        if (ul && r.length > 1 && !ul.nextElementSibling?.classList.contains('mk-ot-roosters__voet')) {
            ul.insertAdjacentElement('afterend', el('p', 'mk-hint mk-ot-roosters__voet', 'Een rooster dat nog in een blok staat, kun je niet verwijderen; bij Opslaan zie je dan op welke pagina’s het staat.'));
        }
    }

    function nieuwRooster() {
        var f = roosterVeld();
        if (!f || !f.add) return;
        f.add();
        setTimeout(function () {
            var r = rijen();
            var rij = r[r.length - 1];
            var naam = input(rij, 'field_mkbase_ot_r_naam');
            if (naam) { naam.value = 'Nieuw rooster'; wijzig(naam); }
            kiesRooster(r.length - 1);
            if (naam) { naam.focus(); naam.select(); }
        }, 50);
    }

    function kiesRooster(i) {
        var r = rijen();
        actief = Math.max(0, Math.min(i, r.length - 1));
        r.forEach(function (rij, n) { rij.classList.toggle('is-actief', n === actief); });
        // Overzicht: de tegels en de regel van het gekozen rooster
        document.querySelectorAll('.mk-ot-tegels[data-mk-ot-rooster]').forEach(function (t) { t.hidden = +t.getAttribute('data-mk-ot-rooster') !== actief; });
        document.querySelectorAll('.mk-ot-roosters li[data-mk-ot-rooster]').forEach(function (li) { li.classList.toggle('is-actief', +li.getAttribute('data-mk-ot-rooster') === actief); });
        bouwKeuze();
        werkVoorbeeldBij();
    }

    // ── Tijdvakken: het tweede pas na "+ Tweede tijdvak" (week, feestdagen en afwijkingen) ──
    // prefix = veldsleutel zonder _van1 enz.; open() = staan de tijden aan (status open of andere tijden)
    function tijdVelden(rij, prefix) {
        return {
            van1: veld(rij, prefix + '_van1'), tot1: veld(rij, prefix + '_tot1'),
            van2: veld(rij, prefix + '_van2'), tot2: veld(rij, prefix + '_tot2'),
            opm: veld(rij, prefix + '_opmerking')
        };
    }
    function dagVelden(rij, dag) { return tijdVelden(rij, 'field_mkbase_ot_r_' + dag); }

    function maakTijdvak(rij, prefix, naam, open) {
        var v = tijdVelden(rij, prefix);
        if (!v.tot1 || !v.tot2 || v.tot1.querySelector('.mk-ot-plus')) return;
        var ververs = function () { verversTijdvak(rij, prefix, open); };

        var plusKnop = el('button', 'mk-ot-plus', '+ Tweede tijdvak');
        plusKnop.type = 'button';
        plusKnop.addEventListener('click', function () {
            rij.classList.add('mk-ot-twee--' + prefix);
            ververs();
            var i = v.van2.querySelector('input');
            if (i) i.focus();
        });
        v.tot1.querySelector('.acf-input').appendChild(plusKnop);

        var weg = el('button', 'mk-ot-weg', '<span aria-hidden="true">×</span>');
        weg.type = 'button';
        weg.setAttribute('aria-label', 'Tweede tijdvak' + (naam ? ' van ' + naam : '') + ' verwijderen');
        weg.addEventListener('click', function () {
            [v.van2, v.tot2].forEach(function (f) { var i = f.querySelector('input'); i.value = ''; wijzig(i); });
            rij.classList.remove('mk-ot-twee--' + prefix);
            ververs();
            werkVoorbeeldBij();
        });
        v.tot2.querySelector('.acf-input').appendChild(weg);
        rij.addEventListener('change', ververs);
        ververs();
    }

    function verversTijdvak(rij, prefix, open) {
        var v = tijdVelden(rij, prefix);
        if (!v.van2) return;
        var heeftTwee = waarde(rij, prefix + '_van2') || waarde(rij, prefix + '_tot2');
        var aan = open();
        var twee = aan && (heeftTwee || rij.classList.contains('mk-ot-twee--' + prefix));
        [v.van2, v.tot2].forEach(function (f) { f.classList.toggle('mk-ot-verborgen', !twee); });
        var p = v.tot1.querySelector('.mk-ot-plus');
        if (p) p.hidden = twee || !aan;
        // Bij de feestdagen staat de knop onder de tijden: dan heeft de regel ruimte nodig
        if (prefix === 'field_mkbase_ot_fd' || prefix === 'field_mkbase_ot_afw') rij.classList.toggle('mk-ot-met-plus', !!p && !p.hidden);
    }

    function maakDag(rij, dag) {
        maakTijdvak(rij, 'field_mkbase_ot_r_' + dag, dag, function () { return keuze(rij, 'field_mkbase_ot_r_' + dag + '_status') === 'open'; });
    }
    function verversDag(rij, dag) {
        verversTijdvak(rij, 'field_mkbase_ot_r_' + dag, function () { return keuze(rij, 'field_mkbase_ot_r_' + dag + '_status') === 'open'; });
        dagFout(rij, dag);
    }

    // Fout direct onder de dag (dezelfde regels als bij opslaan, mkbase_ot_controleer_vakken() in PHP)
    function dagFout(rij, dag) {
        var k = 'field_mkbase_ot_r_' + dag;
        var w = function (deel) { return waarde(rij, k + '_' + deel); };
        var tekst = '';
        if (keuze(rij, k + '_status') === 'open') {
            var vakken = [];
            [1, 2].forEach(function (n) {
                var van = w('van' + n), tot = w('tot' + n);
                if (!van && !tot) return;
                if (!van || !tot) { tekst = tekst || 'Vul bij elk tijdvak een begin- en eindtijd in.'; return; }
                if (!norm(van) || !norm(tot)) { tekst = tekst || 'Vul de tijden in als uu:mm, bijvoorbeeld 08:30.'; return; }
                vakken.push([norm(van), norm(tot)]);
            });
            if (!tekst && vakken.length === 2) {
                var min = function (t) { var p = t.split(':'); return +p[0] * 60 + +p[1]; };
                var einde1 = min(vakken[0][1]) <= min(vakken[0][0]) ? min(vakken[0][1]) + 1440 : min(vakken[0][1]);
                if (min(vakken[1][0]) < einde1) tekst = 'Het tweede tijdvak begint vóór het einde van het eerste (' + vakken[0][1] + ').';
            }
        }
        var opm = veld(rij, k + '_opmerking');
        if (!opm) return;
        var p = rij.querySelector('.mk-ot-dagfout[data-dag="' + dag + '"]');
        if (!tekst) { if (p) p.remove(); return; }
        if (!p) { p = el('p', 'mk-ot-dagfout'); p.setAttribute('data-dag', dag); p.setAttribute('role', 'alert'); opm.insertAdjacentElement('afterend', p); }
        p.textContent = tekst;
        [1, 2].forEach(function (n) {
            ['van', 'tot'].forEach(function (d) { var f = veld(rij, k + '_' + d + n); if (f) f.classList.toggle('mk-ot-fout', !!tekst && n === 2); });
        });
    }

    function maakRij(rij) {
        if (rij.getAttribute('data-mk-ot')) return;
        rij.setAttribute('data-mk-ot', '1');
        DAGEN.forEach(function (dag) { maakDag(rij, dag); });

        // Maandag kopiëren naar dinsdag t/m vrijdag
        var naam = veld(rij, 'field_mkbase_ot_r_naam');
        if (naam && !rij.querySelector('.mk-ot-kopie')) {
            var k = el('div', 'acf-field mk-ot-kopie');
            k.style.width = '50%';
            var b = el('button', 'mk-btn mk-btn--secondary mk-btn--sm', 'Maandag kopiëren naar di t/m vr');
            b.type = 'button';
            b.addEventListener('click', function () {
                var ma = dagVelden(rij, 'maandag');
                var status = keuze(rij, 'field_mkbase_ot_r_maandag_status');
                ['dinsdag', 'woensdag', 'donderdag', 'vrijdag'].forEach(function (dag) {
                    var label = rij.querySelector('[data-key="field_mkbase_ot_r_' + dag + '_status"] input[value="' + status + '"]');
                    if (label) $(label).closest('label').trigger('click');
                    ['van1', 'tot1', 'van2', 'tot2', 'opm'].forEach(function (deel) {
                        var bron = ma[deel] && ma[deel].querySelector('input');
                        var doel = dagVelden(rij, dag)[deel];
                        doel = doel && doel.querySelector('input');
                        if (bron && doel) { doel.value = bron.value; wijzig(doel); }
                    });
                    if (waarde(rij, 'field_mkbase_ot_r_maandag_van2')) rij.classList.add('mk-ot-twee--' + dag);
                    verversDag(rij, dag);
                });
                werkVoorbeeldBij();
                if (window.mkToast) window.mkToast({ title: 'Maandag gekopieerd naar dinsdag t/m vrijdag', tone: 'neutral' });
            });
            k.appendChild(b);
            naam.parentNode.insertBefore(k, naam.nextSibling);
        }
    }

    // ── Live voorbeeld van de vaste week van het gekozen rooster ──
    function werkVoorbeeldBij() {
        var rij = rijen()[actief];
        var frame = voorbeeld.querySelector('.mk-ot-live__frame');
        if (!rij || !frame) return;
        var dagen = DAGEN.map(function (dag) {
            var s = keuze(rij, 'field_mkbase_ot_r_' + dag + '_status') || 'gesloten';
            var vakken = [];
            if (s === 'open') {
                [1, 2].forEach(function (n) {
                    var van = norm(waarde(rij, 'field_mkbase_ot_r_' + dag + '_van' + n));
                    var tot = norm(waarde(rij, 'field_mkbase_ot_r_' + dag + '_tot' + n));
                    if (van && tot && zichtbaar(dagVelden(rij, dag)['van' + n])) vakken.push([van, tot]);
                });
            }
            var opm = s !== 'gesloten' ? waarde(rij, 'field_mkbase_ot_r_' + dag + '_opmerking') : '';
            return { s: s, v: vakken, o: opm, sleutel: s + '|' + JSON.stringify(vakken) + '|' + opm };
        });
        var groepen = [];
        dagen.forEach(function (d, i) {
            var g = groepen[groepen.length - 1];
            if (g && g.sleutel === d.sleutel && g.tot === i - 1) g.tot = i;
            else groepen.push({ sleutel: d.sleutel, van: i, tot: i, d: d });
        });
        var naam = function (i) { return DAGEN[i]; };
        var html = '<p class="mk-ot__kop">' + $('<div>').text(naamVan(rij)).html() + '</p><dl class="mk-ot__week">';
        groepen.forEach(function (g) {
            var eerste = naam(g.van).charAt(0).toUpperCase() + naam(g.van).slice(1);
            var label = g.van === g.tot ? eerste : (g.tot - g.van === 1 ? eerste + ' en ' + naam(g.tot) : eerste + ' t/m ' + naam(g.tot));
            var tijden = g.d.s === 'gesloten' ? ['Gesloten'] : (g.d.s === 'afspraak' ? ['Alleen op afspraak'] : (g.d.v.length ? g.d.v.map(vak) : ['Geopend']));
            html += '<div class="mk-ot__regel mk-ot__regel--' + g.d.s + '"><dt><span class="mk-ot__dag">' + label + '</span>'
                + (g.d.o ? '<span class="mk-ot__extra">' + $('<div>').text(g.d.o).html() + '</span>' : '')
                + '</dt><dd>' + tijden.map(function (t) { return '<span class="mk-ot__tijd">' + t + '</span>'; }).join('') + '</dd></div>';
        });
        frame.innerHTML = '<div class="mk-ot">' + html + '</dl></div>';
    }

    function verversWeek() {
        rijen().forEach(maakRij);
        kiesRooster(actief);
    }

    if (rep) {
        rep.classList.add('mk-ot-roosters');
        var wk = box('week');
        if (wk) wk.querySelector('.inside').appendChild(voorbeeld);
        verversWeek();
        roostersLijst();
        var googleVeld = document.querySelector('[data-key="field_mkbase_ot_google"] select');
        if (googleVeld) $(googleVeld).on('change', roostersLijst);

        rep.addEventListener('input', function (e) {
            if (e.target.closest('[data-key="field_mkbase_ot_r_naam"]')) {
                bouwKeuze();
                // Naam ook in de lijst bij Overzicht
                var rij = e.target.closest('.acf-row');
                var li = document.querySelector('.mk-ot-roosters li[data-mk-ot-rooster="' + rijen().indexOf(rij) + '"] .mk-ot-roosters__naam input');
                if (li && li !== document.activeElement) li.value = e.target.value;
            }
            var rij2 = e.target.closest('.acf-row');
            if (rij2 && e.target.closest('.mk-ot-tijd')) DAGEN.forEach(function (dag) { dagFout(rij2, dag); });
            werkVoorbeeldBij();
        });
        rep.addEventListener('change', function (e) {
            var rij = e.target.closest('.acf-row');
            if (rij) DAGEN.forEach(function (dag) { verversDag(rij, dag); });
            setTimeout(werkVoorbeeldBij, 0);
        });
        acf.addAction('append', function ($el) {
            if ($el.closest('[data-key="field_mkbase_ot_roosters"]').length) setTimeout(verversWeek, 0);
        });
    }

    // =========================================================
    // Feestdagen: Valt op en Op de website
    // =========================================================
    function pasen(j) {
        var a = j % 19, b = Math.floor(j / 100), c = j % 100, d = Math.floor(b / 4), e = b % 4, f = Math.floor((b + 8) / 25),
            g = Math.floor((b - f + 1) / 3), h = (19 * a + b - d - g + 15) % 30, i = Math.floor(c / 4), k = c % 4,
            l = (32 + 2 * e + 2 * i - h - k) % 7, m = Math.floor((a + 11 * h + 22 * l) / 451), n = h + l - 7 * m + 114;
        return new Date(j, Math.floor(n / 31) - 1, (n % 31) + 1);
    }
    function datumIn(wanneer, j) {
        if (wanneer.indexOf('pasen:') === 0) return plus(pasen(j), parseInt(wanneer.slice(6), 10));
        if (wanneer === 'koningsdag') { var k = new Date(j, 3, 27); if (k.getDay() === 0) k.setDate(26); return k; }
        var m = /^(\d{1,2})-(\d{1,2})$/.exec(wanneer);
        if (!m) return null;
        var d = new Date(j, m[2] - 1, m[1]);
        return d.getMonth() === m[2] - 1 ? d : null;
    }
    // Kort, zodat het label op één regel past: "Vanaf 2 dec op de website"
    var MND = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
    function opWebsite(van, tot) {
        if (tot < vandaag) return { tekst: 'Voorbij · niet meer op de website', soort: 'uit' };
        if (van <= zondag && tot >= weekStart) return { tekst: 'Deze week in het overzicht', soort: 'live' };
        if (van <= grens) return { tekst: 'Nu op de website', soort: 'verlopen' };
        var v = plus(van, -vooruit);
        return { tekst: 'Vanaf ' + v.getDate() + ' ' + MND[v.getMonth()] + (v.getFullYear() !== vandaag.getFullYear() ? ' ' + v.getFullYear() : '') + ' op de website', soort: 'uit' };
    }

    function feestdag(rij) {
        maakTijdvak(rij, 'field_mkbase_ot_fd', '', function () { return waarde(rij, 'field_mkbase_ot_fd_status') === 'afwijkend'; });
        var vak = rij.querySelector('[data-key="field_mkbase_ot_fd_valt"] .acf-input');
        if (!vak) return;
        var soort = waarde(rij, 'field_mkbase_ot_fd_feestdag');
        var wanneer = soort === 'eigen' ? waarde(rij, 'field_mkbase_ot_fd_datum') : (cfg.soorten || {})[soort];
        var d = wanneer ? datumIn(wanneer, vandaag.getFullYear()) : null;
        if (d && d < vandaag) d = datumIn(wanneer, vandaag.getFullYear() + 1);
        if (!d) { vak.innerHTML = '<span class="mk-hint">' + (soort === 'eigen' ? 'Vul een datum in' : '–') + '</span>'; return; }
        var w = opWebsite(d, d);
        vak.innerHTML = '<strong class="mk-ot-valt"></strong><span class="mk-pill mk-pill--' + w.soort + '"></span>';
        vak.querySelector('strong').textContent = kort(d);
        vak.querySelector('.mk-pill').textContent = w.tekst;
        vak.appendChild(meldingVak(rij, feestdagSleutel(rij), 'field_mkbase_ot_fd_geen_melding', false));
    }
    function alleFeestdagen() {
        document.querySelectorAll('[data-key="field_mkbase_ot_feestdagen"] .acf-row:not(.acf-clone)').forEach(feestdag);
    }
    // ── Melding bij een feestdag of afwijking (inc/openingstijden/meldingen.php) ──
    var KP = window.mkOtKoppeling || null;
    var STATUS = { live: 'live', gepland: 'gepland', verlopen: 'verlopen', uit: 'uit', leeg: 'zonder tekst', ontkoppeld: 'niet gekoppeld' };
    // Kenmerk van een feestdag: gelijk aan mkbase_ot_feestdag_sleutel() in PHP
    function feestdagSleutel(rij) {
        var soort = waarde(rij, 'field_mkbase_ot_fd_feestdag');
        if (soort && soort !== 'eigen') return 'f:' + soort;
        var m = /^(\d{1,2})-(\d{1,2})$/.exec(waarde(rij, 'field_mkbase_ot_fd_datum'));
        return m ? 'f:eigen-' + ('0' + m[1]).slice(-2) + '-' + ('0' + m[2]).slice(-2) : '';
    }
    function meldingVak(rij, sleutel, geenKey, voorbij) {
        var vak = el('span', 'mk-ot-melding');
        if (!KP || voorbij) return vak;
        var maak = function (label, hoofd) {
            var a = el('a', 'mk-btn mk-btn--sm' + (hoofd ? '' : ' mk-btn--secondary'));
            a.href = KP.meldingen + '&mk_ot_nieuw=' + encodeURIComponent(sleutel);
            a.textContent = label;
            return a;
        };
        var pil = function (tekst, soort) { var p = el('span', 'mk-pill mk-pill--' + soort); p.textContent = tekst; vak.appendChild(p); };
        if (!sleutel) {
            vak.appendChild(el('span', 'mk-hint')).textContent = 'Sla eerst op; daarna kun je er een melding aan koppelen.';
            return vak;
        }
        var eigen = KP.eigen[sleutel];
        var geen = rij.querySelector('[data-key="' + geenKey + '"] input[type=checkbox]');
        if (eigen) {
            pil('Eigen melding: ' + eigen.naam + (STATUS[eigen.status] ? ' · ' + STATUS[eigen.status] : ''), eigen.status === 'live' ? 'live' : 'gepland');
            var link = el('a', 'mk-btn mk-btn--sm mk-btn--secondary');
            link.href = KP.meldingen;
            link.textContent = 'Bekijken →';
            vak.appendChild(link);
        } else if (KP.auto && geen && !geen.checked) {
            pil('Automatische melding', 'live');
            vak.appendChild(maak('Aanpassen', false));
            var uit = el('button', 'mk-btn mk-btn--sm mk-btn--secondary', 'Geen melding');
            uit.type = 'button';
            uit.addEventListener('click', function () { geen.checked = true; geen.dispatchEvent(new Event('change', { bubbles: true })); });
            vak.appendChild(uit);
        } else {
            if (KP.auto && geen && geen.checked) {
                pil('Geen melding', 'uit');
                var weer = el('button', 'mk-btn mk-btn--sm mk-btn--secondary', 'Toch automatisch');
                weer.type = 'button';
                weer.addEventListener('click', function () { geen.checked = false; geen.dispatchEvent(new Event('change', { bubbles: true })); });
                vak.appendChild(weer);
            }
            vak.appendChild(maak('Melding maken', true));
        }
        return vak;
    }

    // Koprij boven de lijst: de labels één keer, met dezelfde ?-uitleg als de velden (mk-help.js zet
    // de ? vanzelf bij elk .acf-field met data-mk-help)
    function feestdagKop() {
        var veldFd = document.querySelector('[data-key="field_mkbase_ot_feestdagen"]');
        var tabel = veldFd && veldFd.querySelector(':scope > .acf-input > .acf-repeater > .acf-table');
        if (!tabel || veldFd.querySelector('.mk-ot-fd-kop')) return;
        veldFd.classList.add('mk-ot-feestdagen');
        var bron = function (key) { return veldFd.querySelector('.acf-row [data-key="' + key + '"]'); };
        var kop = el('div', 'mk-ot-fd-kop');
        [
            ['Feestdag', 'field_mkbase_ot_fd_feestdag', 'mk-ot-fd-kop__feestdag'],
            ['Valt op', '', ''],
            ['Status', 'field_mkbase_ot_fd_status', ''],
            ['Tijden', '', 'mk-ot-fd-kop__tijden'],
            ['Geldt voor', 'field_mkbase_ot_fd_voor', '', 'Niets aangevinkt: alle roosters']
        ].forEach(function (k) {
            var cel = el('div', 'acf-field ' + k[2]);
            var b = k[1] && bron(k[1]);
            if (b && b.dataset.mkHelp) { cel.dataset.mkHelp = b.dataset.mkHelp; if (b.dataset.mkDocs) cel.dataset.mkDocs = b.dataset.mkDocs; }
            var label = el('div', 'acf-label');
            label.appendChild(el('label')).textContent = k[0];
            if (k[3]) label.appendChild(el('small')).textContent = k[3];
            cel.appendChild(label);
            kop.appendChild(cel);
        });
        tabel.parentNode.insertBefore(kop, tabel);

        // Nederlandse feestdagen terugzetten: voegt de standaard-feestdagen toe die er niet (meer) staan
        var balk = el('div', 'mk-ot-fd-balk');
        var terug = el('button', 'mk-btn mk-btn--secondary mk-btn--sm', 'Nederlandse feestdagen terugzetten');
        terug.type = 'button';
        terug.addEventListener('click', function () {
            var f = acf.getField($(veldFd));
            var aanwezig = Array.prototype.map.call(veldFd.querySelectorAll('.acf-row:not(.acf-clone) [data-key="field_mkbase_ot_fd_feestdag"] select'), function (s) { return s.value; });
            var missend = (cfg.standaard || []).filter(function (s) { return aanwezig.indexOf(s) < 0; });
            missend.forEach(function (soort) {
                var $r = f && f.add ? f.add() : null;
                if ($r && $r.length) $r.find('[data-key="field_mkbase_ot_fd_feestdag"] select').val(soort).trigger('change');
            });
            setTimeout(alleFeestdagen, 50);
            if (window.mkToast) window.mkToast({
                title: missend.length ? missend.length + (missend.length === 1 ? ' feestdag' : ' feestdagen') + ' teruggezet' : 'Alle Nederlandse feestdagen staan er al',
                sub: missend.length ? 'Op Gesloten; sla op om ze te bewaren' : '', tone: 'neutral'
            });
        });
        balk.appendChild(terug);
        kop.parentNode.insertBefore(balk, kop);
    }

    var fdBox = box('feestdagen');
    if (fdBox) {
        feestdagKop();
        fdBox.addEventListener('input', function (e) { var r = e.target.closest('.acf-row'); if (r) feestdag(r); });
        fdBox.addEventListener('change', function (e) { var r = e.target.closest('.acf-row'); if (r) feestdag(r); });
        acf.addAction('append', function () { setTimeout(alleFeestdagen, 0); });
        alleFeestdagen();
    }

    // =========================================================
    // Afwijkende dagen: status per rij en overlap
    // =========================================================
    function afwData(rij) {
        var van = (rij.querySelector('[data-key="field_mkbase_ot_afw_van"] input[type=hidden]') || {}).value || '';
        var tot = (rij.querySelector('[data-key="field_mkbase_ot_afw_tot"] input[type=hidden]') || {}).value || '';
        var ymd = function (s) { return s && /^\d{8}$/.test(s) ? new Date(+s.slice(0, 4), +s.slice(4, 6) - 1, +s.slice(6, 8)) : null; };
        var v = ymd(van), t = ymd(tot) || v;
        var voor = Array.prototype.map.call(rij.querySelectorAll('[data-key="field_mkbase_ot_afw_voor"] input:checked'), function (i) { return i.value; });
        return { rij: rij, van: v, tot: t, voor: voor, naam: waarde(rij, 'field_mkbase_ot_afw_naam') || 'Afwijkende dag' };
    }
    // Elke afwijking is een kaart met een kop: sleepgreep, naam, wanneer, status op de website, verwijderen,
    // en daaronder de melding. Een voorbije afwijking staat ingeklapt.
    var afwVeld = document.querySelector('[data-key="field_mkbase_ot_afwijkingen"]');
    function wanneerTekst(a) {
        if (!a.van) return 'Nog geen datum';
        return +a.van === +a.tot ? kort(a.van) : kort(a.van) + ' t/m ' + kort(a.tot);
    }
    function verwijderAfwijking(rij, naam) {
        var doe = function () {
            var f = afwVeld && acf.getField($(afwVeld));
            if (f && f.remove) f.remove($(rij)); else $(rij).find('[data-event="remove-row"]').first().trigger('click');
            setTimeout(afwijkingen, 400);
        };
        if (!window.mkDialog) { if (window.confirm('"' + naam + '" verwijderen?')) doe(); return; }
        window.mkDialog({
            title: '"' + naam + '" verwijderen?',
            text: 'De afwijking verdwijnt pas echt na Opslaan. Een melding die hem volgt, staat daarna op "Niet gekoppeld".',
            buttons: [{ label: 'Verwijderen', variant: 'danger', onClick: doe }, { label: 'Annuleren', variant: 'secondary' }]
        });
    }
    function afwijkingen() {
        var lijst = Array.prototype.map.call(document.querySelectorAll('[data-key="field_mkbase_ot_afwijkingen"] .acf-row:not(.acf-clone)'), afwData);
        lijst.forEach(function (a, i) {
            maakTijdvak(a.rij, 'field_mkbase_ot_afw', a.naam, function () { return keuze(a.rij, 'field_mkbase_ot_afw_status') === 'afwijkend'; });
            var vak = a.rij.querySelector(':scope > .acf-fields');
            if (!vak) return;
            var totInp = a.rij.querySelector('[data-key="field_mkbase_ot_afw_tot"] input[type="text"]');
            if (totInp && !totInp.placeholder) totInp.placeholder = 'Leeg bij één dag';
            // ACF koppelt het label aan het verborgen veld, niet aan het zichtbare datumveld
            var vanInp = a.rij.querySelector('[data-key="field_mkbase_ot_afw_van"] input[type="text"]');
            if (vanInp) vanInp.setAttribute('aria-label', 'Datum');
            if (totInp) totInp.setAttribute('aria-label', 'Tot en met');
            var voorbij = !!a.van && a.tot < vandaag;
            a.rij.classList.toggle('is-voorbij', voorbij);

            var kop = vak.querySelector(':scope > .mk-ot-afw-kop');
            if (!kop) { kop = el('div', 'mk-ot-afw-kop'); vak.insertBefore(kop, vak.firstChild); }
            kop.innerHTML = '<span class="mk-ot-afw-kop__grip" aria-hidden="true"></span><h3 class="mk-ot-afw-kop__naam"></h3><span class="mk-ot-afw-kop__wanneer"></span>'
                + '<span class="mk-ot-afw-kop__rechts"></span>';
            kop.querySelector('h3').textContent = waarde(a.rij, 'field_mkbase_ot_afw_naam') || 'Nieuwe afwijking';
            kop.querySelector('.mk-ot-afw-kop__wanneer').textContent = wanneerTekst(a);
            var rechts = kop.querySelector('.mk-ot-afw-kop__rechts');
            if (a.van) {
                var w = opWebsite(a.van, a.tot);
                var pil = el('span', 'mk-pill mk-pill--' + w.soort);
                pil.textContent = w.tekst;
                rechts.appendChild(pil);
            }
            if (voorbij) {
                var open = el('button', 'mk-btn mk-btn--sm mk-btn--link', a.rij.classList.contains('is-open') ? 'Inklappen' : 'Bekijken');
                open.type = 'button';
                open.addEventListener('click', function () { a.rij.classList.toggle('is-open'); afwijkingen(); });
                rechts.appendChild(open);
            }
            var weg = el('button', 'mk-ot-afw-kop__weg', '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>');
            weg.type = 'button';
            weg.setAttribute('aria-label', '"' + (waarde(a.rij, 'field_mkbase_ot_afw_naam') || 'Afwijking') + '" verwijderen');
            weg.addEventListener('click', function () { verwijderAfwijking(a.rij, waarde(a.rij, 'field_mkbase_ot_afw_naam') || 'Afwijking'); });
            rechts.appendChild(weg);

            var id = waarde(a.rij, 'field_mkbase_ot_afw_id');
            var melding = meldingVak(a.rij, id ? 'a:' + id : '', 'field_mkbase_ot_afw_geen_melding', voorbij);
            if (melding.childNodes.length) {
                var mr = el('div', 'mk-ot-afw-kop__melding');
                mr.appendChild(el('span', 'mk-ot-afw-kop__label')).textContent = 'Melding op de website';
                mr.appendChild(melding);
                kop.appendChild(mr);
            }

            // Overlap met een andere afwijking voor (een deel van) dezelfde roosters (voorbij: maakt niet meer uit)
            var letop = vak.querySelector(':scope > .mk-ot-afw-let-op');
            var bots = voorbij || !a.van ? [] : lijst.filter(function (b, j) {
                if (j === i || !b.van || b.van > a.tot || b.tot < a.van) return false;
                return !a.voor.length || !b.voor.length || a.voor.some(function (r) { return b.voor.indexOf(r) > -1; });
            });
            a.rij.classList.toggle('heeft-let-op', bots.length > 0);
            if (bots.length) {
                if (!letop) { letop = el('p', 'mk-ot-afw-let-op'); letop.setAttribute('role', 'status'); kop.insertAdjacentElement('afterend', letop); }
                var boven = lijst.indexOf(bots[0]) < i;
                letop.textContent = 'Overlapt met "' + bots[0].naam + '". Op de dagen die ze delen, geldt ' + (boven ? '"' + bots[0].naam + '" (die staat hoger in de lijst)' : 'deze') + '. Sleep aan de puntjes om de volgorde te wijzigen.';
            } else if (letop) {
                letop.remove();
            }
        });
    }

    // Rechts naast de afwijkingen: alles wat de komende 120 dagen anders is, feestdagen en afwijkingen samen
    function tijdlijn() {
        var wk = box('afwijkend');
        if (!wk || wk.querySelector('.mk-ot-tijdlijn')) return;
        var aside = el('aside', 'mk-ot-tijdlijn');
        aside.appendChild(el('h3', 'mk-h2')).textContent = 'Komende 120 dagen';
        aside.appendChild(el('p', 'mk-hint')).textContent = 'Feestdagen en afwijkingen samen, op volgorde. Wat binnen ' + vooruit + ' dagen valt, staat al op de website. Bijgewerkt na Opslaan.';
        var ol = el('ol');
        (cfg.tijdlijn || []).forEach(function (d) {
            var li = el('li', d.web ? 'is-web' : '');
            li.appendChild(el('span', 'mk-ot-tijdlijn__datum')).textContent = d.datum;
            var t = el('span', 'mk-ot-tijdlijn__tekst');
            t.appendChild(el('strong')).textContent = d.naam;
            t.appendChild(el('span')).textContent = d.sub;
            li.appendChild(t);
            ol.appendChild(li);
        });
        if (!(cfg.tijdlijn || []).length) ol.appendChild(el('li', 'mk-hint')).textContent = 'Niets bijzonders in de komende 120 dagen.';
        aside.appendChild(ol);
        aside.appendChild(el('p', 'mk-ot-tijdlijn__uitleg', '<span class="is-web"></span>Op de website <span></span>Komt later'));
        wk.querySelector('.inside').appendChild(aside);
    }

    var afwBox = box('afwijkend');
    if (afwBox) {
        tijdlijn();
        afwBox.addEventListener('change', function () { setTimeout(afwijkingen, 0); });
        afwBox.addEventListener('input', function () { setTimeout(afwijkingen, 0); });
        acf.addAction('append', function () { setTimeout(afwijkingen, 0); });
        acf.addAction('sortstop', function () { setTimeout(afwijkingen, 0); });
        acf.addAction('remove', function () { setTimeout(afwijkingen, 400); });
        afwijkingen();
    }
})(jQuery);
