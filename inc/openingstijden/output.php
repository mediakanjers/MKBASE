<?php
    // =============================================================================
    // Openingstijden — tonen (sinds 1.7.0)
    // =============================================================================
    // mkbase_openingstijden($args) geeft de HTML, voor het blok (template-parts/blocks/openingstijden),
    // de shortcode [mk_openingstijden] en eigen templates. "Nu open" en "vandaag" rekent
    // assets/js/openingstijden.js uit in de browser, zodat ze ook kloppen als de pagina uit de cache komt.
    // Opmaak: assets/css/components/openingstijden.css (basis, laadt vóór style-main.css);
    // het child theme doet de rest met de .mk-ot-classes.

    // Uitgezet: het blok staat in de blokkenmap en wordt dus altijd geregistreerd (init, 10); hier weer weghalen
    add_action('init', function () {
        if (!mkbase_ot_actief() && WP_Block_Type_Registry::get_instance()->is_registered('mk/openingstijden')) {
            unregister_block_type('mk/openingstijden');
        }
    }, 20);

    // Vóór de blokken (init, 10), zodat block.json de handles kan gebruiken
    add_action('init', function () {
        if (!mkbase_ot_actief()) return;
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();
        wp_register_style('mk-openingstijden', $theme_uri . '/assets/css/components/openingstijden.css', [], mkbase_asset_version($theme_dir . '/assets/css/components/openingstijden.css'));
        wp_register_script('mk-openingstijden', $theme_uri . '/assets/js/openingstijden.js', [], mkbase_asset_version($theme_dir . '/assets/js/openingstijden.js'), true);
        wp_localize_script('mk-openingstijden', 'mkOt', mkbase_ot_js_teksten());
    }, 5);

    function mkbase_ot_js_teksten() {
        return [
            'dagen'    => mkbase_ot_dagnamen(),
            'uur'      => __('uur', 'mk'),
            'en'       => __('en', 'mk'),
            'vandaag'  => __('Vandaag', 'mk'),
            'teksten'  => [
                'open'          => __('Nu open · tot %s', 'mk'),
                'daarna'        => __(', daarna weer %s', 'mk'),
                'pauze'         => __('Pauze · weer open om %s', 'mk'),
                'straks'        => __('Gesloten · vandaag open om %s', 'mk'),
                'morgen'        => __('Gesloten · morgen open om %s', 'mk'),
                'later'         => __('Gesloten · %1$s open om %2$s', 'mk'),
                'gesloten'      => __('Gesloten', 'mk'),
                'afspraak'      => __('Vandaag alleen op afspraak', 'mk'),
                'geopend'       => __('Vandaag geopend', 'mk'),
                'vandaagOpen'   => __('Vandaag geopend', 'mk'),
                'vandaagDicht'  => __('Vandaag gesloten', 'mk'),
                'vandaagAfspr'  => __('Vandaag alleen op afspraak', 'mk'),
                'opAfspraak'    => __('Alleen op afspraak', 'mk'),
                'sluitOver'     => __('Sluit over %s', 'mk'),
                'nog'           => __('nog %s', 'mk'),
                'minuten'       => __('%s minuten', 'mk'),
                'minuut'        => __('1 minuut', 'mk'),
                'uren'          => __('%s uur', 'mk'),
                'urenMin'       => __('%1$s uur en %2$s min', 'mk'),
            ],
        ];
    }

    function mkbase_ot_assets() {
        wp_enqueue_style('mk-openingstijden');
        wp_enqueue_script('mk-openingstijden');
    }

    // "Maandag 6 april", of voor meerdere dagen "Donderdag 3 t/m zondag 6 december"
    function mkbase_ot_datum_bereik($van, $tot) {
        if ($van == $tot) return mkbase_ot_datum_lang($van);
        $dagen = mkbase_ot_dagnamen();
        $eerste = $van->format('Ym') === $tot->format('Ym')
            ? ucfirst($dagen[(int) $van->format('N') - 1]) . ' ' . $van->format('j')
            : mkbase_ot_datum_lang($van);
        return sprintf(__('%1$s t/m %2$s', 'mk'), $eerste, lcfirst(mkbase_ot_datum_lang($tot)));
    }

    // De regels van het weekoverzicht van de week waarin $vandaag valt. Een dag met een feestdag of
    // afwijking staat op een eigen regel (met datum en naam); gewone dagen met dezelfde tijden na
    // elkaar worden samengevoegd ("Dinsdag t/m vrijdag"), en opeenvolgende dagen van dezelfde
    // afwijking ook ("Donderdag 3 t/m zondag 6 december").
    function mkbase_ot_week_regels($rooster, $vandaag, $samenvoegen = true, $gesloten_tonen = true) {
        $start  = mkbase_ot_week_start($vandaag);
        $namen  = mkbase_ot_dagnamen();
        $regels = [];
        for ($i = 0; $i < 7; $i++) {
            $dag = $start->modify("+$i days");
            $r   = mkbase_ot_dag($rooster, $dag);
            $uitzondering = $r['soort'] !== 'week';
            $sleutel = ($uitzondering ? 'x|' . $r['naam'] . '|' : '') . $r['status'] . '|' . wp_json_encode($r['vakken']) . '|' . $r['opmerking'];
            $laatste = count($regels) - 1;
            if ($samenvoegen && $laatste >= 0 && $regels[$laatste]['sleutel'] === $sleutel && $regels[$laatste]['tot'] === $i - 1) {
                $regels[$laatste]['tot'] = $i;
                $regels[$laatste]['tot_dag'] = $dag;
                $regels[$laatste]['datums'][] = $dag->format('Y-m-d');
                continue;
            }
            $regels[] = [
                'sleutel'      => $sleutel,
                'van'          => $i,
                'tot'          => $i,
                'dag'          => $dag,
                'tot_dag'      => $dag,
                'datums'       => [$dag->format('Y-m-d')],
                'uitzondering' => $uitzondering,
                'r'            => $r,
            ];
        }

        $uit = [];
        foreach ($regels as $g) {
            $r = $g['r'];
            if (!$gesloten_tonen && !$g['uitzondering'] && $r['status'] === 'gesloten') continue;
            if ($g['uitzondering']) {
                $label = mkbase_ot_datum_bereik($g['dag'], $g['tot_dag']);
                $extra = $r['naam'];
            } else {
                $eerste = ucfirst($namen[$g['van']]);
                $label  = $g['van'] === $g['tot'] ? $eerste
                    : ($g['tot'] - $g['van'] === 1
                        ? sprintf(__('%1$s en %2$s', 'mk'), $eerste, $namen[$g['tot']])
                        : sprintf(__('%1$s t/m %2$s', 'mk'), $eerste, $namen[$g['tot']]));
                $extra  = $r['opmerking'];
            }
            $uit[] = [
                'label'        => $label,
                'extra'        => $extra,
                'toelichting'  => $r['toelichting'],
                'status'       => $r['status'],
                'tijden'       => mkbase_ot_tijden($r['status'], $r['vakken']),
                'datums'       => $g['datums'],
                'uitzondering' => $g['uitzondering'],
            ];
        }
        return $uit;
    }

    function mkbase_ot_afwijkingen_html($lijst) {
        $html = '<dl class="mk-ot__lijst">';
        foreach ($lijst as $a) {
            $datum = $a['van'] == $a['tot'] ? mkbase_ot_datum_kort($a['van'])
                : sprintf(__('%1$s t/m %2$s', 'mk'), mkbase_ot_datum_kort($a['van']), mkbase_ot_datum_kort($a['tot']));
            $tijden = ($a['soort'] === 'afwijking' && ($a['status'] === 'open') && !$a['vakken'] && $a['van'] != $a['tot'])
                ? [__('Gewoon geopend', 'mk')]
                : mkbase_ot_tijden($a['status'], $a['vakken']);
            // Met meerdere roosters: bij wie het geldt, als het niet voor alle gekozen roosters geldt
            $voor = !empty($a['roosters']) ? '<span class="mk-ot__rooster">' . esc_html(implode(', ', $a['roosters'])) . '</span>' : '';
            // Kalendertegel met de begindatum ("29 MRT"); de core verbergt hem, het child theme toont hem
            $kalender = '<span class="mk-ot__kalender" aria-hidden="true"><span class="mk-ot__kalender-maand">'
                . esc_html(mb_substr(mkbase_ot_maanden()[(int) $a['van']->format('n') - 1], 0, 3)) . '</span>'
                . '<span class="mk-ot__kalender-dag">' . esc_html($a['van']->format('j')) . '</span></span>';
            $html .= '<div class="mk-ot__regel mk-ot__regel--' . esc_attr($a['status']) . '">'
                . '<dt>' . $kalender . '<span class="mk-ot__dag">' . esc_html($a['naam']) . '</span> <span class="mk-ot__datum">(' . esc_html($datum) . ')</span>'
                . $voor
                . ($a['toelichting'] ? '<span class="mk-ot__toelichting">' . esc_html($a['toelichting']) . '</span>' : '')
                . '</dt><dd>' . mkbase_ot_tijden_html($tijden) . '</dd></div>';
        }
        return $html . '</dl>';
    }

    // "Let op: maandag 29 maart gesloten (Tweede paasdag)" boven de week. Een paar kandidaten op
    // volgorde; de browser toont de eerste die nog niet voorbij is (ook als de pagina uit de cache komt).
    function mkbase_ot_wijziging_html($lijst) {
        $html = '';
        foreach (array_slice($lijst, 0, 3) as $i => $a) {
            $datum = lcfirst(mkbase_ot_datum_bereik($a['van'], $a['tot']));
            if ($a['status'] === 'gesloten')      $tijden = __('gesloten', 'mk');
            elseif ($a['status'] === 'afspraak')  $tijden = __('alleen op afspraak', 'mk');
            elseif (!$a['vakken'])                $tijden = __('gewoon open', 'mk');
            else $tijden = __('open van', 'mk') . ' ' . implode(' ' . __('en', 'mk') . ' ', array_map(function ($v) {
                return sprintf(__('%1$s tot %2$s', 'mk'), mkbase_ot_tijd_tekst($v[0]), mkbase_ot_tijd_tekst($v[1]));
            }, $a['vakken']));
            $naam = $a['naam'] . (!empty($a['roosters']) ? ' · ' . implode(', ', $a['roosters']) : '');
            $html .= '<p class="mk-ot__wijziging" data-mk-ot-van="' . esc_attr($a['van']->format('Y-m-d')) . '" data-mk-ot-tot="' . esc_attr($a['tot']->format('Y-m-d')) . '"' . ($i ? ' hidden' : '') . '>'
                . '<span class="mk-ot__wijziging-tekst"><strong class="mk-ot__wijziging-let-op">' . esc_html__('Let op:', 'mk') . '</strong> '
                . esc_html(sprintf(__('%1$s %2$s (%3$s)', 'mk'), $datum, $tijden, $naam)) . '</span></p>';
        }
        return $html !== '' ? '<div class="mk-ot__wijzigingen">' . $html . '</div>' : '';
    }

    function mkbase_ot_tijden_html($tijden) {
        return implode('', array_map(function ($t) { return '<span class="mk-ot__tijd">' . esc_html($t) . '</span>'; }, $tijden));
    }

    // Met $naam (bij meerdere roosters) staat de naam van het rooster ervoor: "Werkplaats: Nu open · tot 17:00"
    function mkbase_ot_status_html($rooster, $naam = false) {
        $s = mkbase_ot_status($rooster);
        return '<p class="mk-ot__status mk-ot__status--' . esc_attr($s['soort']) . '" data-mk-ot-status="' . esc_attr($rooster['id']) . '">'
            . '<span class="mk-ot__stip" aria-hidden="true"></span>'
            . ($naam ? '<span class="mk-ot__rooster">' . esc_html($rooster['naam']) . ':</span> ' : '')
            . '<span class="mk-ot__status-tekst">' . esc_html($s['tekst']) . '</span></p>';
    }

    function mkbase_ot_vandaag_html($rooster, $vandaag, $naam = false) {
        $r = mkbase_ot_dag($rooster, $vandaag);
        $label = $r['status'] === 'gesloten' ? __('Vandaag gesloten', 'mk') : ($r['status'] === 'afspraak' ? __('Vandaag alleen op afspraak', 'mk') : __('Vandaag geopend', 'mk'));
        return '<p class="mk-ot__vandaag" data-mk-ot-vandaag="' . esc_attr($rooster['id']) . '">'
            . ($naam ? '<span class="mk-ot__rooster">' . esc_html($rooster['naam']) . ':</span> ' : '')
            . '<span class="mk-ot__vandaag-inhoud"><span class="mk-ot__vandaag-label">' . esc_html($label) . '</span>'
            . ($r['naam'] ? ' <span class="mk-ot__extra">' . esc_html($r['naam']) . '</span>' : '')
            . ($r['status'] === 'open' && $r['vakken'] ? ' <span class="mk-ot__vandaag-tijd">' . esc_html(implode(' ' . __('en', 'mk') . ' ', mkbase_ot_tijden('open', $r['vakken']))) . '</span>' : '')
            . '</span></p>';
    }

    // De gekozen roosters: een kenmerk, een lijst met komma's of een array. Leeg = het eerste rooster.
    function mkbase_ot_kies_roosters($keuze) {
        $ids = is_array($keuze) ? $keuze : explode(',', (string) $keuze);
        $ids = array_values(array_unique(array_filter(array_map('sanitize_key', $ids))));
        $alle = mkbase_ot_roosters();
        $uit = [];
        foreach ($ids as $id) if (isset($alle[$id])) $uit[$id] = $alle[$id];
        if (!$uit && $alle) $uit = [array_key_first($alle) => reset($alle)];
        return $uit;
    }

    // Weekoverzicht van meerdere roosters naast elkaar: per regel de dag(en) en per rooster de tijden.
    // Dagen worden alleen samengevoegd als alle roosters gelijk zijn; een dag met een feestdag of
    // afwijking (bij één van de roosters) staat op een eigen regel.
    function mkbase_ot_week_regels_meer($roosters, $vandaag, $samenvoegen = true, $gesloten_tonen = true) {
        $start  = mkbase_ot_week_start($vandaag);
        $namen  = mkbase_ot_dagnamen();
        $regels = [];
        for ($i = 0; $i < 7; $i++) {
            $dag = $start->modify("+$i days");
            $per = [];
            $uitzondering = false;
            $sleutel = '';
            foreach ($roosters as $id => $rooster) {
                $r = mkbase_ot_dag($rooster, $dag);
                $per[$id] = $r;
                if ($r['soort'] !== 'week') $uitzondering = true;
                $sleutel .= $r['naam'] . '|' . $r['status'] . '|' . wp_json_encode($r['vakken']) . '|' . $r['opmerking'] . ';';
            }
            if ($uitzondering) $sleutel = 'x|' . $sleutel;
            $laatste = count($regels) - 1;
            if ($samenvoegen && $laatste >= 0 && $regels[$laatste]['sleutel'] === $sleutel && $regels[$laatste]['tot'] === $i - 1) {
                $regels[$laatste]['tot'] = $i;
                $regels[$laatste]['tot_dag'] = $dag;
                $regels[$laatste]['datums'][] = $dag->format('Y-m-d');
                continue;
            }
            $regels[] = ['sleutel' => $sleutel, 'van' => $i, 'tot' => $i, 'dag' => $dag, 'tot_dag' => $dag, 'datums' => [$dag->format('Y-m-d')], 'uitzondering' => $uitzondering, 'per' => $per];
        }

        $uit = [];
        foreach ($regels as $g) {
            $dicht = !array_filter($g['per'], function ($r) { return $r['status'] !== 'gesloten'; });
            if (!$gesloten_tonen && !$g['uitzondering'] && $dicht) continue;
            if ($g['uitzondering']) {
                $label = mkbase_ot_datum_bereik($g['dag'], $g['tot_dag']);
                $extra = implode(', ', array_unique(array_filter(array_column($g['per'], 'naam'))));
            } else {
                $eerste = ucfirst($namen[$g['van']]);
                $label  = $g['van'] === $g['tot'] ? $eerste
                    : ($g['tot'] - $g['van'] === 1
                        ? sprintf(__('%1$s en %2$s', 'mk'), $eerste, $namen[$g['tot']])
                        : sprintf(__('%1$s t/m %2$s', 'mk'), $eerste, $namen[$g['tot']]));
                $extra  = '';
            }
            $cellen = [];
            foreach ($g['per'] as $id => $r) {
                $cellen[$id] = [
                    'status' => $r['status'],
                    'tijden' => mkbase_ot_tijden($r['status'], $r['vakken']),
                    'extra'  => $r['soort'] === 'week' ? $r['opmerking'] : '',
                ];
            }
            $uit[] = ['label' => $label, 'extra' => $extra, 'datums' => $g['datums'], 'uitzondering' => $g['uitzondering'], 'cellen' => $cellen];
        }
        return $uit;
    }

    // Afwijkingen van meerdere roosters in één lijst. Gelijke afwijkingen (zelfde naam, datum en tijden)
    // staan er één keer in; geldt een afwijking niet voor alle gekozen roosters, dan staan de namen erbij.
    function mkbase_ot_afwijkingen_lijst_meer($roosters, $vanaf, $na = null) {
        $samen = [];
        foreach ($roosters as $id => $rooster) {
            foreach (mkbase_ot_afwijkingen_lijst($rooster, $vanaf, $na) as $a) {
                $sleutel = $a['naam'] . '|' . $a['van']->format('Ymd') . '|' . $a['tot']->format('Ymd') . '|' . $a['status'] . '|' . wp_json_encode($a['vakken']);
                if (!isset($samen[$sleutel])) $samen[$sleutel] = $a + ['ids' => []];
                $samen[$sleutel]['ids'][] = $id;
            }
        }
        $lijst = array_values($samen);
        foreach ($lijst as &$a) {
            $a['roosters'] = count($a['ids']) < count($roosters)
                ? array_map(function ($id) use ($roosters) { return $roosters[$id]['naam']; }, $a['ids'])
                : [];
        }
        unset($a);
        usort($lijst, function ($x, $y) { return [$x['van'], $x['naam']] <=> [$y['van'], $y['naam']]; });
        return $lijst;
    }

    function mkbase_ot_week_tabel($roosters, $regels) {
        $html = '<table class="mk-ot__tabel"><thead><tr><th scope="col"><span class="mk-ot__verborgen">' . esc_html__('Dag', 'mk') . '</span></th>';
        foreach ($roosters as $r) $html .= '<th scope="col">' . esc_html($r['naam']) . '</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($regels as $r) {
            $html .= '<tr class="mk-ot__regel' . ($r['uitzondering'] ? ' mk-ot__regel--uitzondering' : '') . '" data-mk-ot-dagen="' . esc_attr(implode(',', $r['datums'])) . '">'
                . '<th scope="row"><span class="mk-ot__dag">' . esc_html($r['label']) . '</span>'
                . ($r['extra'] ? '<span class="mk-ot__extra">' . esc_html($r['extra']) . '</span>' : '') . '</th>';
            foreach ($r['cellen'] as $id => $c) {
                $html .= '<td class="mk-ot__cel mk-ot__cel--' . esc_attr($c['status']) . '" data-label="' . esc_attr($roosters[$id]['naam']) . '">'
                    . mkbase_ot_tijden_html($c['tijden'])
                    . ($c['extra'] ? '<span class="mk-ot__extra">' . esc_html($c['extra']) . '</span>' : '') . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table>';
    }

    /**
     * Openingstijden tonen.
     *
     * @param array $args rooster (kenmerk, meerdere met komma's of als array; leeg = het eerste),
     *                    weergave (week|vandaag|status|afwijkingen), titel, status, vandaag, samenvoegen,
     *                    gesloten, afwijkingen, wijziging (true/false), indeling (naast|tabs, bij meerdere
     *                    roosters in de week), link (url, of ACF-link ['url', 'title', 'target'] bij
     *                    vandaag en status), voorbeeld (editor)
     */
    function mkbase_openingstijden($args = []) {
        if (!mkbase_ot_actief()) return '';
        $a = wp_parse_args($args, [
            'rooster'     => '',
            'weergave'    => 'week',
            'titel'       => '',
            'status'      => true,
            'vandaag'     => true,
            'samenvoegen' => true,
            'gesloten'    => true,
            'afwijkingen' => true,
            'wijziging'   => true,
            'indeling'    => 'naast',
            'link'        => null,
            'voorbeeld'   => false,
        ]);
        foreach (['status', 'vandaag', 'samenvoegen', 'gesloten', 'afwijkingen', 'wijziging', 'voorbeeld'] as $k) {
            $a[$k] = filter_var($a[$k], FILTER_VALIDATE_BOOLEAN);
        }
        $weergave = in_array($a['weergave'], ['week', 'vandaag', 'status', 'afwijkingen'], true) ? $a['weergave'] : 'week';
        $roosters = mkbase_ot_ingevuld() ? mkbase_ot_kies_roosters($a['rooster']) : [];
        if (!$roosters) {
            return $a['voorbeeld'] && function_exists('mkbase_block_notice')
                ? mkbase_block_notice(__('Openingstijden: vul eerst de tijden in bij Mediakanjers → Openingstijden.', 'mk'))
                : '';
        }
        $meer    = count($roosters) > 1;
        $rooster = reset($roosters);
        $vandaag = mkbase_ot_vandaag();
        $inhoud  = '';
        $zondag  = mkbase_ot_week_start($vandaag)->modify('+6 days');
        $statussen = function () use ($roosters, $meer) {
            $html = '';
            foreach ($roosters as $r) $html .= mkbase_ot_status_html($r, $meer);
            return $meer ? '<div class="mk-ot__statussen">' . $html . '</div>' : $html;
        };

        // De week van één rooster: status, komende wijziging, de dagen en de afwijkingen daarna
        $week_een = function ($r) use ($a, $vandaag, $zondag) {
            $html = $a['status'] ? mkbase_ot_status_html($r) : '';
            if ($a['wijziging']) $html .= mkbase_ot_wijziging_html(mkbase_ot_afwijkingen_lijst($r, $vandaag));
            $html .= '<dl class="mk-ot__week" data-mk-ot-rooster="' . esc_attr($r['id']) . '">';
            foreach (mkbase_ot_week_regels($r, $vandaag, $a['samenvoegen'], $a['gesloten']) as $regel) {
                $html .= '<div class="mk-ot__regel mk-ot__regel--' . esc_attr($regel['status']) . ($regel['uitzondering'] ? ' mk-ot__regel--uitzondering' : '') . '" data-mk-ot-dagen="' . esc_attr(implode(',', $regel['datums'])) . '">'
                    . '<dt><span class="mk-ot__dag">' . esc_html($regel['label']) . '</span>'
                    . ($regel['extra'] ? '<span class="mk-ot__extra">' . esc_html($regel['extra']) . '</span>' : '')
                    . ($regel['toelichting'] ? '<span class="mk-ot__toelichting">' . esc_html($regel['toelichting']) . '</span>' : '')
                    . '</dt><dd>' . mkbase_ot_tijden_html($regel['tijden']) . '</dd></div>';
            }
            $html .= '</dl>';
            if ($a['afwijkingen'] && ($lijst = mkbase_ot_afwijkingen_lijst($r, $vandaag, $zondag))) {
                $html .= '<div class="mk-ot__afwijkingen"><p class="mk-ot__kop">' . esc_html__('Afwijkende openingstijden', 'mk') . '</p>'
                    . mkbase_ot_afwijkingen_html($lijst) . '</div>';
            }
            return $html;
        };
        $tabs = $weergave === 'week' && $meer && $a['indeling'] === 'tabs';

        if ($tabs) {
            // Een tabblad per rooster. Alle tabbladen staan in de HTML (werkt met de cache); de browser
            // zet ze om naar tabjes. Zonder JavaScript staan de roosters onder elkaar, met de naam erboven.
            static $teller = 0;
            $uid  = 'mk-ot-' . (++$teller);
            $knoppen = $panelen = '';
            $i = 0;
            foreach ($roosters as $id => $r) {
                $tab = $uid . '-tab-' . $id;
                $pan = $uid . '-paneel-' . $id;
                $knoppen .= '<button type="button" class="mk-ot__tab" role="tab" id="' . esc_attr($tab) . '" aria-controls="' . esc_attr($pan) . '"'
                    . ' aria-selected="' . ($i ? 'false' : 'true') . '" tabindex="' . ($i ? '-1' : '0') . '" data-mk-ot-tab="' . esc_attr($id) . '">'
                    . '<span class="mk-ot__stip" aria-hidden="true"></span>' . esc_html($r['naam']) . '</button>';
                $panelen .= '<div class="mk-ot__paneel" role="tabpanel" id="' . esc_attr($pan) . '" aria-labelledby="' . esc_attr($tab) . '" data-mk-ot-paneel="' . esc_attr($id) . '">'
                    . '<p class="mk-ot__paneel-kop">' . esc_html($r['naam']) . '</p>' . $week_een($r) . '</div>';
                $i++;
            }
            $inhoud .= '<div class="mk-ot__tabs" role="tablist" aria-label="' . esc_attr__('Roosters', 'mk') . '">' . $knoppen . '</div>' . $panelen;
        } elseif ($weergave === 'week' && !$meer) {
            $inhoud .= $week_een($rooster);
        } elseif ($weergave === 'week') {
            if ($a['status']) $inhoud .= $statussen();
            if ($a['wijziging']) $inhoud .= mkbase_ot_wijziging_html(mkbase_ot_afwijkingen_lijst_meer($roosters, $vandaag));
            $inhoud .= mkbase_ot_week_tabel($roosters, mkbase_ot_week_regels_meer($roosters, $vandaag, $a['samenvoegen'], $a['gesloten']));
            if ($a['afwijkingen'] && ($lijst = mkbase_ot_afwijkingen_lijst_meer($roosters, $vandaag, $zondag))) {
                $inhoud .= '<div class="mk-ot__afwijkingen"><p class="mk-ot__kop">' . esc_html__('Afwijkende openingstijden', 'mk') . '</p>'
                    . mkbase_ot_afwijkingen_html($lijst) . '</div>';
            }
        } elseif ($weergave === 'vandaag') {
            $html = '';
            foreach ($roosters as $r) $html .= mkbase_ot_vandaag_html($r, $vandaag, $meer);
            $inhoud .= $meer ? '<div class="mk-ot__vandaag-lijst">' . $html . '</div>' : $html;
            if ($a['status']) $inhoud .= $statussen();
        } elseif ($weergave === 'status') {
            $inhoud .= $statussen();
        } else {
            $lijst = $meer ? mkbase_ot_afwijkingen_lijst_meer($roosters, $vandaag) : mkbase_ot_afwijkingen_lijst($rooster, $vandaag);
            if (!$lijst) {
                return $a['voorbeeld'] && function_exists('mkbase_block_notice')
                    ? mkbase_block_notice(sprintf(__('Openingstijden: geen afwijkingen in de komende %d dagen. Op de website staat dit blok dan niet.', 'mk'), mkbase_ot_vooruit()))
                    : '';
            }
            $inhoud .= mkbase_ot_afwijkingen_html($lijst);
        }

        // Bij Vandaag en Status: een link naar de pagina met alle openingstijden
        if (in_array($weergave, ['vandaag', 'status'], true) && $a['link']) {
            $link = is_array($a['link']) ? $a['link'] : ['url' => (string) $a['link']];
            if (!empty($link['url'])) {
                $nieuw = ($link['target'] ?? '') === '_blank';
                $inhoud .= '<p class="mk-ot__link-regel"><a class="mk-ot__link" href="' . esc_url($link['url']) . '"' . ($nieuw ? ' target="_blank" rel="noopener noreferrer"' : '') . '>'
                    . esc_html(($link['title'] ?? '') !== '' ? $link['title'] : __('Alle openingstijden', 'mk')) . '</a></p>';
            }
        }

        mkbase_ot_assets();
        // Wat de browser nodig heeft voor "nu open" en "vandaag", per rooster: van de maandag van deze week
        // tot drie weken verder, zodat het ook klopt als de pagina een paar dagen in de cache staat
        // (in een data-attribuut, niet in een <script>: dan werkt het ook waar HTML gefilterd wordt)
        $data = [
            'tz'       => wp_timezone_string(),
            'off'      => (int) (wp_timezone()->getOffset(new DateTime('now')) / 60),
            'notatie'  => mkbase_ot_notatie(),
            'roosters' => [],
        ];
        foreach ($roosters as $id => $r) {
            $data['roosters'][$id] = mkbase_ot_dagen_data($r, mkbase_ot_week_start($vandaag)->modify('-1 day'), 30);
        }
        $classes = 'mk-ot mk-ot--' . $weergave . ($tabs ? ' mk-ot--tabs' : ($meer ? ' mk-ot--meer' : ''));
        foreach ($roosters as $id => $r) $classes .= ' mk-ot--' . sanitize_html_class($id);
        if ($a['vandaag']) $classes .= ' mk-ot--vandaag-uitlichten';
        return '<div class="' . esc_attr($classes) . '" data-mk-ot="' . esc_attr(wp_json_encode($data)) . '">'
            . ($a['titel'] !== '' ? '<h2 class="mk-ot__titel">' . esc_html($a['titel']) . '</h2>' : '')
            . $inhoud
            . '</div>';
    }

    // [mk_openingstijden rooster="winkel" weergave="week" titel="Openingstijden"]
    add_shortcode('mk_openingstijden', function ($atts) {
        return mkbase_openingstijden(shortcode_atts([
            'rooster'     => '',
            'weergave'    => 'week',
            'titel'       => '',
            'status'      => 'true',
            'vandaag'     => 'true',
            'samenvoegen' => 'true',
            'gesloten'    => 'true',
            'afwijkingen' => 'true',
            'wijziging'   => 'true',
            'indeling'    => 'naast',
            'link'        => '',
        ], $atts, 'mk_openingstijden'));
    });

    // =============================================================================
    // Google: openingstijden als gestructureerde gegevens (schema.org)
    // =============================================================================
    // Met Yoast: aanvullen op het organisatie-onderdeel dat Yoast al maakt. Zonder Yoast: een eigen
    // LocalBusiness op de voorpagina. Alleen voor het rooster bij "Gebruiken voor Google".

    function mkbase_ot_schema_rooster() {
        if (!mkbase_ot_actief() || !mkbase_ot_ingevuld()) return null;
        $id = (string) get_field('mkbase_ot_google', 'option');
        $roosters = mkbase_ot_roosters();
        return ($id !== '' && isset($roosters[$id])) ? $roosters[$id] : null;
    }

    function mkbase_ot_schema($rooster) {
        $dagen = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $groepen = [];
        foreach ($rooster['week'] as $i => $w) {
            if ($w['status'] !== 'open') continue;
            foreach ($w['vakken'] as $v) $groepen[$v[0] . '|' . $v[1]][] = $dagen[$i];
        }
        $specs = [];
        foreach ($groepen as $tijden => $d) {
            [$van, $tot] = explode('|', $tijden);
            $specs[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $d, 'opens' => $van, 'closes' => $tot];
        }

        $speciaal = [];
        $vandaag  = mkbase_ot_vandaag();
        for ($i = 0; $i <= mkbase_ot_vooruit(); $i++) {
            $d = $vandaag->modify("+$i days");
            $r = mkbase_ot_dag($rooster, $d);
            if ($r['soort'] === 'week') continue;
            $datum = $d->format('Y-m-d');
            if ($r['status'] === 'open' && $r['vakken']) {
                foreach ($r['vakken'] as $v) {
                    $speciaal[] = ['@type' => 'OpeningHoursSpecification', 'validFrom' => $datum, 'validThrough' => $datum, 'opens' => $v[0], 'closes' => $v[1]];
                }
            } elseif ($r['status'] !== 'open') {
                $speciaal[] = ['@type' => 'OpeningHoursSpecification', 'validFrom' => $datum, 'validThrough' => $datum, 'opens' => '00:00', 'closes' => '00:00'];
            }
        }

        $uit = ['openingHoursSpecification' => $specs];
        if ($speciaal) $uit['specialOpeningHoursSpecification'] = $speciaal;
        return $uit;
    }

    // Met Yoast: een organisatie die Yoast al maakt wordt ook een LocalBusiness met de tijden. Staat er
    // geen organisatie in (Yoast niet ingesteld als organisatie), dan komt er een eigen LocalBusiness bij.
    add_filter('wpseo_schema_graph', function ($graph) {
        $rooster = mkbase_ot_schema_rooster();
        if (!$rooster || !is_array($graph)) return $graph;
        foreach ($graph as &$stuk) {
            $types = (array) ($stuk['@type'] ?? []);
            if (!in_array('Organization', $types, true) || empty($stuk['@id']) || strpos($stuk['@id'], '#organization') === false) continue;
            if (!in_array('LocalBusiness', $types, true)) $types[] = 'LocalBusiness';
            $stuk['@type'] = $types;
            $stuk = array_merge($stuk, mkbase_ot_schema($rooster));
            return $graph;
        }
        unset($stuk);
        $graph[] = ['@id' => home_url('/#localbusiness')] + mkbase_ot_bedrijf_schema($rooster);
        return $graph;
    });

    // Een LocalBusiness met de gegevens uit Website instellingen en de openingstijden
    function mkbase_ot_bedrijf_schema($rooster) {
        $schema = [
            '@type' => 'LocalBusiness',
            'name'  => get_field('bedrijfsnaam', 'option') ?: get_bloginfo('name'),
            'url'   => home_url('/'),
        ];
        if ($tel = trim((string) get_field('telefoon', 'option'))) $schema['telephone'] = $tel;
        $adres = trim((string) get_field('adres', 'option'));
        $plaats = trim((string) get_field('postcode', 'option'));
        if ($adres || $plaats) {
            $schema['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $adres, 'addressCountry' => 'NL'];
            if (preg_match('/^(\d{4}\s?[A-Za-z]{2})\s+(.+)$/', $plaats, $m)) {
                $schema['address']['postalCode'] = $m[1];
                $schema['address']['addressLocality'] = $m[2];
            } elseif ($plaats) {
                $schema['address']['addressLocality'] = $plaats;
            }
        }
        return $schema + mkbase_ot_schema($rooster);
    }

    // Zonder Yoast: zelf op de voorpagina
    add_action('wp_head', function () {
        if (defined('WPSEO_VERSION') || !is_front_page()) return;
        $rooster = mkbase_ot_schema_rooster();
        if (!$rooster) return;
        $schema = ['@context' => 'https://schema.org'] + mkbase_ot_bedrijf_schema($rooster);
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>' . "\n";
    });
?>
