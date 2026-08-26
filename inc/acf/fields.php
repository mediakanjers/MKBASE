<?php
    /**
     * ACF Local Field Groups
     * Geavanceerd – Meldingen – MK instellingen
     */

    // Controleert of een child theme al een veldgroep heeft geregistreerd voor een blok.
    // Als dat zo is, slaat de core zijn eigen registratie over — child theme wint.
    function mkbase_block_has_fields($block) {
        foreach (acf_get_field_groups() as $group) {
            foreach ($group['location'] ?? [] as $rules) {
                foreach ($rules as $rule) {
                    if (isset($rule['param'], $rule['value'])
                        && $rule['param']    === 'block'
                        && $rule['value']    === $block
                    ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    add_action('acf/include_fields', function () {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        /**
         * ====================================
         * GEAVANCEERD
         * ====================================
         */
        $geavanceerd_fields = array(
                array(
                    'key' => 'field_681c77d1b7850',
                    'label' => 'In de head',
                    'name' => 'in_de_head',
                    'type' => 'textarea',
                    'instructions' => 'Scripts die vroeg moeten laden (bijv. GTM, GA)',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681c7a472e609',
                    'label' => 'Direct na body',
                    'name' => 'direct_na_body',
                    'type' => 'textarea',
                    'instructions' => 'Voor noscript/fallback (bijv. GTM iframe)',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681c7a512e60a',
                    'label' => 'Voor de sluitende /body',
                    'name' => 'voor_de_sluitende_body',
                    'type' => 'textarea',
                    'instructions' => 'Chatwidgets, popups, andere niet-kritische code',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681c7a592e60b',
                    'label' => 'Alleen op de homepage',
                    'name' => 'alleen_op_de_homepage',
                    'type' => 'textarea',
                    'instructions' => 'Voor scripts die alleen op de homepage nodig zijn',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681c7a692e60c',
                    'label' => 'Alleen in de footer',
                    'name' => 'alleen_in_de_footer',
                    'type' => 'textarea',
                    'instructions' => 'Voor extra dingen die onderaan geladen mogen worden',
                    'wrapper' => array('width' => '100'),
                ),
                array(
                    'key' => 'field_mkbase_alleen_thema_blokken',
                    'label' => 'Alleen thema-blokken in de editor',
                    'name' => 'alleen_thema_blokken',
                    'type' => 'true_false',
                    'instructions' => 'Verbergt alle standaard WordPress-blokken uit de blokkeneditor, voor alle rollen inclusief beheerders. Alleen de blokken van dit thema en het child thema (blokcategorie "Mediakanjers") blijven beschikbaar, aangevuld met eventueel gekozen "Extra zichtbare blokken". Let op: pagina\'s die standaardblokken gebruiken (paragraaf, afbeelding, kolommen, etc.) kunnen daarna niet meer bewerkt worden.',
                    'default_value' => 1,
                    'ui' => 1,
                    'wrapper' => array('width' => '100'),
                ),
                array(
                    'key' => 'field_mkbase_extra_zichtbare_blokken',
                    'label' => 'Extra zichtbare blokken',
                    'name' => 'extra_zichtbare_blokken',
                    'type' => 'select',
                    'instructions' => 'Kies welke standaardblokken, naast de thema-blokken, ook beschikbaar mogen blijven in de editor.',
                    'choices' => array(),
                    'multiple' => 1,
                    'ui' => 1,
                    'allow_null' => 1,
                    'wrapper' => array('width' => '100'),
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_mkbase_alleen_thema_blokken',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
        );

        // Alleen zichtbaar voor het Mediakanjers-beheeraccount — voor iedereen anders
        // staat de bestandseditor altijd sowieso al uit (zie inc/setup.php).
        if (function_exists('mkbase_is_mediakanjers_admin') && mkbase_is_mediakanjers_admin()) {
            $geavanceerd_fields[] = array(
                'key' => 'field_mkbase_bestandseditor_mediakanjers',
                'label' => 'Bestandseditor toestaan voor Mediakanjers',
                'name' => 'bestandseditor_mediakanjers',
                'type' => 'true_false',
                'instructions' => 'Staat standaard uit, ook voor het Mediakanjers-account. Deze toggle is alleen zichtbaar voor dat account en heeft voor andere gebruikers geen effect — voor iedereen anders blijft de thema-/plugin-bestandseditor altijd uitgeschakeld.',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => array('width' => '100'),
            );
            $geavanceerd_fields[] = array(
                'key' => 'field_mkbase_volgorde_vergrendeld',
                'label' => 'Volgorde vergrendeld (hele website)',
                'name' => 'volgorde_vergrendeld',
                'type' => 'true_false',
                'instructions' => 'Vergrendelt sitebreed, op elke pagina en elk bericht, voor iedereen (ook beheerders van de klant) het toevoegen, verwijderen en verplaatsen van blokken. De inhoud van bestaande blokken blijft wel gewoon bewerkbaar. Deze instelling is alleen zichtbaar voor het Mediakanjers-account — bepaal eerst de volgorde van de blokken op alle pagina\'s voordat je dit aanzet.',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => array('width' => '100'),
            );
        }

        acf_add_local_field_group(array(
            'key' => 'group_681c77cf42eb6',
            'title' => 'Geavanceerd',
            'fields' => $geavanceerd_fields,
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-geavanceerd',
                    ),
                ),
            ),
            'active' => true,
        ));

        /**
         * ====================================
         * MELDINGEN
         * ====================================
         */
        acf_add_local_field_group(array(
            'key' => 'group_68ff64b408f4e',
            'title' => 'Meldingen',
            'fields' => array(
                array(
                    'key' => 'field_68ff64b5b4107',
                    'label' => 'Tekst',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_68ff65a4b4108',
                    'label' => 'Tekst melding',
                    'name' => 'tekst_melding',
                    'type' => 'wysiwyg',
                    'toolbar' => 'full',
                    'media_upload' => 0,
                ),
                array(
                    'key' => 'field_68ff65bcb4109',
                    'label' => 'Data',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_68ff7717d70fc',
                    'label' => 'Data opgeven',
                    'name' => 'data_opgeven',
                    'type' => 'radio',
                    'instructions' => 'Als je geen data opgeeft is de melding altijd zichtbaar.',
                    'choices' => array(
                        'ja' => 'ja',
                        'nee' => 'Nee',
                    ),
                    'default_value' => 'nee',
                    'layout' => 'horizontal',
                ),
                array(
                    'key' => 'field_68ff65c6b410a',
                    'label' => 'Begindatum',
                    'name' => 'begindatum',
                    'type' => 'date_picker',
                    'display_format' => 'd/m/Y',
                    'return_format' => 'Ymd',
                    'wrapper' => array('width' => '50'),
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_68ff7717d70fc',
                                'operator' => '==',
                                'value' => 'ja',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_68ff65dbb410b',
                    'label' => 'Einddatum',
                    'name' => 'einddatum',
                    'type' => 'date_picker',
                    'display_format' => 'd/m/Y',
                    'return_format' => 'Ymd',
                    'wrapper' => array('width' => '50'),
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_68ff7717d70fc',
                                'operator' => '==',
                                'value' => 'ja',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_68ff66555dd92',
                    'label' => 'Opmaak',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_68ff669f5dd93',
                    'label' => 'Achtergrondkleur',
                    'name' => 'achtergrondkleur',
                    'type' => 'color_picker',
                    'default_value' => '#FF0000',
                ),
                array(
                    'key' => 'field_68ff76380e1da',
                    'label' => 'Tekstkleur',
                    'name' => 'tekstkleur',
                    'type' => 'color_picker',
                ),
                array(
                    'key' => 'field_68ff778f5dd88',
                    'label' => 'Zichtbaarheid',
                    'type' => 'tab',
                ),
                array(
                    'key'           => 'field_68ff77975dd89',
                    'label'         => 'Waar zichtbaar',
                    'name'          => 'waar_zichtbaar',
                    'type'          => 'post_object',
                    'post_type'     => array('page'),
                    'post_status'   => array('publish'),
                    'return_format' => 'object',
                    'multiple'      => 1,
                    'allow_null'    => 1,
                    'ui'            => 1,
                ),
                array(
                    'key'          => 'field_6981bd824bb51',
                    'label'        => 'Melding tonen',
                    'name'         => 'melding_tonen',
                    'type'         => 'radio',
                    'instructions' => 'Deze moet op \'Ja\' staan om de meldingen aan te tonen!',
                    'choices'      => array(
                        'Ja'  => 'Ja',
                        'Nee' => 'Nee',
                    ),
                    'default_value' => 'Nee',
                    'layout'        => 'horizontal',
                ),
                array(
                    'key' => 'field_6977204293692',
                    'label' => 'Technisch',
                    'type' => 'tab',
                ),
                array(
                    'key'          => 'field_6977205593693',
                    'label'        => 'CSS overschrijven',
                    'name'         => 'css_overschrijven',
                    'type'         => 'radio',
                    'instructions' => 'Alleen gebruiken voor medewerkers van Mediakanjers!',
                    'choices'      => array(
                        'Ja'  => 'Ja',
                        'Nee' => 'Nee',
                    ),
                    'default_value' => 'Nee',
                    'layout'        => 'horizontal',
                ),
                array(
                    'key' => 'field_voorbeeld_tab',
                    'label' => 'Voorbeeld',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_voorbeeld_preview',
                    'label' => 'Notificatie voorbeeld',
                    'name' => '',
                    'type' => 'message',
                    'message' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-meldingen',
                    ),
                ),
            ),
            'active' => true,
        ));

        /**
         * ====================================
         * MK INSTELLINGEN
         * ====================================
         */
        acf_add_local_field_group(array(
            'key' => 'group_681afe6b4a111',
            'title' => 'MK instellingen',
            'fields' => array(
                array(
                    'key' => 'field_681afe92edf91',
                    'label' => 'Logo',
                    'name' => 'logo',
                    'type' => 'image',
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681afeceedf92',
                    'label' => 'Favicon',
                    'name' => 'favicon',
                    'type' => 'image',
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681afeeaedf94',
                    'label' => 'Bedrijfsnaam',
                    'name' => 'bedrijfsnaam',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_681aff03edf95',
                    'label' => 'Adres',
                    'name' => 'adres',
                    'type' => 'text',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681aff0dedf96',
                    'label' => 'Postcode',
                    'name' => 'postcode',
                    'type' => 'text',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681aff24edf97',
                    'label' => 'Telefoon',
                    'name' => 'telefoon',
                    'type' => 'text',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681aff2bedf98',
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'text',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key' => 'field_681aff31edf99',
                    'label' => 'Mobiel',
                    'name' => 'mobiel',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_681aff7828fc2',
                    'label' => 'Socials',
                    'name' => 'socials',
                    'type' => 'repeater',
                    'button_label' => 'Nieuwe rij',
                    'layout' => 'table',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_681aff7f28fc3',
                            'label' => 'Platform',
                            'name' => 'platform',
                            'type' => 'select',
                            'choices' => array(
                                'discord' => 'Discord',
                                'facebook' => 'Facebook',
                                'instagram' => 'Instagram',
                                'linkedin' => 'Linkedin',
                                'snapchat' => 'Snapchat',
                                'tiktok' => 'Tiktok',
                                'whatsapp' => 'Whatsapp',
                                'x' => 'X',
                                'youtube' => 'Youtube',
                            ),
                        ),
                        array(
                            'key' => 'field_681affea28fc4',
                            'label' => 'URL',
                            'name' => 'url',
                            'type' => 'text',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-website-instellingen',
                    ),
                ),
            ),
            'active' => true,
        ));

        /**
         * ====================================
         * BLOK: LOOP
         * ====================================
         */
        if (!mkbase_block_has_fields('mk/loop'))
        acf_add_local_field_group(array(
            'key'    => 'group_mk_loop_block',
            'title'  => 'Loop blok',
            'fields' => array(
                array(
                    'key'           => 'field_mk_loop_query_loop',
                    'label'         => 'Post type',
                    'name'          => 'query_loop',
                    'type'          => 'select',
                    'choices'       => array('post' => 'Berichten'),
                    'default_value' => 'post',
                    'allow_null'    => 0,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_mk_loop_number_of_posts',
                    'label'         => 'Aantal berichten',
                    'name'          => 'number_of_posts',
                    'type'          => 'number',
                    'default_value' => -1,
                    'min'           => -1,
                    'max'           => 100,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_mk_loop_bericht_object',
                    'label'         => 'Handmatige selectie',
                    'name'          => 'bericht_object',
                    'type'          => 'post_object',
                    'instructions'  => 'Optioneel: selecteer specifieke berichten. Overschrijft het post type en het aantal.',
                    'post_type'     => array(),
                    'post_status'   => array('publish'),
                    'return_format' => 'object',
                    'multiple'      => 1,
                    'allow_null'    => 1,
                    'ui'            => 1,
                ),
                array(
                    'key'           => 'field_mk_loop_grid',
                    'label'         => 'Kolommen',
                    'name'          => 'grid',
                    'type'          => 'select',
                    'choices'       => array(
                        '1' => '1 kolom',
                        '2' => '2 kolommen',
                        '3' => '3 kolommen',
                        '4' => '4 kolommen',
                    ),
                    'default_value' => '3',
                    'allow_null'    => 0,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_mk_loop_breedte',
                    'label'         => 'Breedte',
                    'name'          => 'breedte',
                    'type'          => 'select',
                    'choices'       => array(
                        'contained' => 'Ingesloten',
                        'full'      => 'Volledige breedte',
                    ),
                    'default_value' => 'contained',
                    'allow_null'    => 0,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_mk_loop_toon_knop',
                    'label'         => 'Toon knop',
                    'name'          => 'toon_knop',
                    'type'          => 'true_false',
                    'default_value' => 0,
                    'ui'            => 1,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'               => 'field_mk_loop_knop_tekst',
                    'label'             => 'Knoptekst',
                    'name'              => 'knop_tekst',
                    'type'              => 'text',
                    'default_value'     => 'Meer lezen',
                    'wrapper'           => array('width' => '50'),
                    'conditional_logic' => array(
                        array(
                            array(
                                'field'    => 'field_mk_loop_toon_knop',
                                'operator' => '==',
                                'value'    => '1',
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'block',
                        'operator' => '==',
                        'value'    => 'mk/loop',
                    ),
                ),
            ),
            'active' => true,
        ));

        /**
         * ====================================
         * BLOK: IMAGE SLIDER
         * ====================================
         */
        if (!mkbase_block_has_fields('mk/image-slider'))
        acf_add_local_field_group(array(
            'key'    => 'group_mk_image_slider_block',
            'title'  => 'Image Slider blok',
            'fields' => array(
                array(
                    'key'           => 'field_mk_slider_items_desktop',
                    'label'         => 'Items desktop',
                    'name'          => 'items_desktop',
                    'type'          => 'select',
                    'choices'       => array('1'=>'1','2'=>'2','3'=>'3','4'=>'4'),
                    'default_value' => 4,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_mk_slider_items_tablet',
                    'label'         => 'Items tablet',
                    'name'          => 'items_tablet',
                    'type'          => 'select',
                    'choices'       => array('1'=>'1','2'=>'2','3'=>'3'),
                    'default_value' => 2,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_mk_slider_autoplay',
                    'label'         => 'Autoplay',
                    'name'          => 'autoplay',
                    'type'          => 'true_false',
                    'default_value' => 0,
                    'ui'            => 1,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_mk_slider_aspect_ratio',
                    'label'         => 'Aspect ratio',
                    'name'          => 'aspect_ratio',
                    'type'          => 'select',
                    'choices'       => array(
                        '1/1'  => 'Vierkant - 1:1',
                        '4/3'  => 'Standaard - 4:3',
                        '3/4'  => 'Portret - 3:4',
                        '3/2'  => 'Klassiek - 3:2',
                        '2/3'  => 'Klassiek portret - 2:3',
                        '16/9' => 'Breed - 16:9',
                        '9/16' => 'Groot - 9:16',
                    ),
                    'allow_null'    => 1,
                    'wrapper'       => array('width' => '50'),
                ),
                array(
                    'key'          => 'field_mk_slider_slides',
                    'label'        => 'Slides',
                    'name'         => 'slides',
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'button_label' => 'Nieuwe rij',
                    'sub_fields'   => array(
                        array(
                            'key'           => 'field_mk_slider_afbeelding',
                            'label'         => 'Afbeelding',
                            'name'          => 'afbeelding',
                            'type'          => 'image',
                            'return_format' => 'array',
                            'preview_size'  => 'thumbnail',
                            'wrapper'       => array('width' => '50'),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'block',
                        'operator' => '==',
                        'value'    => 'mk/image-slider',
                    ),
                ),
            ),
            'active' => true,
        ));

        /**
         * ====================================
         * BLOK: KOLOMMEN
         * ====================================
         */
        if (!mkbase_block_has_fields('mk/kolommen'))
        acf_add_local_field_group(array(
            'key'    => 'group_mk_kolommen_block',
            'title'  => 'Kolommen blok',
            'fields' => array(
                array(
                    'key'   => 'field_mk_kolommen_tab_inhoud',
                    'label' => 'Inhoud',
                    'type'  => 'tab',
                ),
                array(
                    'key'           => 'field_mk_kolommen_verhouding',
                    'label'         => 'Verhouding',
                    'name'          => 'verhouding',
                    'type'          => 'select',
                    'instructions'  => 'Bepaalt het aantal kolommen en de breedteverdeling. Voeg hieronder evenveel kolommen toe als gekozen — de eerste kolom die je toevoegt komt links op de pagina, de laatste komt rechts.',
                    'choices'       => array(
                        '100'         => '1 kolom — 100%',
                        '50-50'       => '2 kolommen — 50 / 50',
                        '66-33'       => '2 kolommen — 66 / 33',
                        '33-66'       => '2 kolommen — 33 / 66',
                        '25-75'       => '2 kolommen — 25 / 75',
                        '75-25'       => '2 kolommen — 75 / 25',
                        '33-33-33'    => '3 kolommen — 33 / 33 / 33',
                        '25-50-25'    => '3 kolommen — 25 / 50 / 25',
                        '25-25-25-25' => '4 kolommen — 25 / 25 / 25 / 25',
                    ),
                    'default_value' => '50-50',
                    'allow_null'    => 0,
                ),
                array(
                    'key'          => 'field_mk_kolommen_kolommen',
                    'label'        => 'Kolommen',
                    'name'         => 'kolommen',
                    'type'         => 'repeater',
                    'instructions' => 'De bovenste kolom hieronder komt links op de pagina te staan, de onderste rechts.',
                    'layout'       => 'block',
                    'button_label' => 'Kolom toevoegen',
                    'min'          => 1,
                    'max'          => 4,
                    'collapsed'    => 'field_mk_kolom_type',
                    'sub_fields'   => array(
                        array(
                            'key'           => 'field_mk_kolom_type',
                            'label'         => 'Type',
                            'name'          => 'type',
                            'type'          => 'checkbox',
                            'instructions'  => 'Kies één of meerdere — bijv. Tekst + Knop voor tekst met een button eronder. De velden verschijnen hieronder in deze volgorde.',
                            'choices'       => array(
                                'titel'       => 'Titel',
                                'tekst'       => 'Tekst',
                                'afbeelding'  => 'Afbeelding',
                                'video'       => 'Video',
                                'knop'        => 'Knop / CTA',
                                'icoon_tekst' => 'Icoon + tekst',
                                'cijfer'      => 'Cijfer / statistiek',
                            ),
                            'default_value' => array('tekst'),
                            'layout'        => 'horizontal',
                            'allow_custom'  => 0,
                        ),
                        array(
                            'key'               => 'field_mk_kolom_titel_tekst',
                            'label'             => 'Titel',
                            'name'              => 'titel_tekst',
                            'type'              => 'text',
                            'instructions'      => 'Verplicht — zonder tekst blijft deze titel leeg op de pagina.',
                            'required'          => 1,
                            'wrapper'           => array('width' => '66'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'titel'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_titel_niveau',
                            'label'             => 'Kopniveau',
                            'name'              => 'titel_niveau',
                            'type'              => 'select',
                            'instructions'      => 'Voor de opbouw van de pagina (SEO/toegankelijkheid) — bepaalt niet het lettertype.',
                            'choices'           => array(
                                'h1' => 'H1',
                                'h2' => 'H2',
                                'h3' => 'H3',
                                'h4' => 'H4',
                                'h5' => 'H5',
                                'h6' => 'H6',
                            ),
                            'default_value'     => 'h2',
                            'allow_null'        => 0,
                            'wrapper'           => array('width' => '34'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'titel'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_tekst',
                            'label'             => 'Tekst',
                            'name'              => 'tekst',
                            'type'              => 'wysiwyg',
                            'instructions'      => 'Verplicht — zonder tekst blijft deze kolom leeg op de pagina.',
                            'required'          => 1,
                            'toolbar'           => 'basic',
                            'media_upload'      => 0,
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'tekst'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_afbeelding',
                            'label'             => 'Afbeelding',
                            'name'              => 'afbeelding',
                            'type'              => 'image',
                            'instructions'      => 'Verplicht — zonder afbeelding blijft deze kolom leeg op de pagina.',
                            'required'          => 1,
                            'return_format'     => 'array',
                            'preview_size'      => 'medium',
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'afbeelding'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_video',
                            'label'             => 'Video',
                            'name'              => 'video',
                            'type'              => 'oembed',
                            'instructions'      => 'Verplicht — plak hier een YouTube- of Vimeo-link.',
                            'required'          => 1,
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'video'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_knop',
                            'label'             => 'Knop',
                            'name'              => 'knop',
                            'type'              => 'link',
                            'instructions'      => 'Verplicht — kies een tekst en link voor de knop.',
                            'required'          => 1,
                            'wrapper'           => array('width' => '50'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'knop'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_knop_stijl',
                            'label'             => 'Knopstijl',
                            'name'              => 'knop_stijl',
                            'type'              => 'button_group',
                            'choices'           => array('primair' => 'Primair', 'secundair' => 'Secundair'),
                            'default_value'     => 'primair',
                            'allow_null'        => 0,
                            'wrapper'           => array('width' => '50'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'knop'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_icoon',
                            'label'             => 'Icoon',
                            'name'              => 'icoon',
                            'type'              => 'image',
                            'instructions'      => 'Verplicht.',
                            'required'          => 1,
                            'return_format'     => 'array',
                            'preview_size'      => 'thumbnail',
                            'wrapper'           => array('width' => '50'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'icoon_tekst'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_icoon_tekst',
                            'label'             => 'Tekst',
                            'name'              => 'icoon_tekst',
                            'type'              => 'text',
                            'instructions'      => 'Verplicht.',
                            'required'          => 1,
                            'wrapper'           => array('width' => '50'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'icoon_tekst'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_cijfer_waarde',
                            'label'             => 'Cijfer',
                            'name'              => 'cijfer_waarde',
                            'type'              => 'text',
                            'instructions'      => 'Verplicht. Bijv. "500+"',
                            'required'          => 1,
                            'wrapper'           => array('width' => '50'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'cijfer'),
                                ),
                            ),
                        ),
                        array(
                            'key'               => 'field_mk_kolom_cijfer_label',
                            'label'             => 'Label',
                            'name'              => 'cijfer_label',
                            'type'              => 'text',
                            'instructions'      => 'Verplicht. Bijv. "tevreden klanten"',
                            'required'          => 1,
                            'wrapper'           => array('width' => '50'),
                            'conditional_logic' => array(
                                array(
                                    array('field' => 'field_mk_kolom_type', 'operator' => '==', 'value' => 'cijfer'),
                                ),
                            ),
                        ),
                        array(
                            'key'                   => 'field_mk_kolom_achtergrond',
                            'label'                 => 'Achtergrondkleur (dit item)',
                            'name'                  => 'kolom_achtergrond',
                            'type'                  => 'color_picker',
                            'instructions'          => 'Optioneel. Kleurt alleen deze kolom.',
                            'show_custom_palette'   => 1,
                            'custom_palette_source' => 'themejson',
                            'enable_opacity'        => 1,
                            'wrapper'               => array('width' => '33'),
                        ),
                        array(
                            'key'                   => 'field_mk_kolom_tekstkleur',
                            'label'                 => 'Tekstkleur (dit item)',
                            'name'                  => 'kolom_tekstkleur',
                            'type'                  => 'color_picker',
                            'instructions'          => 'Optioneel — handig bij een donkere achtergrondkleur.',
                            'show_custom_palette'   => 1,
                            'custom_palette_source' => 'themejson',
                            'wrapper'               => array('width' => '33'),
                        ),
                        array(
                            'key'           => 'field_mk_kolom_afgerond',
                            'label'         => 'Afgeronde hoeken',
                            'name'          => 'kolom_afgerond',
                            'type'          => 'true_false',
                            'default_value' => 0,
                            'ui'            => 1,
                            'wrapper'       => array('width' => '34'),
                        ),
                        array(
                            'key'           => 'field_mk_kolom_padding',
                            'label'         => 'Padding (dit item)',
                            'name'          => 'kolom_padding',
                            'type'          => 'button_group',
                            'instructions'  => 'Ruimte binnen deze kolom, rondom de inhoud.',
                            'choices'       => array(
                                'geen'     => 'Geen',
                                'klein'    => 'Klein',
                                'normaal'  => 'Normaal',
                                'groot'    => 'Groot',
                            ),
                            'default_value' => 'geen',
                            'allow_null'    => 0,
                            'wrapper'       => array('width' => '50'),
                        ),
                        array(
                            'key'           => 'field_mk_kolom_content_uitlijning',
                            'label'         => 'Verticale uitlijning content (dit item)',
                            'name'          => 'kolom_uitlijning',
                            'type'          => 'button_group',
                            'instructions'  => 'Handig als deze kolom door "Uitgerekt" hoger wordt dan de inhoud — bepaalt waar de inhoud dan in de kolom komt te staan.',
                            'choices'       => array(
                                'boven'  => 'Boven',
                                'midden' => 'Midden',
                                'onder'  => 'Onder',
                            ),
                            'default_value' => 'boven',
                            'allow_null'    => 0,
                            'wrapper'       => array('width' => '50'),
                        ),
                    ),
                ),
                array(
                    'key'   => 'field_mk_kolommen_tab_opmaak',
                    'label' => 'Opmaak',
                    'type'  => 'tab',
                ),
                array(
                    'key'           => 'field_mk_kolommen_uitlijning',
                    'label'         => 'Verticale uitlijning',
                    'name'          => 'verticale_uitlijning',
                    'type'          => 'button_group',
                    'instructions'  => 'Hoe kolommen zich verticaal uitlijnen als ze niet even hoog zijn.',
                    'choices'       => array(
                        'boven'     => 'Boven',
                        'midden'    => 'Midden',
                        'onder'     => 'Onder',
                        'uitgerekt' => 'Uitgerekt',
                    ),
                    'default_value' => 'boven',
                    'allow_null'    => 0,
                ),
                array(
                    'key'                   => 'field_mk_kolommen_achtergrond_breed',
                    'label'                 => 'Achtergrondkleur — volledige breedte',
                    'name'                  => 'achtergrond_breed',
                    'type'                  => 'color_picker',
                    'instructions'          => 'Optioneel. Kleurband die tot de randen van het scherm loopt.',
                    'show_custom_palette'   => 1,
                    'custom_palette_source' => 'themejson',
                    'enable_opacity'        => 1,
                    'wrapper'               => array('width' => '50'),
                ),
                array(
                    'key'                   => 'field_mk_kolommen_achtergrond_grid',
                    'label'                 => 'Achtergrondkleur — binnen grid',
                    'name'                  => 'achtergrond_grid',
                    'type'                  => 'color_picker',
                    'instructions'          => 'Optioneel. Blijft binnen de reguliere paginabreedte — te combineren met de kleur hierboven.',
                    'show_custom_palette'   => 1,
                    'custom_palette_source' => 'themejson',
                    'enable_opacity'        => 1,
                    'wrapper'               => array('width' => '50'),
                ),
                array(
                    'key'                   => 'field_mk_kolommen_tekstkleur',
                    'label'                 => 'Tekstkleur',
                    'name'                  => 'tekstkleur',
                    'type'                  => 'color_picker',
                    'instructions'          => 'Optioneel — handig als de achtergrondkleur zwarte tekst onleesbaar maakt.',
                    'show_custom_palette'   => 1,
                    'custom_palette_source' => 'themejson',
                ),
                array(
                    'key'           => 'field_mk_kolommen_padding',
                    'label'         => 'Padding',
                    'name'          => 'padding',
                    'type'          => 'button_group',
                    'instructions'  => 'Binnenruimte rondom de inhoud van het blok.',
                    'choices'       => array(
                        'geen'     => 'Geen',
                        'klein'    => 'Klein',
                        'normaal'  => 'Normaal',
                        'groot'    => 'Groot',
                    ),
                    'default_value' => 'geen',
                    'allow_null'    => 0,
                ),
                array(
                    'key'           => 'field_mk_kolommen_afgerond',
                    'label'         => 'Afgeronde hoeken',
                    'name'          => 'blok_afgerond',
                    'type'          => 'true_false',
                    'instructions'  => 'Rondt de hoeken af van de achtergrondkleur "binnen grid" hierboven.',
                    'default_value' => 0,
                    'ui'            => 1,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'block',
                        'operator' => '==',
                        'value'    => 'mk/kolommen',
                    ),
                ),
            ),
            'active' => true,
        ));

        /**
         * ====================================
         * 404
         * ====================================
         */
        acf_add_local_field_group(array(
            'key'    => 'group_69f0a9ec6e104',
            'title'  => '404',
            'fields' => array(
                array(
                    'key'     => 'field_69f0a9ed41264',
                    'label'   => 'Titel',
                    'name'    => 'titel',
                    'type'    => 'text',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key'     => 'field_69f0aa1541265',
                    'label'   => 'Tekst',
                    'name'    => 'tekst',
                    'type'    => 'textarea',
                    'wrapper' => array('width' => '50'),
                ),
                array(
                    'key'           => 'field_69f0aa2a41266',
                    'label'         => 'Afbeelding',
                    'name'          => 'afbeelding',
                    'type'          => 'image',
                    'return_format' => 'url',
                    'preview_size'  => 'medium',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'acf-options-404',
                    ),
                ),
            ),
            'active' => true,
        ));

    });

    add_filter('acf/load_field/key=field_mkbase_extra_zichtbare_blokken', function($field) {
        $theme_blocks = function_exists('mkbase_theme_block_names') ? mkbase_theme_block_names() : [];
        $choices = [];
        foreach (WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block_type) {
            if (in_array($name, $theme_blocks, true)) continue;
            $choices[$name] = ($block_type->title ?: $name) . ' (' . $name . ')';
        }
        asort($choices);
        $field['choices'] = $choices;
        return $field;
    });

    add_filter('acf/load_field/key=field_mk_loop_query_loop', function($field) {
        $post_types = get_post_types(['public' => true], 'objects');
        $choices = [];
        foreach ($post_types as $pt) {
            if ($pt->name === 'attachment') continue;
            $choices[$pt->name] = $pt->labels->name;
        }
        $field['choices'] = $choices;
        return $field;
    });

    add_filter('acf/load_field/key=field_voorbeeld_preview', function($field) {
        $bgcolor          = get_field('achtergrondkleur', 'option') ?: '#ff0000';
        $txtcolor         = get_field('tekstkleur', 'option') ?: '#ffffff';
        $txt              = get_field('tekst_melding', 'option');
        $shownotification = get_field('melding_tonen', 'option');

        if (empty($txt)) {
            $field['message'] = '<p><em>Er is nog geen meldingstekst ingesteld.</em></p>';
        } else {
            $field['message'] = '<div style="background:' . esc_attr($bgcolor) . '; color:' . esc_attr($txtcolor) . '; padding:12px 20px; border-radius:4px; font-size:14px;">'
                . wp_kses_post($txt)
                . '</div>';

            if ($shownotification !== 'Ja') {
                $field['message'] .= '<p style="color:#888; margin-top:8px; font-style:italic;">Let op: "Melding tonen" staat op Nee — de melding is niet zichtbaar op de website.</p>';
            }
        }

        return $field;
    });
?>