<?php
    /**
     * ACF Local Field Groups
     * Geavanceerd – Meldingen – MK instellingen
     */
    add_action('acf/include_fields', function () {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        /**
         * ====================================
         * GEAVANCEERD
         * ====================================
         */
        acf_add_local_field_group(array(
            'key' => 'group_681c77cf42eb6',
            'title' => 'Geavanceerd',
            'fields' => array(
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
            ),
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
                    'ui'            => 1,
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
                    'ui'            => 1,
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
                    'ui'            => 1,
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