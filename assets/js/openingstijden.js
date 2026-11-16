/**
 * Openingstijden — "nu open" en "vandaag" in de browser (inc/openingstijden/output.php).
 * Rekent in de tijdzone van de website met de dagen die de server meegeeft, zodat het ook klopt als
 * de pagina uit de cache komt. Zelfde regels als mkbase_ot_status() in inc/openingstijden/data.php.
 * Werkt elke minuut bij zolang de pagina openstaat.
 */
(function () {
    var cfg = window.mkOt || {};
    var tekst = cfg.teksten || {};

    function fmt(s) {
        var args = Array.prototype.slice.call(arguments, 1), i = 0;
        return s.replace(/%(\d)\$s|%s/g, function (m, n) { return args[n ? n - 1 : i++]; });
    }

    // Datum en minuten "nu" in de tijdzone van de website. Kent de browser de tijdzone niet
    // (bijv. "+01:00"), dan met de afwijking van UTC die de server meegeeft.
    function nuIn(tz, off) {
        var d = new Date(), delen = {};
        try {
            new Intl.DateTimeFormat('en-CA', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
                .formatToParts(d).forEach(function (p) { delen[p.type] = p.value; });
        } catch (e) {
            var u = new Date(d.getTime() + (off || 0) * 60000);
            delen = { year: u.getUTCFullYear(), month: ('0' + (u.getUTCMonth() + 1)).slice(-2), day: ('0' + u.getUTCDate()).slice(-2), hour: u.getUTCHours(), minute: u.getUTCMinutes() };
        }
        return { datum: delen.year + '-' + delen.month + '-' + delen.day, min: parseInt(delen.hour, 10) % 24 * 60 + parseInt(delen.minute, 10) };
    }

    function plusDagen(datum, n) {
        var p = datum.split('-'), d = new Date(Date.UTC(+p[0], +p[1] - 1, +p[2] + n));
        return d.toISOString().slice(0, 10);
    }

    function dagIndex(datum) { // 0 = maandag
        var p = datum.split('-');
        return (new Date(Date.UTC(+p[0], +p[1] - 1, +p[2])).getUTCDay() + 6) % 7;
    }

    function minuten(t) { var p = t.split(':'); return +p[0] * 60 + +p[1]; }

    function tijd(t, notatie) {
        if (notatie === 'hh') return t;
        var kort = t.replace(/^0(\d)/, '$1');
        return notatie === 'uur' ? kort.replace(':', '.') + ' ' + cfg.uur : kort;
    }

    function vak(v, notatie) {
        if (notatie === 'uur') return tijd(v[0], 'h').replace(':', '.') + ' – ' + tijd(v[1], 'uur');
        return tijd(v[0], notatie) + ' – ' + tijd(v[1], notatie);
    }

    // "45 minuten", "1 minuut"
    function minutenTekst(m) { return m === 1 ? tekst.minuut : fmt(tekst.minuten, m); }

    // Hoe lang nog open: "nog 2 uur", "nog 1 uur en 15 min", "nog 45 minuten"
    function nogTekst(m) {
        if (m < 60) return fmt(tekst.nog, minutenTekst(m));
        var u = Math.floor(m / 60), r = m % 60;
        return fmt(tekst.nog, r < 10 ? fmt(tekst.uren, u) : fmt(tekst.urenMin, u, r));
    }

    function open(tot, nog, volgende, notatie) {
        // Het laatste halfuur: "Sluit over 25 minuten"
        if (nog <= 30) return { soort: 'bijna', tekst: fmt(tekst.sluitOver, minutenTekst(nog)), nog: '' };
        var t = fmt(tekst.open, tijd(tot, notatie));
        if (volgende) t += fmt(tekst.daarna, vak(volgende, notatie));
        return { soort: 'open', tekst: t, nog: nogTekst(nog) };
    }

    function status(data, nu) {
        var n = data.notatie, dagen = data.dagen;
        var gisteren = dagen[plusDagen(nu.datum, -1)];
        if (gisteren && gisteren.s === 'open') {
            for (var g = 0; g < gisteren.v.length; g++) {
                var gv = gisteren.v[g];
                if (minuten(gv[1]) < minuten(gv[0]) && nu.min < minuten(gv[1])) return open(gv[1], minuten(gv[1]) - nu.min, null, n);
            }
        }
        var dag = dagen[nu.datum];
        if (!dag) return null; // de pagina is te oud: laat staan wat de server gaf
        if (dag.s === 'afspraak') return { soort: 'afspraak', tekst: tekst.afspraak };
        if (dag.s === 'open') {
            for (var i = 0; i < dag.v.length; i++) {
                var van = minuten(dag.v[i][0]), tot = minuten(dag.v[i][1]);
                if (tot <= van) tot += 1440;
                if (nu.min >= van && nu.min < tot) return open(dag.v[i][1], tot - nu.min, dag.v[i + 1], n);
                if (nu.min < van) {
                    return i === 0
                        ? { soort: 'gesloten', tekst: fmt(tekst.straks, tijd(dag.v[i][0], n)) }
                        : { soort: 'pauze', tekst: fmt(tekst.pauze, tijd(dag.v[i][0], n)) };
                }
            }
            if (!dag.v.length) return { soort: 'open', tekst: tekst.geopend };
        }
        for (var d = 1; d <= 14; d++) {
            var datum = plusDagen(nu.datum, d), r = dagen[datum];
            if (!r) break;
            if (r.s !== 'open' || !r.v.length) continue;
            var om = tijd(r.v[0][0], n);
            return { soort: 'gesloten', tekst: d === 1 ? fmt(tekst.morgen, om) : fmt(tekst.later, cfg.dagen[dagIndex(datum)], om) };
        }
        return { soort: 'gesloten', tekst: tekst.gesloten };
    }

    function bijwerken(blok) {
        var data;
        try { data = JSON.parse(blok.getAttribute('data-mk-ot')); } catch (e) { return; }
        if (!data || !data.roosters) return;
        var nu = nuIn(data.tz, data.off);
        // De dagen van een rooster (per element via data-mk-ot-status / data-mk-ot-vandaag)
        var dagenVan = function (id) { return data.roosters[id] || data.roosters[Object.keys(data.roosters)[0]]; };

        blok.querySelectorAll('[data-mk-ot-status]').forEach(function (el) {
            var s = status({ dagen: dagenVan(el.getAttribute('data-mk-ot-status')), notatie: data.notatie }, nu);
            if (!s) return;
            el.className = 'mk-ot__status mk-ot__status--' + s.soort;
            el.querySelector('.mk-ot__status-tekst').textContent = s.tekst;
            // "nog 2 uur" als los stukje, zodat het child theme het rustiger kan opmaken
            var nog = el.querySelector('.mk-ot__status-nog');
            if (s.nog && !nog) {
                nog = document.createElement('span');
                nog.className = 'mk-ot__status-nog';
                el.appendChild(nog);
            }
            if (nog) {
                nog.textContent = s.nog ? ' · ' + s.nog : '';
                nog.hidden = !s.nog;
            }
        });

        // Tabjes: een stip per rooster (open, pauze, gesloten), net als de status
        blok.querySelectorAll('[data-mk-ot-tab]').forEach(function (tab) {
            var s = status({ dagen: dagenVan(tab.getAttribute('data-mk-ot-tab')), notatie: data.notatie }, nu);
            tab.className = 'mk-ot__tab' + (s ? ' mk-ot__tab--' + s.soort : '');
            if (s) tab.title = s.tekst;
        });

        // Komende wijziging: de eerste die nog niet voorbij is
        blok.querySelectorAll('.mk-ot__wijzigingen').forEach(function (lijst) {
            var getoond = false;
            lijst.querySelectorAll('.mk-ot__wijziging').forEach(function (w) {
                var toon = !getoond && w.getAttribute('data-mk-ot-tot') >= nu.datum;
                w.hidden = !toon;
                if (toon) getoond = true;
            });
            lijst.hidden = !getoond;
        });

        // Vandaag: hoe ver de dag is (van de eerste opening tot de laatste sluiting), voor een
        // voortgangsstreepje in de regel van vandaag (--mk-ot-voortgang, 0 tot 1; opmaak in het child theme)
        blok.querySelectorAll('.mk-ot__week[data-mk-ot-rooster]').forEach(function (week) {
            var dag = dagenVan(week.getAttribute('data-mk-ot-rooster'))[nu.datum];
            var regel = Array.prototype.filter.call(week.querySelectorAll('[data-mk-ot-dagen]'), function (r) {
                return r.getAttribute('data-mk-ot-dagen').split(',').indexOf(nu.datum) > -1;
            })[0];
            week.querySelectorAll('.mk-ot__regel').forEach(function (r) { if (r !== regel) r.style.removeProperty('--mk-ot-voortgang'); });
            if (!regel) return;
            var waarde = null;
            if (dag && dag.s === 'open' && dag.v.length) {
                var van = minuten(dag.v[0][0]), tot = minuten(dag.v[dag.v.length - 1][1]);
                if (tot <= van) tot += 1440;
                if (nu.min >= van && nu.min < tot) waarde = (nu.min - van) / (tot - van);
            }
            if (waarde === null) regel.style.removeProperty('--mk-ot-voortgang');
            else regel.style.setProperty('--mk-ot-voortgang', waarde.toFixed(3));
            regel.classList.toggle('is-nu-open', waarde !== null);
        });

        // Vandaag uitlichten in het weekoverzicht
        if (blok.classList.contains('mk-ot--vandaag-uitlichten')) {
            blok.querySelectorAll('.mk-ot__week [data-mk-ot-dagen], .mk-ot__tabel [data-mk-ot-dagen]').forEach(function (regel) {
                var is = regel.getAttribute('data-mk-ot-dagen').split(',').indexOf(nu.datum) > -1;
                regel.classList.toggle('is-vandaag', is);
                var label = regel.querySelector('.mk-ot__label');
                if (is && !label) {
                    label = document.createElement('span');
                    label.className = 'mk-ot__label';
                    label.textContent = cfg.vandaag;
                    regel.querySelector('dt, th').appendChild(label);
                } else if (!is && label) {
                    label.remove();
                }
            });
        }

        // Weergave Vandaag (een regel per rooster; de naam ervoor blijft staan)
        blok.querySelectorAll('[data-mk-ot-vandaag]').forEach(function (el) {
            var dag = dagenVan(el.getAttribute('data-mk-ot-vandaag'))[nu.datum];
            var vak2 = el.querySelector('.mk-ot__vandaag-inhoud');
            if (!dag || !vak2) return;
            var label = dag.s === 'gesloten' ? tekst.vandaagDicht : (dag.s === 'afspraak' ? tekst.vandaagAfspr : tekst.vandaagOpen);
            var html = '<span class="mk-ot__vandaag-label"></span>';
            if (dag.n) html += ' <span class="mk-ot__extra"></span>';
            if (dag.s === 'open' && dag.v.length) html += ' <span class="mk-ot__vandaag-tijd"></span>';
            vak2.innerHTML = html;
            vak2.querySelector('.mk-ot__vandaag-label').textContent = label;
            if (dag.n) vak2.querySelector('.mk-ot__extra').textContent = dag.n;
            var t = vak2.querySelector('.mk-ot__vandaag-tijd');
            if (t) t.textContent = dag.v.map(function (v) { return vak(v, data.notatie); }).join(' ' + cfg.en + ' ');
        });
    }

    // ── Tabjes (meerdere roosters, indeling "Tabjes") ──
    // Zonder JavaScript staan de roosters onder elkaar; hier worden het tabjes (WAI-ARIA tabs:
    // pijltjes, Home en End wisselen, alleen het actieve tabje zit in de tabvolgorde).
    function kies(blok, tab, focus) {
        blok.querySelectorAll('[data-mk-ot-tab]').forEach(function (t) {
            var aan = t === tab;
            t.setAttribute('aria-selected', aan ? 'true' : 'false');
            t.tabIndex = aan ? 0 : -1;
            var paneel = document.getElementById(t.getAttribute('aria-controls'));
            if (paneel) paneel.hidden = !aan;
        });
        if (focus) tab.focus();
    }

    function tabjes(blok) {
        if (blok.classList.contains('is-tabs')) return;
        var knoppen = Array.prototype.slice.call(blok.querySelectorAll('[data-mk-ot-tab]'));
        if (!knoppen.length) return;
        blok.classList.add('is-tabs');
        // Begin bij het rooster dat nu open is, anders bij het eerste
        var open = knoppen.filter(function (t) { return /mk-ot__tab--(open|bijna)/.test(t.className); })[0];
        kies(blok, open || knoppen[0], false);
        knoppen.forEach(function (t, i) {
            t.addEventListener('click', function () { kies(blok, t, false); });
            t.addEventListener('keydown', function (e) {
                var naar = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: knoppen.length - 1 }[e.key];
                if (naar === undefined) return;
                e.preventDefault();
                kies(blok, knoppen[(naar + knoppen.length) % knoppen.length], true);
            });
        });
    }

    function alles() {
        document.querySelectorAll('[data-mk-ot]').forEach(function (blok) {
            bijwerken(blok);
            if (blok.classList.contains('mk-ot--tabs')) tabjes(blok);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', alles);
    else alles();
    setInterval(alles, 60000);
    window.mkOtBijwerken = alles;
    // In de blokeditor: na elke nieuwe weergave van het blok
    if (window.acf && window.acf.addAction) window.acf.addAction('render_block_preview', function () { setTimeout(alles, 0); });
})();
