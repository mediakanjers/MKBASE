<?php
    // =============================================================================
    // Openingstijden — gegevens en "wat geldt er op een dag" (sinds 1.7.0)
    // =============================================================================
    // Beheer: Mediakanjers → Openingstijden (fields.php, admin.php). Tonen: output.php.
    // Per dag en per rooster geldt: eerst een afwijkende dag, dan een feestdag, dan de vaste week.
    // Alles heet mkbase_ot_*, zodat een eigen openingstijden-module in een child theme (zoals die
    // van Fasttech, mk_openingstijden_*) er los naast kan bestaan. Uitzetten per site:
    // add_filter('mkbase_openingstijden_actief', '__return_false');

    function mkbase_ot_actief() {
        return (bool) apply_filters('mkbase_openingstijden_actief', true);
    }

    function mkbase_ot_dagen() {
        return ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
    }

    // Weergavenamen (vertaalbaar); index 0 = maandag
    function mkbase_ot_dagnamen($kort = false) {
        return $kort
            ? [__('ma', 'mk'), __('di', 'mk'), __('wo', 'mk'), __('do', 'mk'), __('vr', 'mk'), __('za', 'mk'), __('zo', 'mk')]
            : [__('maandag', 'mk'), __('dinsdag', 'mk'), __('woensdag', 'mk'), __('donderdag', 'mk'), __('vrijdag', 'mk'), __('zaterdag', 'mk'), __('zondag', 'mk')];
    }

    function mkbase_ot_maanden() {
        return [__('januari', 'mk'), __('februari', 'mk'), __('maart', 'mk'), __('april', 'mk'), __('mei', 'mk'), __('juni', 'mk'),
            __('juli', 'mk'), __('augustus', 'mk'), __('september', 'mk'), __('oktober', 'mk'), __('november', 'mk'), __('december', 'mk')];
    }

    // Is de pagina ooit opgeslagen? Zo niet, dan toont de website nog niets.
    function mkbase_ot_ingevuld() {
        return get_option('options_mkbase_ot_roosters', null) !== null;
    }

    function mkbase_ot_vandaag() {
        return new DateTimeImmutable('today', wp_timezone());
    }

    function mkbase_ot_nu() {
        return new DateTimeImmutable('now', wp_timezone());
    }

    // Maandag van de week waarin $dag valt
    function mkbase_ot_week_start($dag) {
        return $dag->setTime(0, 0)->modify('-' . ((int) $dag->format('N') - 1) . ' days');
    }

    function mkbase_ot_vooruit() {
        return max(1, min(365, (int) (get_field('mkbase_ot_vooruit', 'option') ?: 30)));
    }

    // 'hh' = 08:30, 'h' = 8:30, 'uur' = 8.30 uur
    function mkbase_ot_notatie() {
        $n = get_field('mkbase_ot_notatie', 'option');
        return in_array($n, ['hh', 'h', 'uur'], true) ? $n : 'hh';
    }

    // '8:30', '08.30' of '0830' → '08:30'; ongeldig → ''
    function mkbase_ot_tijd($waarde) {
        $waarde = trim((string) $waarde);
        if (!preg_match('/^(\d{1,2})[:.]?(\d{2})$/', $waarde, $m)) return '';
        $uur = (int) $m[1];
        $min = (int) $m[2];
        if ($uur > 24 || $min > 59 || ($uur === 24 && $min > 0)) return '';
        return sprintf('%02d:%02d', $uur === 24 ? 0 : $uur, $min);
    }

    function mkbase_ot_minuten($tijd) {
        [$u, $m] = array_map('intval', explode(':', $tijd));
        return $u * 60 + $m;
    }

    // Maximaal twee tijdvakken: [['08:30', '12:00'], ['13:00', '17:30']]
    function mkbase_ot_vakken($rij, $prefix = '') {
        $vakken = [];
        foreach ([1, 2] as $n) {
            $van = mkbase_ot_tijd($rij[$prefix . 'van' . $n] ?? '');
            $tot = mkbase_ot_tijd($rij[$prefix . 'tot' . $n] ?? '');
            if ($van !== '' && $tot !== '') $vakken[] = [$van, $tot];
        }
        return $vakken;
    }

    // =============================================================================
    // Roosters en vaste week
    // =============================================================================

    function mkbase_ot_week_standaard($i) {
        return $i < 5 ? 'open' : 'gesloten';
    }

    // [id => ['id', 'naam', 'week' => [0..6 => ['status', 'vakken', 'opmerking']]]], in de volgorde van het beheer
    function mkbase_ot_roosters() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = [];
        if (!function_exists('get_field')) return $cache;

        foreach ((array) get_field('mkbase_ot_roosters', 'option') as $i => $rij) {
            if (!is_array($rij)) continue;
            $id = sanitize_key($rij['id'] ?? '') ?: 'rooster-' . ($i + 1);
            $week = [];
            foreach (mkbase_ot_dagen() as $d => $dag) {
                $status = $rij[$dag . '_status'] ?? '';
                if (!in_array($status, ['open', 'afspraak', 'gesloten'], true)) $status = mkbase_ot_week_standaard($d);
                $week[$d] = [
                    'status'    => $status,
                    'vakken'    => $status === 'open' ? mkbase_ot_vakken($rij, $dag . '_') : [],
                    'opmerking' => $status === 'gesloten' ? '' : trim((string) ($rij[$dag . '_opmerking'] ?? '')),
                ];
            }
            $cache[$id] = [
                'id'   => $id,
                'naam' => trim((string) ($rij['naam'] ?? '')) ?: __('Openingstijden', 'mk'),
                'week' => $week,
            ];
        }
        return $cache;
    }

    // Het gekozen rooster, of het eerste als er niets (meer) gekozen is
    function mkbase_ot_rooster($id = '') {
        $roosters = mkbase_ot_roosters();
        if ($id !== '' && isset($roosters[$id])) return $roosters[$id];
        return $roosters ? reset($roosters) : null;
    }

    // Geldt een feestdag of afwijking voor dit rooster? Leeg = voor alle roosters.
    function mkbase_ot_geldt($voor, $rooster_id) {
        $voor = array_filter((array) $voor);
        return !$voor || in_array($rooster_id, $voor, true);
    }

    // =============================================================================
    // Feestdagen
    // =============================================================================

    // [naam, wanneer]: een vaste datum (dd-mm), een dag rond Pasen (pasen:dagen na eerste paasdag)
    // of koningsdag (27 april, op zondag de zaterdag ervoor)
    function mkbase_ot_feestdag_soorten() {
        return [
            'nieuwjaarsdag'      => [__('Nieuwjaarsdag', 'mk'), '01-01'],
            'goede_vrijdag'      => [__('Goede Vrijdag', 'mk'), 'pasen:-2'],
            'eerste_paasdag'     => [__('Eerste paasdag', 'mk'), 'pasen:0'],
            'tweede_paasdag'     => [__('Tweede paasdag', 'mk'), 'pasen:1'],
            'koningsdag'         => [__('Koningsdag', 'mk'), 'koningsdag'],
            'bevrijdingsdag'     => [__('Bevrijdingsdag', 'mk'), '05-05'],
            'hemelvaartsdag'     => [__('Hemelvaartsdag', 'mk'), 'pasen:39'],
            'eerste_pinksterdag' => [__('Eerste pinksterdag', 'mk'), 'pasen:49'],
            'tweede_pinksterdag' => [__('Tweede pinksterdag', 'mk'), 'pasen:50'],
            'kerstavond'         => [__('Kerstavond', 'mk'), '24-12'],
            'eerste_kerstdag'    => [__('Eerste kerstdag', 'mk'), '25-12'],
            'tweede_kerstdag'    => [__('Tweede kerstdag', 'mk'), '26-12'],
            'oudejaarsdag'       => [__('Oudejaarsdag', 'mk'), '31-12'],
        ];
    }

    // De gangbare Nederlandse feestdagen, zolang de lijst nog nooit is opgeslagen
    function mkbase_ot_feestdagen_standaard() {
        return ['nieuwjaarsdag', 'eerste_paasdag', 'tweede_paasdag', 'koningsdag', 'hemelvaartsdag', 'eerste_pinksterdag', 'tweede_pinksterdag', 'eerste_kerstdag', 'tweede_kerstdag'];
    }

    function mkbase_ot_feestdagen_lijst() {
        if (get_option('options_mkbase_ot_feestdagen', null) === null) {
            return array_map(function ($soort) { return ['feestdag' => $soort, 'status' => 'gesloten']; }, mkbase_ot_feestdagen_standaard());
        }
        return array_filter((array) get_field('mkbase_ot_feestdagen', 'option'), 'is_array');
    }

    // [naam, wanneer] van een regel: uit de vaste lijst, of zelf ingevuld bij Eigen feestdag
    function mkbase_ot_feestdag_info($rij) {
        $soorten = mkbase_ot_feestdag_soorten();
        $soort   = $rij['feestdag'] ?? 'eigen';
        if (isset($soorten[$soort])) return $soorten[$soort];
        return [trim((string) ($rij['naam'] ?? '')) ?: __('Feestdag', 'mk'), trim((string) ($rij['datum'] ?? ''))];
    }

    // Vast kenmerk van een feestdag voor de koppeling met Meldingen: de soort ('f:eerste_kerstdag'),
    // of bij een eigen feestdag de datum ('f:eigen-14-09'). Gelijk in assets/js/admin-openingstijden.js.
    function mkbase_ot_feestdag_sleutel($rij) {
        $soort = $rij['feestdag'] ?? 'eigen';
        if ($soort !== 'eigen') return 'f:' . sanitize_key($soort);
        $datum = trim((string) ($rij['datum'] ?? ''));
        return preg_match('/^(\d{1,2})-(\d{1,2})$/', $datum, $m) ? sprintf('f:eigen-%02d-%02d', $m[1], $m[2]) : '';
    }

    // Eerste paasdag (anonieme Gregoriaanse berekening, zonder de calendar-extensie)
    function mkbase_ot_pasen($jaar) {
        $a = $jaar % 19; $b = intdiv($jaar, 100); $c = $jaar % 100;
        $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30; $i = intdiv($c, 4); $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $n = $h + $l - 7 * $m + 114;
        return new DateTimeImmutable(sprintf('%d-%02d-%02d', $jaar, intdiv($n, 31), ($n % 31) + 1), wp_timezone());
    }

    // De datum van een "wanneer" in een jaar, of null
    function mkbase_ot_feestdag_datum($wanneer, $jaar) {
        if (strpos($wanneer, 'pasen:') === 0) {
            return mkbase_ot_pasen($jaar)->modify(((int) substr($wanneer, 6)) . ' days');
        }
        if ($wanneer === 'koningsdag') {
            $dag = new DateTimeImmutable("$jaar-04-27", wp_timezone());
            return $dag->format('N') === '7' ? $dag->modify('-1 day') : $dag;
        }
        if (!preg_match('/^(\d{1,2})-(\d{1,2})$/', $wanneer, $m) || !checkdate((int) $m[2], (int) $m[1], $jaar)) return null;
        return new DateTimeImmutable(sprintf('%d-%02d-%02d', $jaar, $m[2], $m[1]), wp_timezone());
    }

    // ['2026-12-25' => ['naam', 'status', 'vakken', 'voor'], ...]
    function mkbase_ot_feestdagen_van($jaar) {
        static $cache = [];
        if (isset($cache[$jaar])) return $cache[$jaar];
        $uit = [];
        foreach (mkbase_ot_feestdagen_lijst() as $rij) {
            [$naam, $wanneer] = mkbase_ot_feestdag_info($rij);
            $dag = mkbase_ot_feestdag_datum($wanneer, $jaar);
            if (!$dag) continue;
            $status = in_array($rij['status'] ?? '', ['gesloten', 'afwijkend', 'afspraak'], true) ? $rij['status'] : 'gesloten';
            $uit[$dag->format('Y-m-d')] = [
                'naam'         => $naam,
                'status'       => $status,
                'vakken'       => $status === 'afwijkend' ? mkbase_ot_vakken($rij) : [],
                'voor'         => array_values(array_filter((array) ($rij['voor'] ?? []))),
                'sleutel'      => mkbase_ot_feestdag_sleutel($rij),
                'geen_melding' => !empty($rij['geen_melding']),
            ];
        }
        return $cache[$jaar] = $uit;
    }

    // =============================================================================
    // Afwijkende dagen en periodes
    // =============================================================================

    // Alle afwijkingen, in de volgorde van het beheer (bij overlap geldt de bovenste)
    function mkbase_ot_afwijkingen_alle() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = [];
        foreach ((array) get_field('mkbase_ot_afwijkingen', 'option') as $i => $rij) {
            if (!is_array($rij) || empty($rij['van'])) continue;
            try {
                $van = new DateTimeImmutable($rij['van'], wp_timezone());
                $tot = !empty($rij['tot']) ? new DateTimeImmutable($rij['tot'], wp_timezone()) : $van;
            } catch (Exception $e) {
                continue;
            }
            if ($tot < $van) [$van, $tot] = [$tot, $van];
            $status = in_array($rij['status'] ?? '', ['gesloten', 'afwijkend', 'afspraak', 'open'], true) ? $rij['status'] : 'gesloten';
            $cache[] = [
                'naam'        => trim((string) ($rij['naam'] ?? '')) ?: __('Afwijkende openingstijden', 'mk'),
                'van'         => $van->setTime(0, 0),
                'tot'         => $tot->setTime(0, 0),
                'status'      => $status,
                'vakken'      => $status === 'afwijkend' ? mkbase_ot_vakken($rij) : [],
                'voor'        => array_values(array_filter((array) ($rij['voor'] ?? []))),
                'toelichting' => trim((string) ($rij['toelichting'] ?? '')),
                // Kenmerk voor de koppeling met Meldingen; nog niet opgeslagen: alleen voor automatische meldingen
                'sleutel'     => !empty($rij['id']) ? 'a:' . sanitize_key($rij['id']) : 'a:rij-' . $i,
                'vast'        => !empty($rij['id']),
                'geen_melding' => !empty($rij['geen_melding']),
            ];
        }
        return $cache;
    }

    // =============================================================================
    // Koppeling met Meldingen: feestdagen en afwijkingen als "bron"
    // =============================================================================

    // Alle feestdagen en afwijkingen waar een melding aan kan hangen, met hun eerstvolgende of lopende
    // periode: ['f:eerste_kerstdag' => ['sleutel', 'soort', 'naam', 'van', 'tot', 'status', 'vakken',
    // 'voor', 'toelichting', 'geen_melding', 'vast'], ...]. Een feestdag die dit jaar al voorbij is, telt
    // voor volgend jaar; een afwijking houdt zijn eigen datums (ook als hij voorbij is).
    function mkbase_ot_bronnen($vandaag = null) {
        static $cache = [];
        $vandaag = $vandaag ?: mkbase_ot_vandaag();
        $k = $vandaag->format('Ymd');
        if (isset($cache[$k])) return $cache[$k];
        $uit = [];
        foreach (mkbase_ot_afwijkingen_alle() as $a) {
            $uit[$a['sleutel']] = [
                'sleutel' => $a['sleutel'], 'soort' => 'afwijking', 'naam' => $a['naam'], 'van' => $a['van'], 'tot' => $a['tot'],
                'status' => $a['status'], 'vakken' => $a['vakken'], 'voor' => $a['voor'], 'toelichting' => $a['toelichting'],
                'geen_melding' => $a['geen_melding'], 'vast' => $a['vast'],
            ];
        }
        $jaar = (int) $vandaag->format('Y');
        foreach ([$jaar, $jaar + 1] as $j) {
            foreach (mkbase_ot_feestdagen_van($j) as $datum => $f) {
                if (!$f['sleutel'] || isset($uit[$f['sleutel']])) continue;
                $dag = new DateTimeImmutable($datum, wp_timezone());
                if ($dag < $vandaag) continue;
                $uit[$f['sleutel']] = [
                    'sleutel' => $f['sleutel'], 'soort' => 'feestdag', 'naam' => $f['naam'], 'van' => $dag, 'tot' => $dag,
                    'status' => $f['status'], 'vakken' => $f['vakken'], 'voor' => $f['voor'], 'toelichting' => '',
                    'geen_melding' => $f['geen_melding'], 'vast' => true,
                ];
            }
        }
        uasort($uit, function ($x, $y) { return $x['van'] <=> $y['van']; });
        return $cache[$k] = $uit;
    }

    function mkbase_ot_bron($sleutel, $vandaag = null) {
        return mkbase_ot_bronnen($vandaag)[(string) $sleutel] ?? null;
    }

    // Wanneer een melding bij deze bron zichtbaar is: $vooraf dagen voor de eerste dag tot en met de laatste.
    // ['start' => 'Y-m-d 00:00:00', 'eind' => 'Y-m-d 23:59:59'] (zelfde notatie als de planningsvelden)
    function mkbase_ot_bron_periode($bron, $vooraf = null) {
        $vooraf = $vooraf === null || $vooraf === '' ? mkbase_ot_vooruit() : max(0, (int) $vooraf);
        return [
            'start' => $bron['van']->modify("-$vooraf days")->format('Y-m-d 00:00:00'),
            'eind'  => $bron['tot']->format('Y-m-d 23:59:59'),
        ];
    }

    // "van dinsdag 1 tot en met donderdag 31 december" of "op vrijdag 25 december"
    function mkbase_ot_datum_zin($van, $tot) {
        $dagen = mkbase_ot_dagnamen();
        $maanden = mkbase_ot_maanden();
        $d = function ($dag, $met_maand = true) use ($dagen, $maanden) {
            $t = $dagen[(int) $dag->format('N') - 1] . ' ' . $dag->format('j');
            if ($met_maand) $t .= ' ' . $maanden[(int) $dag->format('n') - 1];
            if ($met_maand && $dag->format('Y') !== mkbase_ot_vandaag()->format('Y')) $t .= ' ' . $dag->format('Y');
            return $t;
        };
        if ($van == $tot) return sprintf(__('op %s', 'mk'), $d($van));
        $zelfde_maand = $van->format('Ym') === $tot->format('Ym');
        return sprintf(__('van %1$s tot en met %2$s', 'mk'), $d($van, !$zelfde_maand), $d($tot));
    }

    // De namen van de roosters waarvoor iets geldt ("Winkel en Werkplaats"); leeg = alle roosters
    function mkbase_ot_rooster_namen($voor) {
        $alle  = mkbase_ot_roosters();
        $namen = [];
        foreach ($voor ? $voor : array_keys($alle) as $id) if (isset($alle[$id])) $namen[] = $alle[$id]['naam'];
        if (count($namen) < 2) return implode('', $namen);
        $laatste = array_pop($namen);
        return implode(', ', $namen) . ' ' . __('en', 'mk') . ' ' . $laatste;
    }

    // Invullers in de tekst van een melding: {naam} {datum} {tijden} {rooster} {toelichting}
    function mkbase_ot_invullers($bron) {
        $tijden = $bron['status'] === 'gesloten' ? __('gesloten', 'mk')
            : ($bron['status'] === 'afspraak' ? __('alleen op afspraak open', 'mk')
            : ($bron['status'] === 'open' ? __('gewoon open', 'mk')
            : ($bron['vakken']
                ? __('open', 'mk') . ' ' . implode(' ' . __('en', 'mk') . ' ', array_map(function ($v) {
                    return sprintf(__('van %1$s tot %2$s', 'mk'), mkbase_ot_tijd_tekst($v[0]), mkbase_ot_tijd_tekst($v[1]));
                }, $bron['vakken']))
                : __('open', 'mk'))));
        return [
            '{naam}'        => $bron['naam'],
            '{datum}'       => mkbase_ot_datum_zin($bron['van'], $bron['tot']),
            '{tijden}'      => $tijden,
            '{rooster}'     => mkbase_ot_rooster_namen($bron['voor']),
            '{toelichting}' => $bron['toelichting'],
        ];
    }

    // $html: de tekst is HTML (de editor van een melding), dus de waarden veilig invoegen
    function mkbase_ot_vul_in($tekst, $bron, $html = false) {
        $waarden = mkbase_ot_invullers($bron);
        if ($html) $waarden = array_map('esc_html', $waarden);
        $tekst = strtr((string) $tekst, $waarden);
        // Lege invullers laten geen dubbele spaties of " ." achter
        return trim(preg_replace(['/ {2,}/', '/ ([.,!?])/', '/\(\)/'], [' ', '$1', ''], $tekst));
    }

    // De standaardtekst van een automatische melding (ook voor "Melding maken")
    function mkbase_ot_standaard_tekst() {
        $t = trim((string) get_field('mkbase_ot_auto_tekst', 'option'));
        return $t !== '' ? $t : __('Let op: {datum} zijn wij {tijden} ({naam}). {toelichting}', 'mk');
    }

    // =============================================================================
    // Wat geldt er op een dag
    // =============================================================================

    // ['status' => open|afspraak|gesloten, 'vakken', 'naam', 'soort' => week|feestdag|afwijking, 'opmerking', 'toelichting']
    function mkbase_ot_dag($rooster, $dag) {
        $datum  = $dag->format('Y-m-d');
        $week   = $rooster['week'][(int) $dag->format('N') - 1];
        $gewoon = false;

        foreach (mkbase_ot_afwijkingen_alle() as $a) {
            if (!mkbase_ot_geldt($a['voor'], $rooster['id'])) continue;
            if ($datum < $a['van']->format('Y-m-d') || $datum > $a['tot']->format('Y-m-d')) continue;
            // Gewoon open: de vaste week, ook als het een feestdag is
            if ($a['status'] === 'open') { $gewoon = true; break; }
            return [
                'status'      => $a['status'] === 'afwijkend' ? 'open' : $a['status'],
                'vakken'      => $a['vakken'],
                'naam'        => $a['naam'],
                'soort'       => 'afwijking',
                'opmerking'   => '',
                'toelichting' => $a['toelichting'],
            ];
        }

        if (!$gewoon) {
            $f = mkbase_ot_feestdagen_van((int) $dag->format('Y'))[$datum] ?? null;
            if ($f && mkbase_ot_geldt($f['voor'], $rooster['id'])) {
                return [
                    'status'      => $f['status'] === 'afwijkend' ? 'open' : $f['status'],
                    'vakken'      => $f['vakken'],
                    'naam'        => $f['naam'],
                    'soort'       => 'feestdag',
                    'opmerking'   => '',
                    'toelichting' => '',
                ];
            }
        }
        return $week + ['naam' => '', 'soort' => 'week', 'toelichting' => ''];
    }

    // Feestdagen en afwijkingen voor de lijst "Afwijkende openingstijden": alles wat op of na $vanaf
    // nog loopt en binnen het ingestelde aantal dagen begint. Met $na alleen wat na die dag doorloopt
    // (in het weekoverzicht staan de dagen van deze week al op hun eigen regel).
    function mkbase_ot_afwijkingen_lijst($rooster, $vanaf, $na = null) {
        $grens = $vanaf->modify('+' . mkbase_ot_vooruit() . ' days');
        $lijst = [];
        $bezet = [];

        foreach (mkbase_ot_afwijkingen_alle() as $a) {
            if (!mkbase_ot_geldt($a['voor'], $rooster['id'])) continue;
            for ($d = $a['van']; $d <= $a['tot']; $d = $d->modify('+1 day')) $bezet[$d->format('Y-m-d')] = true;
            if ($a['tot'] < $vanaf || $a['van'] > $grens || ($na && $a['tot'] <= $na)) continue;
            $item = $a + ['soort' => 'afwijking'];
            if ($a['status'] === 'open' && $a['van'] == $a['tot']) {
                $w = $rooster['week'][(int) $a['van']->format('N') - 1];
                $item['status'] = $w['status'];
                $item['vakken'] = $w['vakken'];
            } elseif ($a['status'] === 'afwijkend') {
                $item['status'] = 'open';
            }
            $lijst[] = $item;
        }

        foreach ([(int) $vanaf->format('Y'), (int) $vanaf->format('Y') + 1] as $jaar) {
            foreach (mkbase_ot_feestdagen_van($jaar) as $datum => $f) {
                if (isset($bezet[$datum]) || !mkbase_ot_geldt($f['voor'], $rooster['id'])) continue;
                $dag = new DateTimeImmutable($datum, wp_timezone());
                if ($dag < $vanaf || $dag > $grens || ($na && $dag <= $na)) continue;
                // Op een dag die toch al dicht is, hoeft een gesloten feestdag niet in het lijstje
                if ($f['status'] === 'gesloten' && $rooster['week'][(int) $dag->format('N') - 1]['status'] === 'gesloten') continue;
                $lijst[] = [
                    'naam'        => $f['naam'],
                    'van'         => $dag,
                    'tot'         => $dag,
                    'status'      => $f['status'] === 'afwijkend' ? 'open' : $f['status'],
                    'vakken'      => $f['vakken'],
                    'toelichting' => '',
                    'soort'       => 'feestdag',
                ];
            }
        }

        usort($lijst, function ($a, $b) { return $a['van'] <=> $b['van']; });
        return $lijst;
    }

    // =============================================================================
    // Tekst
    // =============================================================================

    function mkbase_ot_tijd_tekst($tijd, $notatie = null) {
        $notatie = $notatie ?: mkbase_ot_notatie();
        if ($notatie === 'hh') return $tijd;
        $kort = ltrim(substr($tijd, 0, 2), '0');
        $kort = ($kort === '' ? '0' : $kort) . substr($tijd, 2);
        return $notatie === 'uur' ? str_replace(':', '.', $kort) . ' ' . __('uur', 'mk') : $kort;
    }

    function mkbase_ot_vak_tekst($vak, $notatie = null) {
        $notatie = $notatie ?: mkbase_ot_notatie();
        if ($notatie === 'uur') {
            return str_replace(' ' . __('uur', 'mk'), '', mkbase_ot_tijd_tekst($vak[0], 'uur')) . ' – ' . mkbase_ot_tijd_tekst($vak[1], 'uur');
        }
        return mkbase_ot_tijd_tekst($vak[0], $notatie) . ' – ' . mkbase_ot_tijd_tekst($vak[1], $notatie);
    }

    // De tijden van een dag als losse regels: ['08:30 – 12:00', '13:00 – 17:30'] of ['Gesloten']
    function mkbase_ot_tijden($status, $vakken, $notatie = null) {
        if ($status === 'gesloten') return [__('Gesloten', 'mk')];
        if ($status === 'afspraak') return [__('Alleen op afspraak', 'mk')];
        if (!$vakken) return [__('Geopend', 'mk')];
        return array_map(function ($v) use ($notatie) { return mkbase_ot_vak_tekst($v, $notatie); }, $vakken);
    }

    // "ma 27 april", met het jaar erbij als het niet dit jaar is
    function mkbase_ot_datum_kort($dag) {
        $tekst = mkbase_ot_dagnamen(true)[(int) $dag->format('N') - 1] . ' ' . $dag->format('j') . ' ' . mkbase_ot_maanden()[(int) $dag->format('n') - 1];
        if ($dag->format('Y') !== mkbase_ot_vandaag()->format('Y')) $tekst .= ' ' . $dag->format('Y');
        return $tekst;
    }

    // "Maandag 6 april"
    function mkbase_ot_datum_lang($dag) {
        return ucfirst(mkbase_ot_dagnamen()[(int) $dag->format('N') - 1]) . ' ' . $dag->format('j') . ' ' . mkbase_ot_maanden()[(int) $dag->format('n') - 1];
    }

    // =============================================================================
    // Nu open? (dezelfde regels als assets/js/openingstijden.js, voor wie geen JavaScript heeft
    // en voor de tegel in het beheer)
    // =============================================================================

    // ['soort' => open|bijna|pauze|gesloten|afspraak, 'tekst']
    function mkbase_ot_status($rooster, $nu = null) {
        $nu      = $nu ?: mkbase_ot_nu();
        $vandaag = $nu->setTime(0, 0);
        $min     = (int) $nu->format('G') * 60 + (int) $nu->format('i');
        $t       = function ($tijd) { return mkbase_ot_tijd_tekst($tijd); };

        // Een tijdvak van gisteren dat over middernacht doorloopt
        $gisteren = mkbase_ot_dag($rooster, $vandaag->modify('-1 day'));
        if ($gisteren['status'] === 'open') {
            foreach ($gisteren['vakken'] as $v) {
                if (mkbase_ot_minuten($v[1]) < mkbase_ot_minuten($v[0]) && $min < mkbase_ot_minuten($v[1])) {
                    return mkbase_ot_status_open($v[1], mkbase_ot_minuten($v[1]) - $min, null);
                }
            }
        }

        $dag = mkbase_ot_dag($rooster, $vandaag);
        if ($dag['status'] === 'afspraak') return ['soort' => 'afspraak', 'tekst' => __('Vandaag alleen op afspraak', 'mk')];
        if ($dag['status'] === 'open') {
            foreach ($dag['vakken'] as $i => $v) {
                $van = mkbase_ot_minuten($v[0]);
                $tot = mkbase_ot_minuten($v[1]);
                if ($tot <= $van) $tot += 1440;
                if ($min >= $van && $min < $tot) return mkbase_ot_status_open($v[1], $tot - $min, $dag['vakken'][$i + 1] ?? null);
                if ($min < $van) {
                    return $i === 0
                        ? ['soort' => 'gesloten', 'tekst' => sprintf(__('Gesloten · vandaag open om %s', 'mk'), $t($v[0]))]
                        : ['soort' => 'pauze', 'tekst' => sprintf(__('Pauze · weer open om %s', 'mk'), $t($v[0]))];
                }
            }
            if (!$dag['vakken']) return ['soort' => 'open', 'tekst' => __('Vandaag geopend', 'mk')];
        }

        // Eerstvolgende dag waarop het open is
        for ($i = 1; $i <= 14; $i++) {
            $d = $vandaag->modify("+$i days");
            $r = mkbase_ot_dag($rooster, $d);
            if ($r['status'] !== 'open' || !$r['vakken']) continue;
            $om = $t($r['vakken'][0][0]);
            $tekst = $i === 1
                ? sprintf(__('Gesloten · morgen open om %s', 'mk'), $om)
                : sprintf(__('Gesloten · %1$s open om %2$s', 'mk'), mkbase_ot_dagnamen()[(int) $d->format('N') - 1], $om);
            return ['soort' => 'gesloten', 'tekst' => $tekst];
        }
        return ['soort' => 'gesloten', 'tekst' => __('Gesloten', 'mk')];
    }

    function mkbase_ot_status_open($tot, $nog, $volgende) {
        $t = function ($tijd) { return mkbase_ot_tijd_tekst($tijd); };
        if ($nog <= 30) return ['soort' => 'bijna', 'tekst' => sprintf(__('Sluit over %s', 'mk'), $nog === 1 ? __('1 minuut', 'mk') : sprintf(__('%s minuten', 'mk'), $nog))];
        $tekst = sprintf(__('Nu open · tot %s', 'mk'), $t($tot));
        if ($volgende) $tekst .= sprintf(__(', daarna weer %s', 'mk'), mkbase_ot_vak_tekst($volgende));
        return ['soort' => 'open', 'tekst' => $tekst];
    }

    // =============================================================================
    // Gegevens voor de browser en de vingerafdruk voor de cache
    // =============================================================================

    // De uitgerekende dagen van $van tot en met $aantal dagen verder: wat de browser nodig heeft om
    // "nu open" en "vandaag" te bepalen, ook als de pagina een paar dagen uit de cache komt
    function mkbase_ot_dagen_data($rooster, $van, $aantal) {
        $uit = [];
        for ($i = 0; $i < $aantal; $i++) {
            $d = $van->modify("+$i days");
            $r = mkbase_ot_dag($rooster, $d);
            $uit[$d->format('Y-m-d')] = ['s' => $r['status'], 'v' => $r['vakken'], 'n' => $r['naam']];
        }
        return $uit;
    }

    // Verandert zodra er op de website iets anders zou staan (ook als er een nieuwe week begint of
    // een feestdag in beeld komt). Zie de nachtelijke controle in admin.php.
    function mkbase_ot_vingerafdruk() {
        $vandaag = mkbase_ot_vandaag();
        $start   = mkbase_ot_week_start($vandaag);
        $data    = ['week' => $start->format('Y-m-d'), 'notatie' => mkbase_ot_notatie()];
        foreach (mkbase_ot_roosters() as $id => $rooster) {
            $data[$id] = mkbase_ot_dagen_data($rooster, $start, 7 + mkbase_ot_vooruit() + 7);
        }
        // Meldingen die een feestdag of afwijking volgen, en de automatische: welke er vandaag live zijn
        if (function_exists('mkbase_notification_status')) {
            $auto = function_exists('mkbase_ot_auto_meldingen') ? mkbase_ot_auto_meldingen() : [];
            foreach (array_merge(mkbase_get_notifications(), $auto) as $row) {
                if (!empty($row['mk_bron'])) $data['meldingen'][$row['mk_bron']] = mkbase_notification_status($row);
            }
        }
        return md5(wp_json_encode($data));
    }
?>
