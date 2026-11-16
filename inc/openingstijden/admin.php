<?php
    // =============================================================================
    // Openingstijden — beheer (sinds 1.7.0)
    // =============================================================================
    // Mediakanjers → Openingstijden: keuzelijsten met de roosters, standaard-feestdagen, controles bij
    // opslaan, het overzicht bovenaan en de nachtelijke controle voor de cache.
    // JS en CSS: assets/js/admin-openingstijden.js en assets/css/admin-openingstijden.css.

    function mkbase_ot_is_beheer() {
        return is_admin() && ($_GET['page'] ?? '') === 'acf-options-openingstijden';
    }

    // Staat de tijdzone op een stad (bijv. Europe/Amsterdam)? Met alleen een afwijking van UTC ("UTC+0",
    // "UTC+1") klopt "nu open" niet of maar half het jaar (zomertijd).
    function mkbase_ot_tijdzone_ok() {
        return (string) get_option('timezone_string') !== '';
    }

    // Advies als de tijdzone geen stad is, vergeleken met Nederland (filter mkbase_ot_tijdzone_referentie):
    // ['ernst' => 'fout'|'let-op', 'kop' => ..., 'tekst' => ...], of null als het goed staat.
    // "fout": het scheelt nu al; "let-op": het klopt nu, maar niet meer na de volgende overgang naar zomer- of wintertijd.
    function mkbase_ot_tijdzone_advies() {
        if (mkbase_ot_tijdzone_ok()) return null;
        $ref   = new DateTimeZone(apply_filters('mkbase_ot_tijdzone_referentie', 'Europe/Amsterdam'));
        $nu    = new DateTimeImmutable('now', $ref);
        $site  = (float) get_option('gmt_offset');
        $echt  = $ref->getOffset($nu) / 3600;
        $kies  = __('Kies bij Instellingen → Algemeen → Tijdzone een stad, zoals Amsterdam: die gaat vanzelf mee met de zomer- en wintertijd.', 'mk');
        $schil = function ($uren) { return abs($uren) == 1 ? __('een uur', 'mk') : sprintf(__('%s uur', 'mk'), number_format_i18n(abs($uren), floor(abs($uren)) == abs($uren) ? 0 : 1)); };

        if ($site != $echt) {
            $verschil = $site - $echt;
            return [
                'ernst' => 'fout',
                'kop'   => sprintf(__('De tijdzone van de website staat op %1$s: voor de website is het nu %2$s, in Nederland %3$s.', 'mk'),
                    mkbase_ot_tijdzone_naam(), $nu->modify(($verschil * 60) . ' minutes')->format('H:i'), $nu->format('H:i')),
                'tekst' => sprintf($verschil < 0 ? __('"Nu open" en "vandaag" lopen daardoor %s achter.', 'mk') : __('"Nu open" en "vandaag" lopen daardoor %s voor.', 'mk'), $schil($verschil)) . ' ' . $kies,
            ];
        }
        // Klopt nu: wanneer niet meer?
        $overgangen = $ref->getTransitions($nu->getTimestamp(), $nu->modify('+1 year')->getTimestamp());
        $volgende = null;
        foreach (array_slice($overgangen, 1) as $o) {
            if ($o['offset'] / 3600 != $echt) { $volgende = $o; break; }
        }
        if (!$volgende) return null; // geen zomertijd in deze tijdzone: een vaste afwijking is dan prima
        $datum = (new DateTimeImmutable('@' . $volgende['ts']))->setTimezone($ref);
        $datum_tekst = lcfirst(mkbase_ot_datum_lang($datum));
        if (!preg_match('/\d{4}/', $datum_tekst)) $datum_tekst .= ' ' . $datum->format('Y');
        $straks = $site - $volgende['offset'] / 3600;
        return [
            'ernst' => 'let-op',
            'kop'   => sprintf(__('De tijdzone van de website staat op %s. Dat klopt nu, maar niet meer vanaf %s.', 'mk'), mkbase_ot_tijdzone_naam(), $datum_tekst),
            'tekst' => sprintf($straks < 0 ? __('Dan begint de %1$s en lopen "Nu open" en "vandaag" %2$s achter.', 'mk') : __('Dan begint de %1$s en lopen "Nu open" en "vandaag" %2$s voor.', 'mk'),
                    $volgende['isdst'] ? __('zomertijd', 'mk') : __('wintertijd', 'mk'), $schil($straks)) . ' ' . $kies,
        ];
    }

    // "UTC+0", "UTC+1", "UTC+5.5": zoals WordPress het bij Instellingen → Algemeen noemt
    function mkbase_ot_tijdzone_naam() {
        $tz = (string) get_option('timezone_string');
        return $tz !== '' ? $tz : 'UTC' . sprintf('%+g', (float) get_option('gmt_offset'));
    }

    // Roosters als keuzes: [id => naam]
    function mkbase_ot_rooster_keuzes() {
        return array_map(function ($r) { return $r['naam']; }, mkbase_ot_roosters());
    }

    foreach (['field_mkbase_ot_google', 'field_mkbase_ot_fd_voor', 'field_mkbase_ot_afw_voor', 'field_mk_ot_rooster'] as $key) {
        add_filter('acf/load_field/key=' . $key, function ($field) {
            $field['choices'] = mkbase_ot_rooster_keuzes();
            return $field;
        });
    }

    // Nog nooit opgeslagen: de gangbare Nederlandse feestdagen alvast in de lijst
    add_filter('acf/load_value/key=field_mkbase_ot_feestdagen', function ($value) {
        if (!empty($value) || get_option('options_mkbase_ot_feestdagen', null) !== null) return $value;
        return array_map(function ($soort) {
            return ['field_mkbase_ot_fd_feestdag' => $soort, 'field_mkbase_ot_fd_status' => 'gesloten'];
        }, mkbase_ot_feestdagen_standaard());
    }, 20);

    // Een rooster krijgt bij het opslaan een vast kenmerk, zodat blokken het houden als de naam of de
    // volgorde verandert. Het eerste rooster heet "standaard".
    add_filter('acf/update_value/key=field_mkbase_ot_r_id', function ($value) {
        $value = sanitize_key((string) $value);
        if ($value !== '') return $value;
        static $gebruikt = false;
        if (!$gebruikt && !in_array('standaard', array_keys(mkbase_ot_roosters()), true)) {
            $gebruikt = true;
            return 'standaard';
        }
        return 'r' . substr(md5(uniqid('', true)), 0, 8);
    });

    // Een afwijking krijgt bij het opslaan ook een vast kenmerk: daar hangt een melding aan (meldingen.php)
    add_filter('acf/update_value/key=field_mkbase_ot_afw_id', function ($value) {
        $value = sanitize_key((string) $value);
        return $value !== '' ? $value : 'a' . substr(md5(uniqid('', true)), 0, 8);
    });

    // Tijden altijd als 08:30 opslaan, ook als iemand 8.30 of 830 typt
    add_filter('acf/update_value/type=text', function ($value, $post_id, $field) {
        if (strpos($field['key'] ?? '', 'field_mkbase_ot_') !== 0 || !preg_match('/_(van|tot)[12]$/', $field['key'])) return $value;
        return mkbase_ot_tijd($value) ?: '';
    }, 10, 3);

    // =============================================================================
    // Controles bij opslaan
    // =============================================================================

    // Klopt een paar tijdvakken? Geeft een foutmelding of ''.
    function mkbase_ot_controleer_vakken($rij, $prefix, $keyprefix, $wat) {
        $waarde = function ($deel, $n) use ($rij, $keyprefix) { return trim((string) ($rij[$keyprefix . '_' . $deel . $n] ?? '')); };
        $vakken = [];
        foreach ([1, 2] as $n) {
            $van = $waarde('van', $n);
            $tot = $waarde('tot', $n);
            if ($van === '' && $tot === '') continue;
            if ($van === '' || $tot === '') return sprintf(__('%s: vul bij elk tijdvak een begin- en eindtijd in.', 'mk'), $wat);
            if (!mkbase_ot_tijd($van) || !mkbase_ot_tijd($tot)) return sprintf(__('%s: vul de tijden in als uu:mm, bijvoorbeeld 08:30.', 'mk'), $wat);
            $vakken[] = [mkbase_ot_minuten(mkbase_ot_tijd($van)), mkbase_ot_minuten(mkbase_ot_tijd($tot))];
        }
        if (!$vakken) return sprintf(__('%s: vul de tijden in.', 'mk'), $wat);
        if (count($vakken) === 2) {
            $einde1 = $vakken[0][1] <= $vakken[0][0] ? $vakken[0][1] + 1440 : $vakken[0][1];
            if ($vakken[1][0] < $einde1) return sprintf(__('%s: het tweede tijdvak begint vóór het einde van het eerste.', 'mk'), $wat);
        }
        return '';
    }

    add_filter('acf/validate_value/key=field_mkbase_ot_roosters', function ($geldig, $rijen) {
        if ($geldig !== true || !is_array($rijen)) return $geldig;
        $over = [];
        foreach ($rijen as $rij) {
            $naam = trim((string) ($rij['field_mkbase_ot_r_naam'] ?? '')) ?: __('Rooster', 'mk');
            foreach (mkbase_ot_dagen() as $i => $dag) {
                if (($rij["field_mkbase_ot_r_{$dag}_status"] ?? '') !== 'open') continue;
                $fout = mkbase_ot_controleer_vakken($rij, '', "field_mkbase_ot_r_{$dag}", $naam . ', ' . (mkbase_ot_dagnamen()[$i] ?? $dag));
                if ($fout) return $fout;
            }
            if (!empty($rij['field_mkbase_ot_r_id'])) $over[] = sanitize_key($rij['field_mkbase_ot_r_id']);
        }
        // Een rooster dat nog in een blok staat, kan niet weg
        foreach (mkbase_ot_gebruik() as $id => $plekken) {
            if ($id === '' || in_array($id, $over, true) || !isset(mkbase_ot_roosters()[$id])) continue;
            return sprintf(__('Rooster "%1$s" staat nog in een blok op: %2$s. Kies daar eerst een ander rooster.', 'mk'),mkbase_ot_roosters()[$id]['naam'], implode(', ', array_slice($plekken, 0, 3)));
        }
        return true;
    }, 10, 2);

    add_filter('acf/validate_value/key=field_mkbase_ot_feestdagen', function ($geldig, $rijen) {
        if ($geldig !== true || !is_array($rijen)) return $geldig;
        foreach ($rijen as $rij) {
            $soort = $rij['field_mkbase_ot_fd_feestdag'] ?? 'eigen';
            $naam  = $soort === 'eigen' ? trim((string) ($rij['field_mkbase_ot_fd_naam'] ?? '')) : (mkbase_ot_feestdag_soorten()[$soort][0] ?? $soort);
            if ($soort === 'eigen') {
                if ($naam === '') return __('Eigen feestdag: vul een naam in.', 'mk');
                $datum = trim((string) ($rij['field_mkbase_ot_fd_datum'] ?? ''));
                if (!preg_match('/^(\d{1,2})-(\d{1,2})$/', $datum, $m) || !checkdate((int) $m[2], (int) $m[1], 2024)) {
                    return sprintf(__('%s: vul de datum in als dag-maand, bijvoorbeeld 14-09.', 'mk'), $naam);
                }
            }
            if (($rij['field_mkbase_ot_fd_status'] ?? '') === 'afwijkend') {
                $fout = mkbase_ot_controleer_vakken($rij, '', 'field_mkbase_ot_fd', $naam);
                if ($fout) return $fout;
            }
        }
        return true;
    }, 10, 2);

    add_filter('acf/validate_value/key=field_mkbase_ot_afwijkingen', function ($geldig, $rijen) {
        if ($geldig !== true || !is_array($rijen)) return $geldig;
        foreach ($rijen as $rij) {
            $naam = trim((string) ($rij['field_mkbase_ot_afw_naam'] ?? '')) ?: __('Afwijkende dag', 'mk');
            $van  = (string) ($rij['field_mkbase_ot_afw_van'] ?? '');
            $tot  = (string) ($rij['field_mkbase_ot_afw_tot'] ?? '');
            if ($van !== '' && $tot !== '' && $tot < $van) return sprintf(__('%s: "Tot en met" ligt vóór de begindatum.', 'mk'), $naam);
            if (($rij['field_mkbase_ot_afw_status'] ?? '') === 'afwijkend') {
                $fout = mkbase_ot_controleer_vakken($rij, '', 'field_mkbase_ot_afw', $naam);
                if ($fout) return $fout;
            }
        }
        return true;
    }, 10, 2);

    // Waar staan de blokken? [rooster-id => ['Contact', 'Home']]; '' = het eerste rooster
    function mkbase_ot_gebruik() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = [];
        // Ook gesynchroniseerde patronen, templates en templateonderdelen ('any' slaat die over)
        $types = array_values(array_unique(array_merge(
            array_keys(get_post_types(['show_ui' => true])),
            ['wp_block', 'wp_template', 'wp_template_part']
        )));
        $soort = [
            'wp_block'         => __('Patroon: %s', 'mk'),
            'wp_template'      => __('Template: %s', 'mk'),
            'wp_template_part' => __('Templateonderdeel: %s', 'mk'),
        ];
        $berichten = get_posts([
            'post_type'      => $types,
            'post_status'    => ['publish', 'draft', 'pending', 'future', 'private'],
            'posts_per_page' => 200,
            's'              => 'wp:mk/openingstijden',
            'search_columns' => ['post_content'],
            'fields'         => 'all',
        ]);
        $zoek = function ($blokken, $titel) use (&$zoek, &$cache) {
            foreach ($blokken as $b) {
                if (($b['blockName'] ?? '') === 'mk/openingstijden') {
                    // Eén kenmerk of (sinds meerdere roosters per blok) een lijst; leeg = het eerste rooster
                    $ids = array_filter((array) ($b['attrs']['data']['rooster'] ?? []));
                    foreach ($ids ?: [''] as $id) $cache[(string) $id][] = $titel;
                }
                if (!empty($b['innerBlocks'])) $zoek($b['innerBlocks'], $titel);
            }
        };
        foreach ($berichten as $p) {
            if (strpos($p->post_content, 'wp:mk/openingstijden') === false) continue;
            $titel = get_the_title($p) ?: __('(zonder titel)', 'mk');
            if (isset($soort[$p->post_type])) $titel = sprintf($soort[$p->post_type], $titel);
            $zoek(parse_blocks($p->post_content), $titel);
        }
        foreach ($cache as $id => $titels) $cache[$id] = array_values(array_unique($titels));
        return $cache;
    }

    // =============================================================================
    // Overzicht bovenaan: tegels, roosters en wat de website nu toont
    // =============================================================================

    add_filter('acf/load_field/key=field_mkbase_ot_overzicht', function ($field) {
        if (!mkbase_ot_is_beheer()) return $field;

        if (!mkbase_ot_ingevuld()) {
            $field['message'] = '<div class="mk-ot-leeg"><p class="mk-text">Nog niets ingevuld. Begin bij <a href="#week" data-mk-ot-tab="week">Vaste week</a>: daar staan alvast standaardtijden klaar. Na <strong>Opslaan</strong> zie je hier wat de website toont.</p></div>';
            return $field;
        }

        $nu       = mkbase_ot_nu();
        $vandaag  = mkbase_ot_vandaag();
        $rooster  = mkbase_ot_rooster();
        $vooruit  = mkbase_ot_vooruit();
        $tegel    = function ($label, $waarde, $sub, $class = '') {
            return '<div class="mk-ot-tegel ' . esc_attr($class) . '"><span class="mk-ot-tegel__label">' . esc_html($label) . '</span>'
                . '<span class="mk-ot-tegel__waarde">' . esc_html($waarde) . '</span><span class="mk-ot-tegel__sub">' . esc_html($sub) . '</span></div>';
        };

        // Tegels per rooster; de roosterkeuze bij de tabbladen toont die van het gekozen rooster
        $html = '';
        if ($advies = mkbase_ot_tijdzone_advies()) {
            $instellingen = current_user_can('manage_options')
                ? ' <a href="' . esc_url(admin_url('options-general.php#timezone_string')) . '">' . esc_html__('Tijdzone instellen', 'mk') . '</a>'
                : ' ' . esc_html__('Vraag een beheerder om dit aan te passen.', 'mk');
            $html .= '<p class="mk-ot-tijdzone mk-ot-tijdzone--' . esc_attr($advies['ernst']) . '" role="' . ($advies['ernst'] === 'fout' ? 'alert' : 'status') . '">'
                . '<strong>' . esc_html($advies['kop']) . '</strong> ' . esc_html($advies['tekst']) . $instellingen . '</p>';
        }
        $n = 0;
        foreach (mkbase_ot_roosters() as $rid => $r) {
            $status   = mkbase_ot_status($r, $nu);
            $dag      = mkbase_ot_dag($r, $vandaag);
            $lijst    = mkbase_ot_afwijkingen_lijst($r, $vandaag);
            $volgende = $lijst[0] ?? null;
            $kleur    = ['open' => 'is-open', 'bijna' => 'is-let-op', 'pauze' => 'is-let-op', 'afspraak' => 'is-let-op', 'gesloten' => 'is-dicht'][$status['soort']];
            $nu_tekst = explode(' · ', $status['tekst'], 2);
            $html .= '<div class="mk-ot-tegels" data-mk-ot-rooster="' . esc_attr($n++) . '">';
            $html .= $tegel(sprintf('Nu (%s, %s)', mkbase_ot_datum_kort($vandaag), $nu->format('H:i')), $nu_tekst[0], $nu_tekst[1] ?? '', $kleur);
            $html .= $tegel('Vandaag' . ($dag['naam'] ? ' · ' . $dag['naam'] : ''), implode(' · ', mkbase_ot_tijden($dag['status'], $dag['vakken'])), $r['naam']);
            $html .= $tegel('Eerstvolgende afwijking', $volgende ? $volgende['naam'] : '–', $volgende ? mkbase_ot_datum_kort($volgende['van']) . ' · ' . implode(' · ', mkbase_ot_tijden($volgende['status'], $volgende['vakken'])) : sprintf('Geen in de komende %d dagen', $vooruit));
            $html .= $tegel('Op de website', sprintf(_n('%d afwijking', '%d afwijkingen', count($lijst), 'mk'), count($lijst)), sprintf('Binnen %d dagen', $vooruit));
            $html .= '</div>';
        }

        // Roosters
        $gebruik = mkbase_ot_gebruik();
        $eerste  = array_key_first(mkbase_ot_roosters());
        $google  = (string) get_field('mkbase_ot_google', 'option');
        $html .= '<div class="mk-ot-kolommen"><section class="mk-ot-kaart"><h3 class="mk-h2">Roosters</h3>'
            . '<p class="mk-hint">Eén rooster is genoeg voor de meeste websites. Heb je bijvoorbeeld een winkel en een werkplaats met andere tijden, voeg dan een rooster toe bij <a href="#week" data-mk-ot-tab="week">Vaste week</a>. In het blok kies je welk rooster je toont.</p><ul class="mk-ot-roosters">';
        // Naam, Google en verwijderen werkt de JS bij (gekoppeld aan de rij bij Vaste week via de volgorde)
        $n = 0;
        foreach (mkbase_ot_roosters() as $id => $r) {
            $plekken = array_merge($gebruik[$id] ?? [], $id === $eerste ? ($gebruik[''] ?? []) : []);
            $html .= '<li data-mk-ot-rooster="' . esc_attr($n++) . '" data-id="' . esc_attr($id) . '"><strong>' . esc_html($r['naam']) . '</strong><span class="mk-hint">'
                . esc_html($plekken ? sprintf(_n('In %1$d blok: %2$s', 'In %1$d blokken: %2$s', count($plekken), 'mk'), count($plekken), implode(', ', array_slice($plekken, 0, 3))) : 'Nog niet in een blok')
                . '</span>' . ($id === $google ? '<span class="mk-pill mk-pill--gepland">Gebruikt voor Google</span>' : '') . '</li>';
        }
        $html .= '</ul>';
        // Nog geen rooster voor Google (de JS verbergt dit zodra er een gekozen is)
        $html .= '<p class="mk-ot-google-leeg" role="status"' . ($google !== '' && isset(mkbase_ot_roosters()[$google]) ? ' hidden' : '') . '>'
            . '<strong>' . esc_html__('Google krijgt nog geen openingstijden.', 'mk') . '</strong> '
            . esc_html__('Kies bij het rooster van je winkel of balie "Gebruiken voor Google". Dan toont Google je openingstijden, ook op feestdagen en bij afwijkingen.', 'mk') . '</p>';
        $html .= '</section>';

        // Zo staat het op de website (alle roosters samen, zoals een blok met alle roosters aangevinkt)
        $alle = array_keys(mkbase_ot_roosters());
        $html .= '<section class="mk-ot-kaart"><h3 class="mk-h2">Zo staat het op de website</h3><div class="mk-ot-voorbeeld">'
            . mkbase_openingstijden(['rooster' => $alle, 'titel' => count($alle) > 1 ? '' : $rooster['naam']])
            . '</div><p class="mk-hint">Na opslaan bijgewerkt. Feestdagen en afwijkende dagen in deze week staan op hun eigen dag; latere staan eronder zodra ze binnen ' . (int) $vooruit . ' dagen vallen.</p></section></div>';

        $field['message'] = $html;
        return $field;
    });

    // Tijdlijn naast de afwijkende dagen: feestdagen en afwijkingen van de komende $dagen dagen, op volgorde
    function mkbase_ot_tijdlijn_admin($dagen) {
        $vandaag = mkbase_ot_vandaag();
        $grens   = $vandaag->modify("+$dagen days");
        $web     = $vandaag->modify('+' . mkbase_ot_vooruit() . ' days');
        $uit     = [];
        foreach (mkbase_ot_bronnen() as $b) {
            if ($b['tot'] < $vandaag || $b['van'] > $grens) continue;
            $tijden = $b['status'] === 'gesloten' ? __('gesloten', 'mk') : ($b['status'] === 'afspraak' ? __('op afspraak', 'mk')
                : ($b['status'] === 'open' ? __('gewoon open', 'mk') : implode(' · ', mkbase_ot_tijden('open', $b['vakken']))));
            $sub = ($b['soort'] === 'feestdag' ? __('Feestdag', 'mk') : __('Afwijking', 'mk'))
                . ($b['voor'] ? ' · ' . mkbase_ot_rooster_namen($b['voor']) : '') . ' · ' . $tijden;
            if ($b['van'] != $b['tot']) $sub .= ' · ' . sprintf(__('t/m %s', 'mk'), mkbase_ot_datum_kort($b['tot']));
            $uit[] = ['datum' => mkbase_ot_datum_kort($b['van']), 'naam' => $b['naam'], 'sub' => $sub, 'web' => $b['van'] <= $web];
        }
        return $uit;
    }

    // =============================================================================
    // Scripts en stijl van de beheerpagina
    // =============================================================================

    add_action('admin_enqueue_scripts', function () {
        if (!mkbase_ot_is_beheer()) return;
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();
        wp_enqueue_style('mk-admin-openingstijden', $theme_uri . '/assets/css/admin-openingstijden.css', ['mk-admin', 'mk-openingstijden'], mkbase_asset_version($theme_dir . '/assets/css/admin-openingstijden.css'));
        wp_enqueue_script('mk-admin-openingstijden', $theme_uri . '/assets/js/admin-openingstijden.js', ['acf-input', 'mk-admin', 'mk-openingstijden'], mkbase_asset_version($theme_dir . '/assets/js/admin-openingstijden.js'), true);
        $vandaag = mkbase_ot_vandaag();
        $soorten = [];
        foreach (mkbase_ot_feestdag_soorten() as $k => $f) $soorten[$k] = $f[1];
        wp_localize_script('mk-admin-openingstijden', 'mkOtAdmin', [
            'soorten'   => $soorten,
            'standaard' => mkbase_ot_feestdagen_standaard(),
            'vandaag'   => $vandaag->format('Y-m-d'),
            'weekStart' => mkbase_ot_week_start($vandaag)->format('Y-m-d'),
            'vooruit'   => mkbase_ot_vooruit(),
            'notatie'   => mkbase_ot_notatie(),
            'tijdlijn'  => mkbase_ot_ingevuld() ? mkbase_ot_tijdlijn_admin(120) : [],
        ]);
    }, 20);

    // =============================================================================
    // Cache: na opslaan, en elke nacht als er op de website iets anders zou staan
    // =============================================================================
    // Het legen na opslaan doet inc/admin/ui.php al voor alle MK-pagina's; hier alleen de vingerafdruk.

    add_action('acf/save_post', function ($post_id) {
        if ($post_id !== 'options' || !mkbase_ot_is_beheer()) return;
        update_option('mkbase_ot_vingerafdruk', mkbase_ot_vingerafdruk(), false);
    }, 30);

    add_action('mkbase_ot_dagelijks', function () {
        if (!mkbase_ot_actief() || !mkbase_ot_ingevuld()) return;
        $nu = mkbase_ot_vingerafdruk();
        if ($nu === get_option('mkbase_ot_vingerafdruk')) return;
        update_option('mkbase_ot_vingerafdruk', $nu, false);
        if (function_exists('rocket_clean_domain')) rocket_clean_domain();
    });

    add_action('init', function () {
        if (!mkbase_ot_actief() || !mkbase_ot_ingevuld() || wp_next_scheduled('mkbase_ot_dagelijks')) return;
        // Net na middernacht, in de tijdzone van de website
        $morgen = new DateTimeImmutable('tomorrow 00:05', wp_timezone());
        wp_schedule_event($morgen->getTimestamp(), 'daily', 'mkbase_ot_dagelijks');
    });
?>
