<?php
    // =============================================================================
    // Openingstijden — velden (sinds 1.7.0)
    // =============================================================================
    // Eén optiepagina (acf-options-openingstijden, zie inc/acf/acf.php) met vijf veldgroepen. Het
    // beheer toont ze als vier tabbladen (Overzicht · Vaste week · Feestdagen · Afwijkend), zie
    // assets/js/admin-openingstijden.js. De opties heten mkbase_ot_*.

    add_action('acf/include_fields', function () {
        if (!function_exists('acf_add_local_field_group') || !mkbase_ot_actief()) return;

        $pagina = [[['param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-openingstijden']]];
        $als    = function ($veld, $waarde, $operator = '==') {
            return [[['field' => $veld, 'operator' => $operator, 'value' => $waarde]]];
        };
        // Bewerkvenster van het blok: een kopje boven een groep velden
        $groepkop = function ($key, $tekst, $voorwaarde = 0) {
            return [
                'key'               => $key,
                'label'             => '',
                'name'              => '',
                'type'              => 'message',
                'message'           => '<span class="mk-bv-groepkop__tekst">' . esc_html($tekst) . '</span>',
                'esc_html'          => 0,
                'wrapper'           => ['class' => 'mk-bv-groepkop'],
                'conditional_logic' => $voorwaarde,
            ];
        };
        // Bewerkvenster van het blok: een schakelaar in de compacte lijst (twee naast elkaar)
        $schakel = function ($key, $label, $naam, $voorwaarde) {
            return [
                'key'               => $key,
                'label'             => $label,
                'name'              => $naam,
                'type'              => 'true_false',
                'ui'                => 1,
                'default_value'     => 1,
                'wrapper'           => ['width' => '50', 'class' => 'mk-bv-schakel'],
                'conditional_logic' => $voorwaarde,
            ];
        };
        // Twee tijdvakken (van/tot); het tweede verschijnt pas als je het toevoegt (zie de JS)
        $tijden = function ($prefix, $key, $label, $voorwaarde) {
            $velden = [];
            foreach ([1, 2] as $n) {
                foreach (['van' => __('van', 'mk'), 'tot' => __('tot', 'mk')] as $deel => $woord) {
                    $velden[] = [
                        'key'               => $key . '_' . $deel . $n,
                        'label'             => $label . ' ' . $woord . ($n === 2 ? ' (2)' : ''),
                        'name'              => $prefix . $deel . $n,
                        'type'              => 'text',
                        'maxlength'         => 5,
                        'placeholder'       => $deel === 'van' ? ($n === 1 ? '08:30' : '13:00') : ($n === 1 ? '12:00' : '17:30'),
                        'wrapper'           => ['width' => 11, 'class' => 'mk-ot-tijd mk-ot-tijd--' . $deel . ' mk-ot-tijd--' . $n],
                        'conditional_logic' => $voorwaarde,
                    ];
                }
            }
            return $velden;
        };

        // ── Overzicht ──
        acf_add_local_field_group([
            'key'         => 'group_mkbase_ot_overzicht',
            'title'       => 'Overzicht',
            'description' => 'De stand van vandaag en precies wat de website nu toont.',
            'fields'      => [[
                'key'       => 'field_mkbase_ot_overzicht',
                'label'     => '',
                'name'      => '',
                'type'      => 'message',
                'esc_html'  => 0,
                'new_lines' => '',
                'message'   => '', // gevuld in admin.php
            ]],
            'location'    => $pagina,
            'menu_order'  => 0,
        ]);

        acf_add_local_field_group([
            'key'         => 'group_mkbase_ot_algemeen',
            'title'       => 'Algemeen',
            'description' => 'Geldt voor alle roosters en alle blokken.',
            'fields'      => [
                [
                    'key'           => 'field_mkbase_ot_vooruit',
                    'label'         => 'Afwijkingen vooraf tonen',
                    'name'          => 'mkbase_ot_vooruit',
                    'type'          => 'number',
                    'default_value' => 30,
                    'min'           => 1,
                    'max'           => 365,
                    'append'        => 'dagen',
                    'wrapper'       => ['width' => 33],
                ],
                [
                    'key'           => 'field_mkbase_ot_notatie',
                    'label'         => 'Tijden tonen als',
                    'name'          => 'mkbase_ot_notatie',
                    'type'          => 'button_group',
                    'choices'       => ['hh' => '08:30', 'h' => '8:30', 'uur' => '8.30 uur'],
                    'default_value' => 'hh',
                    'wrapper'       => ['width' => 33],
                ],
                [
                    'key'           => 'field_mkbase_ot_google',
                    'label'         => 'Gebruiken voor Google',
                    'name'          => 'mkbase_ot_google',
                    'type'          => 'select',
                    'choices'       => [], // de roosters, zie admin.php
                    'allow_null'    => 1,
                    'placeholder'   => 'Geen',
                    'wrapper'       => ['width' => 34],
                ],
                // Koppeling met Meldingen (inc/notifications.php)
                [
                    'key'           => 'field_mkbase_ot_auto',
                    'label'         => 'Automatisch een melding bij elke feestdag en afwijking',
                    'name'          => 'mkbase_ot_auto',
                    'type'          => 'true_false',
                    'ui'            => 1,
                    'default_value' => 0,
                ],
                [
                    'key'               => 'field_mkbase_ot_auto_positie',
                    'label'             => 'Positie',
                    'name'              => 'mkbase_ot_auto_positie',
                    'type'              => 'select',
                    'choices'           => ['boven' => 'Bovenaan, blijft staan', 'boven_scrollt' => 'Bovenaan, scrolt mee weg', 'onder' => 'Onderaan, blijft staan'],
                    'default_value'     => 'boven',
                    'wrapper'           => ['width' => 40],
                    'conditional_logic' => $als('field_mkbase_ot_auto', '1'),
                ],
                [
                    'key'               => 'field_mkbase_ot_auto_stijl',
                    'label'             => 'Stijl',
                    'name'              => 'mkbase_ot_auto_stijl',
                    'type'              => 'button_group',
                    'choices'           => ['info' => 'Info', 'waarschuwing' => 'Waarschuwing', 'succes' => 'Succes', 'actie' => 'Actie'],
                    'default_value'     => 'waarschuwing',
                    'wrapper'           => ['width' => 60],
                    'conditional_logic' => $als('field_mkbase_ot_auto', '1'),
                ],
                [
                    'key'               => 'field_mkbase_ot_auto_tekst',
                    'label'             => 'Standaardtekst',
                    'name'              => 'mkbase_ot_auto_tekst',
                    'type'              => 'text',
                    'placeholder'       => 'Let op: {datum} zijn wij {tijden} ({naam}). {toelichting}',
                    'instructions'      => 'Leeg: de tekst hierboven. Invullers: {naam} {datum} {tijden} {rooster} {toelichting}',
                    'conditional_logic' => $als('field_mkbase_ot_auto', '1'),
                ],
            ],
            'location'    => $pagina,
            'menu_order'  => 1,
        ]);

        // ── Vaste week: één rij per rooster ──
        $week = [
            [
                'key'           => 'field_mkbase_ot_r_naam',
                'label'         => 'Naam van het rooster',
                'name'          => 'naam',
                'type'          => 'text',
                'default_value' => 'Openingstijden',
                'placeholder'   => 'Bijvoorbeeld: Winkel',
                'required'      => 1,
                'wrapper'       => ['width' => 50, 'class' => 'mk-ot-naam'],
            ],
            [
                'key'     => 'field_mkbase_ot_r_id',
                'label'   => 'Kenmerk',
                'name'    => 'id',
                'type'    => 'text',
                'wrapper' => ['class' => 'mk-ot-id'],
            ],
        ];
        foreach (mkbase_ot_dagen() as $i => $dag) {
            $naam = ucfirst($dag);
            $week[] = [
                'key'           => "field_mkbase_ot_r_{$dag}_status",
                'label'         => $naam,
                'name'          => "{$dag}_status",
                'type'          => 'button_group',
                'choices'       => ['open' => 'Open', 'afspraak' => 'Op afspraak', 'gesloten' => 'Gesloten'],
                'default_value' => mkbase_ot_week_standaard($i),
                'wrapper'       => ['width' => 34, 'class' => 'mk-ot-status', 'data-dag' => $dag],
            ];
            $open = $als("field_mkbase_ot_r_{$dag}_status", 'open');
            foreach ($tijden("{$dag}_", "field_mkbase_ot_r_{$dag}", $naam, $open) as $veld) {
                if ($i < 5 && $veld['name'] === "{$dag}_van1") $veld['default_value'] = '09:00';
                if ($i < 5 && $veld['name'] === "{$dag}_tot1") $veld['default_value'] = '17:00';
                $week[] = $veld;
            }
            $week[] = [
                'key'               => "field_mkbase_ot_r_{$dag}_opmerking",
                'label'             => $naam . ': opmerking',
                'name'              => "{$dag}_opmerking",
                'type'              => 'text',
                'placeholder'       => 'Opmerking',
                'wrapper'           => ['width' => 22, 'class' => 'mk-ot-opmerking'],
                'conditional_logic' => $als("field_mkbase_ot_r_{$dag}_status", 'gesloten', '!='),
            ];
        }

        acf_add_local_field_group([
            'key'         => 'group_mkbase_ot_week',
            'title'       => 'Vaste week',
            'description' => 'De tijden van een gewone week. Feestdagen en afwijkende dagen gaan hier vanzelf voor. Een pauze of koopavond? Gebruik een tweede tijdvak.',
            'fields'      => [[
                'key'          => 'field_mkbase_ot_roosters',
                'label'        => 'Roosters',
                'name'         => 'mkbase_ot_roosters',
                'type'         => 'repeater',
                'layout'       => 'block',
                'min'          => 1,
                'button_label' => 'Rooster toevoegen',
                'sub_fields'   => $week,
            ]],
            'location'    => $pagina,
            'menu_order'  => 2,
        ]);

        // ── Feestdagen ──
        $soorten = [];
        foreach (mkbase_ot_feestdag_soorten() as $sleutel => $f) $soorten[$sleutel] = $f[0];
        $soorten['eigen'] = 'Eigen feestdag…';
        $fd_tijd = $als('field_mkbase_ot_fd_status', 'afwijkend');

        acf_add_local_field_group([
            'key'         => 'group_mkbase_ot_feestdagen',
            'title'       => 'Feestdagen',
            'description' => 'Feestdagen die elk jaar terugkomen; de datum wordt elk jaar vanzelf uitgerekend, ook voor Pasen, Hemelvaart en Pinksteren. Valt een feestdag in de week op de website, dan staat hij op zijn eigen dag in het weekoverzicht.',
            'fields'      => [[
                'key'          => 'field_mkbase_ot_feestdagen',
                'label'        => 'Feestdagen',
                'name'         => 'mkbase_ot_feestdagen',
                'type'         => 'repeater',
                'layout'       => 'block',
                'button_label' => 'Feestdag toevoegen',
                'sub_fields'   => array_merge(
                    [
                        [
                            'key'           => 'field_mkbase_ot_fd_feestdag',
                            'label'         => 'Feestdag',
                            'name'          => 'feestdag',
                            'type'          => 'select',
                            'choices'       => $soorten,
                            'default_value' => 'eigen',
                            'wrapper'       => ['width' => 25, 'class' => 'mk-ot-fd-feestdag'],
                        ],
                        [
                            'key'               => 'field_mkbase_ot_fd_naam',
                            'label'             => 'Naam',
                            'name'              => 'naam',
                            'type'              => 'text',
                            'placeholder'       => 'Bijvoorbeeld: Kermis',
                            'wrapper'           => ['width' => 25, 'class' => 'mk-ot-fd-naam'],
                            'conditional_logic' => $als('field_mkbase_ot_fd_feestdag', 'eigen'),
                        ],
                        [
                            'key'               => 'field_mkbase_ot_fd_datum',
                            'label'             => 'Datum',
                            'name'              => 'datum',
                            'type'              => 'text',
                            'placeholder'       => '14-09',
                            'maxlength'         => 5,
                            'wrapper'           => ['width' => 15, 'class' => 'mk-ot-fd-datum'],
                            'conditional_logic' => $als('field_mkbase_ot_fd_feestdag', 'eigen'),
                        ],
                        [
                            'key'       => 'field_mkbase_ot_fd_valt',
                            'label'     => 'Valt op',
                            'name'      => '',
                            'type'      => 'message',
                            'message'   => '',
                            'esc_html'  => 0,
                            'new_lines' => '',
                            'wrapper'   => ['width' => 20, 'class' => 'mk-ot-fd-valt'],
                        ],
                        [
                            'key'           => 'field_mkbase_ot_fd_status',
                            'label'         => 'Status',
                            'name'          => 'status',
                            'type'          => 'select',
                            'choices'       => ['gesloten' => 'Gesloten', 'afwijkend' => 'Andere tijden', 'afspraak' => 'Op afspraak'],
                            'default_value' => 'gesloten',
                            'wrapper'       => ['width' => 15, 'class' => 'mk-ot-fd-status'],
                        ],
                    ],
                    $tijden('', 'field_mkbase_ot_fd', 'Feestdag', $fd_tijd),
                    [
                        [
                            'key'          => 'field_mkbase_ot_fd_voor',
                            'label'        => 'Geldt voor',
                            'name'         => 'voor',
                            'type'         => 'checkbox',
                            'choices'      => [], // de roosters, zie admin.php
                            'layout'       => 'horizontal',
                            'instructions' => 'Niets aangevinkt: alle roosters',
                            'wrapper'      => ['class' => 'mk-ot-voor'],
                        ],
                        // Geen automatische melding voor deze feestdag (knop in de regel, zie de JS)
                        [
                            'key'     => 'field_mkbase_ot_fd_geen_melding',
                            'label'   => 'Geen automatische melding',
                            'name'    => 'geen_melding',
                            'type'    => 'true_false',
                            'wrapper' => ['class' => 'mk-ot-geen-melding'],
                        ],
                    ]
                ),
            ]],
            'location'    => $pagina,
            'menu_order'  => 3,
        ]);

        // ── Afwijkende dagen en periodes ──
        $afw_tijd = $als('field_mkbase_ot_afw_status', 'afwijkend');
        acf_add_local_field_group([
            'key'         => 'group_mkbase_ot_afwijkend',
            'title'       => 'Afwijkende dagen',
            'description' => 'Eenmalige dagen of periodes, zoals een bedrijfsvakantie of een kortere dag. Deze gaan voor op de vaste week én op de feestdagen. Voorbije dagen verdwijnen vanzelf van de website.',
            'fields'      => [[
                'key'          => 'field_mkbase_ot_afwijkingen',
                'label'        => 'Afwijkende dagen en periodes',
                'name'         => 'mkbase_ot_afwijkingen',
                'type'         => 'repeater',
                'layout'       => 'block',
                'button_label' => 'Dag of periode toevoegen',
                'sub_fields'   => array_merge(
                    [
                        [
                            'key'         => 'field_mkbase_ot_afw_naam',
                            'label'       => 'Omschrijving',
                            'name'        => 'naam',
                            'type'        => 'text',
                            'placeholder' => 'Bijvoorbeeld: Bedrijfsvakantie',
                            'required'    => 1,
                            'wrapper'     => ['width' => 50],
                        ],
                        [
                            'key'            => 'field_mkbase_ot_afw_van',
                            'label'          => 'Datum',
                            'name'           => 'van',
                            'type'           => 'date_picker',
                            'display_format' => 'd-m-Y',
                            'return_format'  => 'Y-m-d',
                            'first_day'      => 1,
                            'required'       => 1,
                            'wrapper'        => ['width' => 25, 'class' => 'mk-ot-afw-van'],
                        ],
                        [
                            'key'            => 'field_mkbase_ot_afw_tot',
                            'label'          => 'Tot en met',
                            'name'           => 'tot',
                            'type'           => 'date_picker',
                            'display_format' => 'd-m-Y',
                            'return_format'  => 'Y-m-d',
                            'first_day'      => 1,
                            'instructions'   => 'Leeg bij één dag',
                            'wrapper'        => ['width' => 25, 'class' => 'mk-ot-afw-tot'],
                        ],
                        [
                            'key'           => 'field_mkbase_ot_afw_status',
                            'label'         => 'Status',
                            'name'          => 'status',
                            'type'          => 'button_group',
                            'choices'       => ['gesloten' => 'Gesloten', 'afwijkend' => 'Andere tijden', 'afspraak' => 'Op afspraak', 'open' => 'Gewoon open'],
                            'default_value' => 'gesloten',
                            'wrapper'       => ['width' => 50, 'class' => 'mk-ot-afw-status'],
                        ],
                    ],
                    $tijden('', 'field_mkbase_ot_afw', 'Afwijking', $afw_tijd),
                    [
                        [
                            'key'          => 'field_mkbase_ot_afw_voor',
                            'label'        => 'Geldt voor',
                            'name'         => 'voor',
                            'type'         => 'checkbox',
                            'choices'      => [],
                            'layout'       => 'horizontal',
                            'instructions' => 'Niets aangevinkt: alle roosters',
                            'wrapper'      => ['width' => 50, 'class' => 'mk-ot-voor'],
                        ],
                        [
                            'key'         => 'field_mkbase_ot_afw_toelichting',
                            'label'       => 'Toelichting',
                            'name'        => 'toelichting',
                            'type'        => 'text',
                            'placeholder' => 'Bijvoorbeeld: voor spoed zijn we telefonisch bereikbaar',
                            'wrapper'     => ['width' => 50],
                        ],
                        // Vast kenmerk voor de koppeling met Meldingen (bij opslaan gevuld, zie admin.php)
                        [
                            'key'     => 'field_mkbase_ot_afw_id',
                            'label'   => 'Kenmerk',
                            'name'    => 'id',
                            'type'    => 'text',
                            'wrapper' => ['class' => 'mk-ot-id'],
                        ],
                        [
                            'key'     => 'field_mkbase_ot_afw_geen_melding',
                            'label'   => 'Geen automatische melding',
                            'name'    => 'geen_melding',
                            'type'    => 'true_false',
                            'wrapper' => ['class' => 'mk-ot-geen-melding'],
                        ],
                    ]
                ),
            ]],
            'location'    => $pagina,
            'menu_order'  => 4,
        ]);

        // ── Blok Openingstijden ──
        if (!mkbase_block_has_fields('mk/openingstijden'))
        acf_add_local_field_group([
            'key'    => 'group_mk_openingstijden_block',
            'title'  => 'Openingstijden blok',
            // Het bewerkvenster in drie groepen (kopjes: message-velden met class mk-bv-groepkop):
            // Wat toon je · Indeling · Onderdelen. De schakelaars staan als compacte lijst twee naast
            // elkaar (class mk-bv-schakel). Opmaak: assets/css/editor-blocks.css; "Meerdere roosters"
            // pas bij twee roosters en de knoppen voor de stijl: assets/js/editor-blocks.js.
            'fields' => [
                $groepkop('field_mk_ot_kop_inhoud', 'Wat toon je'),
                [
                    'key'           => 'field_mk_ot_rooster',
                    'label'         => 'Roosters',
                    'name'          => 'rooster',
                    'type'          => 'checkbox',
                    'choices'       => [], // zie admin.php
                    'layout'        => 'horizontal',
                    'return_format' => 'value',
                    'instructions'  => 'Kies er meer om ze in één overzicht te tonen. Niets aangevinkt: het eerste rooster.',
                ],
                [
                    'key'           => 'field_mk_ot_weergave',
                    'label'         => 'Weergave',
                    'name'          => 'weergave',
                    'type'          => 'button_group',
                    'choices'       => ['week' => 'Week', 'vandaag' => 'Vandaag', 'status' => 'Status', 'afwijkingen' => 'Afwijkingen'],
                    'default_value' => 'week',
                ],
                [
                    'key'         => 'field_mk_ot_titel',
                    'label'       => 'Titel',
                    'name'        => 'titel',
                    'type'        => 'text',
                    'placeholder' => 'Bijvoorbeeld: Openingstijden',
                ],
                [
                    'key'               => 'field_mk_ot_link',
                    'label'             => 'Link naar alle openingstijden',
                    'name'              => 'link',
                    'type'              => 'link',
                    'return_format'     => 'array',
                    'instructions'      => 'Optioneel. Zonder linktekst: "Alle openingstijden"',
                    'conditional_logic' => [
                        [['field' => 'field_mk_ot_weergave', 'operator' => '==', 'value' => 'vandaag']],
                        [['field' => 'field_mk_ot_weergave', 'operator' => '==', 'value' => 'status']],
                    ],
                ],

                $groepkop('field_mk_ot_kop_indeling', 'Indeling'),
                [
                    'key'               => 'field_mk_ot_indeling',
                    'label'             => 'Meerdere roosters',
                    'name'              => 'indeling',
                    'type'              => 'button_group',
                    'choices'           => ['naast' => 'Naast elkaar', 'tabs' => 'Tabjes'],
                    'default_value'     => 'naast',
                    'conditional_logic' => $als('field_mk_ot_weergave', 'week'),
                ],
                [
                    'key'           => 'field_mk_ot_breedte',
                    'label'         => 'Breedte',
                    'name'          => 'breedte',
                    'type'          => 'button_group',
                    'choices'       => ['inhoud' => 'Inhoudsbreedte', 'vol' => 'Volle breedte (100%)'],
                    'default_value' => 'inhoud',
                ],

                // Bij Afwijkingen valt er niets aan of uit te zetten
                $groepkop('field_mk_ot_kop_onderdelen', 'Onderdelen', [[['field' => 'field_mk_ot_weergave', 'operator' => '!=', 'value' => 'afwijkingen'], ['field' => 'field_mk_ot_weergave', 'operator' => '!=', 'value' => 'status']]]),
                $schakel('field_mk_ot_toon_status', 'Status "nu open"', 'toon_status', [
                    [['field' => 'field_mk_ot_weergave', 'operator' => '==', 'value' => 'week']],
                    [['field' => 'field_mk_ot_weergave', 'operator' => '==', 'value' => 'vandaag']],
                ]),
                $schakel('field_mk_ot_wijziging', 'Komende wijziging uitlichten', 'wijziging_uitlichten', $als('field_mk_ot_weergave', 'week')),
                $schakel('field_mk_ot_vandaag', 'Vandaag uitlichten', 'vandaag_uitlichten', $als('field_mk_ot_weergave', 'week')),
                $schakel('field_mk_ot_samenvoegen', 'Gelijke dagen samenvoegen', 'samenvoegen', $als('field_mk_ot_weergave', 'week')),
                $schakel('field_mk_ot_gesloten', 'Gesloten dagen tonen', 'gesloten_tonen', $als('field_mk_ot_weergave', 'week')),
                $schakel('field_mk_ot_afwijkingen', 'Afwijkingen tonen', 'afwijkingen_tonen', $als('field_mk_ot_weergave', 'week')),
            ],
            'location' => [[['param' => 'block', 'operator' => '==', 'value' => 'mk/openingstijden']]],
            'active'   => true,
        ]);
        else
            mkbase_report_issue("De ACF-velden van blok 'mk/openingstijden' worden overschreven door het child-theme.", 'info', false);
    });
?>
