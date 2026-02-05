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
                    'key' => 'field_68ff77975dd89',
                    'label' => 'Waar zichtbaar',
                    'name' => 'waar_zichtbaar',
                    'aria-label' => '',
                    'type' => 'post_object',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'post_type' => array(0 => 'page',),
                    'post_status' => array(0 => 'publish',),
                    'taxonomy' => '',
                    'return_format' => 'object',
                    'multiple' => 1,
                    'allow_null' => 1,
                    'allow_in_bindings' => 0,
                    'bidirectional' => 0,
                    'ui' => 1,
                    'bidirectional_target' => array(
                    ),
                ),
                array(
                    'key' => 'field_6981bd824bb51',
                    'label' => 'Melding tonen',
                    'name' => 'melding_tonen',
                    'aria-label' => '',
                    'type' => 'radio',
                    'instructions' => 'Deze moet op \'Ja\' staan om de meldingen aan te tonen!',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'choices' => array(
                        'Ja' => 'Ja',
                        'Nee' => 'Nee',
                    ),
                    'default_value' => 'Nee',
                    'return_format' => 'value',
                    'allow_null' => 0,
                    'other_choice' => 0,
                    'allow_in_bindings' => 0,
                    'layout' => 'horizontal',
                    'save_other_choice' => 0,
                ),
                array(
                    'key' => 'field_6977204293692',
                    'label' => 'Technisch',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_6977205593693',
                    'label' => 'CSS overschrijven',
                    'name' => 'css_overschrijven',
                    'aria-label' => '',
                    'type' => 'radio',
                    'instructions' => 'Alleen gebruiken voor medewerkers van Mediakanjers!',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'choices' => array(
                        'Ja' => 'Ja',
                        'Nee' => 'Nee',
                    ),
                    'default_value' => 'Nee',
                    'return_format' => 'value',
                    'allow_null' => 0,
                    'other_choice' => 0,
                    'allow_in_bindings' => 0,
                    'layout' => 'horizontal',
                    'save_other_choice' => 0,
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
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => '',
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
    });
?>