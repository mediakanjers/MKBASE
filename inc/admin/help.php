<?php
    // =============================================================================
    // Uitleg met ? bij elk veld (MK-pagina's en de blokken in de editor)
    // =============================================================================
    // Per veldsleutel: 'help' (achter de ?), 'below' (korte regel onder het veld, vervangt
    // de ACF-instructie), 'docs' (onderwerp/sectie in Documentatie voor "Meer uitleg") en
    // eventueel 'label' (duidelijker label). Eén plek voor alle teksten.
    // JS en CSS: assets/js/mk-help.js en assets/css/mk-help.css.

    function mkbase_help_texts() {
        $size = 'Groter is scherper, maar laadt trager. Kies het formaat dat past bij hoe breed de afbeelding op de pagina staat; telefoons krijgen vanzelf een kleinere versie.';
        return [
            // ── Website instellingen ──
            'field_681afe92edf91' => ['help' => 'Staat linksboven in de header en in het mobiele menu. Een SVG blijft op elk scherm scherp; anders een PNG van maximaal 600 px breed.', 'docs' => 'instellingen/logo'],
            'field_681afeceedf92' => ['help' => 'Het kleine icoon in het tabblad van de browser en bij bladwijzers. Kies een vierkante afbeelding van minimaal 512 × 512 px, zonder tekst.', 'docs' => 'instellingen/logo'],
            'field_681afeeaedf94' => ['help' => 'Staat vetgedrukt boven je contactgegevens in de footer.', 'below' => '', 'docs' => 'instellingen/bedrijf'],
            'field_681aff03edf95' => ['help' => 'Straat en huisnummer, zoals ze in de footer moeten staan.', 'below' => 'Bijv. Marktstraat 12', 'docs' => 'instellingen/bedrijf'],
            'field_681aff0dedf96' => ['help' => 'Postcode en plaats. Staan in de footer bij je adres.', 'below' => 'Bijv. 7511 AB Enschede', 'docs' => 'instellingen/bedrijf'],
            'field_681aff24edf97' => ['help' => 'Staat in de footer. Op een telefoon kunnen bezoekers erop tikken om direct te bellen.', 'below' => 'Bijv. +31 53 478 61 47', 'docs' => 'instellingen/bedrijf'],
            'field_681aff2bedf98' => ['help' => 'Staat in de footer; bezoekers kunnen erop klikken om je te mailen. Kies een adres dat iemand leest.', 'below' => '', 'docs' => 'instellingen/bedrijf'],
            'field_681aff31edf99' => ['help' => 'Een tweede nummer, bijvoorbeeld voor spoed. Laat leeg als je er geen hebt.', 'below' => 'Optioneel', 'docs' => 'instellingen/bedrijf'],
            'field_681aff7f28fc3' => ['help' => 'Kies het kanaal; het icoon komt vanzelf.', 'below' => '', 'docs' => 'instellingen/socials'],
            'field_681affea28fc4' => ['help' => 'Het volledige adres van je pagina, inclusief https://.', 'below' => '', 'label' => 'Link', 'docs' => 'instellingen/socials'],

            // ── Meldingen ──
            'field_mk_melding_naam'         => ['help' => 'Een naam om de melding terug te vinden, bijv. "Feestdagen 2026". Bezoekers zien hem niet.', 'below' => 'alleen voor jou', 'docs' => 'meldingen/melding-inhoud'],
            'field_mk_melding_tekst'        => ['help' => 'De tekst in de balk. Eén zin leest het best, zeker op mobiel. Links krijgen vanzelf de tekstkleur.', 'below' => '', 'docs' => 'meldingen/melding-inhoud'],
            'field_mk_melding_knop'         => ['help' => 'Een knop achter de tekst, bijv. "Bekijk de actie". Laat leeg voor geen knop.', 'below' => 'optioneel', 'docs' => 'meldingen/melding-inhoud'],
            'field_mk_melding_planning'     => ['help' => 'Uit: zichtbaar zolang de melding aan staat. Aan: alleen tussen de begin- en eindtijd.', 'below' => 'Alleen zichtbaar tussen de begin- en eindtijd', 'docs' => 'meldingen/melding-planning'],
            'field_mk_melding_start'        => ['help' => 'Vanaf dit moment staat de melding op de website, in de tijdzone van de website. De cache wordt dan vanzelf geleegd.', 'below' => 'Leeg: direct', 'docs' => 'meldingen/melding-planning'],
            'field_mk_melding_eind'         => ['help' => 'Tot dit moment staat de melding op de website. Daarna verdwijnt hij vanzelf, maar hij blijft bewaard.', 'below' => 'Leeg: geen einde', 'docs' => 'meldingen/melding-planning'],
            'field_mk_melding_stijl'        => ['help' => 'Kant-en-klare kleuren: Info, Succes, Waarschuwing of Actie. Kies Eigen kleuren om zelf de kleuren te bepalen.', 'below' => '', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_achtergrond'  => ['help' => 'De kleur van de balk. Kies een kleur waarop de tekst goed te lezen is; het voorbeeld laat het meteen zien.', 'below' => '', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_tekstkleur'   => ['help' => 'Geldt ook voor links en het kruisje.', 'below' => '', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_positie'      => ['help' => 'Een balk bovenaan of onderaan, een pop-up in het midden van het scherm, of een zwevend blok op een vaste plek. Een pop-up en elke plek van een blok kunnen maar één melding tegelijk hebben.', 'below' => '', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_backdrop'     => ['help' => 'Maakt de pagina achter de pop-up donker, zodat de bezoeker eerst reageert. Uit: de pagina blijft gewoon te gebruiken.', 'below' => '', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_scroll_blok'  => ['help' => 'Zolang de pop-up open is, kan de bezoeker de pagina erachter niet scrollen. Uit: de pagina scrolt gewoon mee.', 'below' => '', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_blok_plek'    => ['help' => 'Waar het blok op het scherm staat. Het blijft daar staan tijdens het scrollen. Op een telefoon staan blokken altijd onderaan, over de volle breedte.', 'below' => '', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_blok_breedte' => ['help' => 'Hoe breed het blok is op een groot scherm. 360 px past goed voor een korte tekst met een knop.', 'below' => 'Standaard 360 px', 'docs' => 'meldingen/melding-weergave'],
            'field_mk_melding_tonen'        => ['help' => 'Direct bij het openen van de pagina, na een aantal seconden, of pas als de bezoeker een stuk naar beneden heeft gescrold.', 'below' => '', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_tonen_sec'    => ['help' => 'Na hoeveel seconden op de pagina de melding verschijnt.', 'below' => '', 'label' => 'Na hoeveel seconden', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_tonen_px'     => ['help' => 'Na hoeveel pixels scrollen de melding verschijnt. Een schermhoogte is ongeveer 800 px.', 'below' => '', 'label' => 'Na hoeveel scrollen', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_auto'         => ['help' => 'De melding verdwijnt vanzelf. Zolang de bezoeker er met de muis of het toetsenbord op staat, wacht hij. Daarna geldt "Daarna weer tonen".', 'below' => '', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_auto_sec'     => ['help' => 'Hoe lang de melding zichtbaar blijft.', 'below' => '', 'label' => 'Verdwijnt na', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_sluitbaar'    => ['help' => 'Toont een kruisje waarmee bezoekers de melding wegklikken. Zet uit voor iets belangrijks, zoals een storing. Een pop-up heeft altijd een kruisje; daar staat deze keuze niet.', 'below' => 'Bezoeker kan hem wegklikken', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_opnieuw'      => ['help' => 'Wanneer een weggeklikte melding terugkomt: op de volgende pagina, bij een volgend bezoek, na een aantal dagen of nooit. Pas je tekst, knop of planning aan, dan ziet iedereen hem opnieuw.', 'below' => '', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_dagen'        => ['help' => 'Na hoeveel dagen een weggeklikte melding terugkomt.', 'below' => '', 'docs' => 'meldingen/melding-gedrag'],
            'field_mk_melding_zichtbaarheid'=> ['help' => 'Op de hele website, alleen op de pagina\'s die je kiest, of overal behalve die pagina\'s.', 'below' => '', 'docs' => 'meldingen/melding-zichtbaarheid'],
            'field_mk_melding_paginas'      => ['help' => 'Pagina\'s, berichten of andere items. Typ om te zoeken.', 'below' => '', 'docs' => 'meldingen/melding-zichtbaarheid'],
            'field_6977205593693'           => ['help' => 'Ja: het child theme regelt de opmaak zelf en de header schuift niet mee. Laat op Nee, tenzij een developer het zo heeft gebouwd.', 'below' => 'Alleen Mediakanjers', 'docs' => 'meldingen/melding-technisch'],

            // ── 404 ──
            'field_69f0a9ed41264'             => ['help' => 'De kop die bezoekers zien als een pagina niet bestaat, bijv. "Deze pagina bestaat niet (meer)".', 'below' => '', 'docs' => '404/404-inhoud'],
            'field_69f0aa1541265'             => ['help' => 'Leg kort uit wat er mis ging en waar de bezoeker heen kan, zoals de homepage.', 'below' => '', 'docs' => '404/404-inhoud'],
            'field_69f0aa2a41266'             => ['help' => 'Een afbeelding op de 404-pagina. Een vriendelijke illustratie maakt een foutpagina minder vervelend.', 'below' => 'Optioneel', 'docs' => '404/404-inhoud'],
            'field_mk_404_afbeeldingsformaat' => ['help' => $size, 'below' => '', 'docs' => '404/404-inhoud'],

            // ── Geavanceerd ──
            'field_681c77d1b7850' => ['help' => 'Code die zo vroeg mogelijk moet laden. Plak precies wat de dienst geeft, inclusief de <script>-tags.', 'below' => 'Bijv. Google Tag Manager', 'docs' => 'geavanceerd/head'],
            'field_681c7a472e609' => ['help' => 'Code die direct aan het begin van de pagina moet staan. Tag Manager vraagt hierom.', 'below' => 'Bijv. het noscript-deel van Tag Manager', 'docs' => 'geavanceerd/body-open'],
            'field_681c7a512e60a' => ['help' => 'Code die als laatste laadt, op elke pagina, zodat de pagina eerst verschijnt. Hier hoort de meeste code.', 'below' => 'Bijv. een chatwidget', 'label' => 'Onderaan de pagina', 'docs' => 'geavanceerd/body-close'],
            'field_681c7a592e60b' => ['help' => 'Onderaan de pagina, maar alleen op de voorpagina.', 'below' => '', 'docs' => 'geavanceerd/homepage-only'],
            'field_mkbase_alleen_thema_blokken'      => ['help' => 'Toont in de editor alleen de Mediakanjers-blokken, plus de blokken die je hieronder kiest. Pagina\'s met standaardblokken zijn daarna niet meer te bewerken.', 'below' => 'Verbergt de standaard WordPress-blokken, voor iedereen', 'docs' => 'geavanceerd/editor'],
            'field_mkbase_extra_zichtbare_blokken'   => ['help' => 'Standaardblokken die naast de thema-blokken beschikbaar blijven, bijv. Kop of Lijst.', 'below' => '', 'docs' => 'geavanceerd/editor'],
            'field_mkbase_bestandseditor_mediakanjers' => ['help' => 'Zet de thema- en plugin-bestandseditor aan, alleen voor het account mediakanjers. Voor iedereen anders blijft hij altijd uit.', 'below' => 'Alleen Mediakanjers', 'docs' => 'geavanceerd/editor'],
            'field_mkbase_volgorde_vergrendeld'      => ['help' => 'Op elke pagina kan niemand nog blokken toevoegen, verwijderen of verplaatsen; de inhoud blijft wel te bewerken. Zet de volgorde eerst goed voordat je dit aanzet.', 'below' => 'Alleen Mediakanjers', 'docs' => 'geavanceerd/editor'],

            // ── Blok: Kolommen ──
            'field_mk_kolommen_verhouding'      => ['help' => 'Het aantal kolommen en hoe breed ze zijn. Ontbrekende kolommen worden vanzelf toegevoegd.', 'below' => '', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolommen_kolommen'        => ['help' => 'De bovenste kolom staat links op de pagina, de onderste rechts. Sleep aan de puntjes om de volgorde te wijzigen.', 'below' => '', 'label' => 'Kolommen · van links naar rechts', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_type'               => ['help' => 'Wat er in deze kolom komt. Kies er meerdere, bijv. Tekst + Knop; ze verschijnen in deze volgorde.', 'below' => '', 'label' => 'Inhoud', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_titel_tekst'        => ['help' => 'De kop van deze kolom.', 'below' => 'Verplicht', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_titel_niveau'       => ['help' => 'Voor de opbouw van de pagina, voor Google en schermlezers; het verandert niet hoe de kop eruitziet. Gebruik één H1 per pagina.', 'below' => '', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_tekst'              => ['help' => 'De tekst van de kolom. Met de knoppen boven het veld maak je tekst vet of voeg je een link of lijst toe.', 'below' => 'Verplicht', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_afbeelding'         => ['help' => 'De afbeelding in deze kolom. Vul de alt-tekst in bij Media.', 'below' => 'Verplicht', 'docs' => 'afbeeldingen/afb-alt'],
            'field_mk_kolom_afbeeldingsformaat' => ['help' => 'Hoe groot de afbeelding getoond wordt, nooit breder dan de kolom. Origineel vult de hele kolom.', 'below' => '', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_video'              => ['help' => 'Plak een YouTube- of Vimeo-link. De video laadt pas na een klik op afspelen; dat houdt de pagina snel en privacyvriendelijk.', 'below' => 'Verplicht', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_knop'               => ['help' => 'De tekst op de knop en de pagina waar hij naartoe gaat.', 'below' => 'Verplicht', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_knop_stijl'         => ['help' => 'Hoe de knop eruitziet. De stijlen komen uit de huisstijl van de website.', 'below' => '', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_icoon'              => ['help' => 'Een klein icoon naast de tekst, het liefst een SVG.', 'below' => 'Verplicht', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_icoon_tekst'        => ['help' => 'De korte tekst naast het icoon.', 'below' => 'Verplicht', 'label' => 'Tekst bij icoon', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_cijfer_waarde'      => ['help' => 'Een opvallend getal, zoals een aantal klanten of jaren ervaring.', 'below' => 'Bijv. 500+', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_cijfer_label'       => ['help' => 'De tekst onder het cijfer.', 'below' => 'Bijv. tevreden klanten', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_achtergrond'        => ['help' => 'Kleurt alleen deze kolom.', 'below' => 'Optioneel', 'label' => 'Achtergrondkleur kolom', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_tekstkleur'         => ['help' => 'Handig bij een donkere achtergrondkleur.', 'below' => 'Optioneel', 'label' => 'Tekstkleur kolom', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_afgerond'           => ['help' => 'Rondt de hoeken van deze kolom af; zichtbaar als de kolom een achtergrondkleur heeft.', 'below' => '', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_padding'            => ['help' => 'Ruimte binnen deze kolom, rondom de inhoud.', 'below' => '', 'label' => 'Binnenruimte kolom', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolom_content_uitlijning' => ['help' => 'Waar de inhoud staat als de kolom hoger is dan de inhoud: boven, midden of onder.', 'below' => '', 'label' => 'Inhoud uitlijnen', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolommen_uitlijning'      => ['help' => 'Hoe kolommen naast elkaar uitlijnen als ze niet even hoog zijn. Uitgerekt maakt ze even hoog.', 'below' => '', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolommen_achtergrond_breed' => ['help' => 'Een kleurband achter het hele blok, tot de randen van het scherm.', 'below' => 'Optioneel', 'label' => 'Kleur van de hele strook', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolommen_achtergrond_grid'  => ['help' => 'Een kleurvlak direct achter de kolommen, binnen de gewone paginabreedte. Te combineren met de kleur van de strook.', 'below' => 'Optioneel', 'label' => 'Kleur achter de kolommen', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolommen_tekstkleur'      => ['help' => 'Voor het hele blok. Handig bij een donkere achtergrond.', 'below' => 'Optioneel', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolommen_padding'         => ['help' => 'Ruimte rondom de inhoud van het blok.', 'below' => '', 'label' => 'Binnenruimte', 'docs' => 'blokken/blok-kolommen'],
            'field_mk_kolommen_afgerond'        => ['help' => 'Rondt de hoeken af van de kleur achter de kolommen.', 'below' => '', 'docs' => 'blokken/blok-kolommen'],

            // ── Blok: Loop ──
            'field_mk_loop_query_loop'         => ['help' => 'Welke soort inhoud getoond wordt, bijv. Berichten.', 'below' => '', 'label' => 'Soort inhoud', 'docs' => 'blokken/blok-loop'],
            'field_mk_loop_number_of_posts'    => ['help' => 'Hoeveel berichten er maximaal staan; de nieuwste eerst. Zet "Alles tonen" aan om ze allemaal te laten zien.', 'below' => '', 'docs' => 'blokken/blok-loop'],
            'field_mk_loop_bericht_object'     => ['help' => 'Kies zelf welke berichten getoond worden, in jouw volgorde. Dit gaat voor de twee velden hierboven.', 'below' => 'Optioneel', 'docs' => 'blokken/blok-loop'],
            'field_mk_loop_grid'               => ['help' => 'Hoeveel kaarten naast elkaar staan op een groot scherm. Op kleinere schermen worden het er vanzelf minder.', 'below' => '', 'docs' => 'blokken/blok-loop'],
            'field_mk_loop_breedte'            => ['help' => 'Ingesloten: binnen de gewone paginabreedte. Volledige breedte: tot de randen van het scherm.', 'below' => '', 'docs' => 'blokken/blok-loop'],
            'field_mk_loop_afbeeldingsformaat' => ['help' => $size, 'below' => '', 'docs' => 'blokken/blok-loop'],
            'field_mk_loop_toon_knop'          => ['help' => 'Toont een knop op elke kaart die naar het bericht gaat.', 'below' => '', 'docs' => 'blokken/blok-loop'],
            'field_mk_loop_knop_tekst'         => ['help' => 'De tekst op de knop.', 'below' => 'Bijv. Lees meer', 'docs' => 'blokken/blok-loop'],

            // ── Blok: Image Slider ──
            'field_mk_slider_items_desktop'      => ['help' => 'Hoeveel afbeeldingen tegelijk te zien zijn op een groot scherm.', 'below' => '', 'label' => 'Naast elkaar (computer)', 'docs' => 'blokken/blok-slider'],
            'field_mk_slider_items_tablet'       => ['help' => 'Hoeveel afbeeldingen tegelijk op een tablet. Op een telefoon zie je er één.', 'below' => 'Op een telefoon altijd 1', 'label' => 'Naast elkaar (tablet)', 'docs' => 'blokken/blok-slider'],
            'field_mk_slider_autoplay'           => ['help' => 'De slider schuift vanzelf door. Bezoekers kunnen ook zelf bladeren.', 'below' => '', 'label' => 'Automatisch afspelen', 'docs' => 'blokken/blok-slider'],
            'field_mk_slider_aspect_ratio'       => ['help' => 'De vorm van elke slide, bijv. vierkant of breed (16:9). Laat leeg om de eigen vorm van de afbeeldingen te houden.', 'below' => '', 'label' => 'Vorm', 'docs' => 'blokken/blok-slider'],
            'field_mk_slider_lightbox'           => ['help' => 'Klikken opent de afbeelding groot, en bezoekers kunnen door alle slides bladeren.', 'below' => '', 'label' => 'Groot openen bij klikken', 'docs' => 'blokken/blok-slider'],
            'field_mk_slider_afbeeldingsformaat' => ['help' => $size, 'below' => '', 'docs' => 'blokken/blok-slider'],
            'field_mk_slider_slides'             => ['help' => 'De afbeeldingen in de slider. Met "Afbeeldingen toevoegen" kies je er meerdere tegelijk. Sleep om de volgorde te wijzigen; vul bij elke afbeelding de alt-tekst in.', 'below' => '', 'label' => 'Afbeeldingen', 'docs' => 'blokken/blok-slider'],
            'field_mk_slider_afbeelding'         => ['help' => 'Een afbeelding voor deze slide.', 'below' => '', 'docs' => 'afbeeldingen/afb-alt'],
        ];
    }

    // Uitleg, korte regel en label aan het veld hangen (alleen bij het tonen, niet bij opslaan)
    add_filter('acf/prepare_field', function ($field) {
        if (!$field || empty($field['key'])) return $field;
        $h = mkbase_help_texts()[$field['key']] ?? null;
        if (!$h) return $field;

        if (isset($h['label'])) $field['label'] = $h['label'];
        if (array_key_exists('below', $h)) $field['instructions'] = $h['below'];
        $field['wrapper'] = (array) ($field['wrapper'] ?? []);
        $field['wrapper']['data-mk-help'] = $h['help'];
        if (!empty($h['docs'])) $field['wrapper']['data-mk-docs'] = $h['docs'];
        return $field;
    });

    function mkbase_help_enqueue() {
        $theme_uri = get_template_directory_uri();
        $theme_dir = get_template_directory();
        wp_enqueue_style('mk-help', $theme_uri . '/assets/css/mk-help.css', [], mkbase_asset_version($theme_dir . '/assets/css/mk-help.css'));
        wp_enqueue_script('mk-help', $theme_uri . '/assets/js/mk-help.js', [], mkbase_asset_version($theme_dir . '/assets/js/mk-help.js'), true);
        wp_localize_script('mk-help', 'mkHelp', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mkbase_help'),
            'docs'  => admin_url('admin.php?page=mkbase-docs'),
        ]);
    }

    // Op de MK-pagina's en in de blokeditor (velden van de blokken in de zijbalk)
    add_action('admin_enqueue_scripts', function () {
        if (function_exists('mkbase_admin_current_page') && mkbase_admin_current_page()) mkbase_help_enqueue();
    });
    add_action('enqueue_block_editor_assets', 'mkbase_help_enqueue');

    // "Meer uitleg": de Documentatie als HTML, het script pakt er het juiste stuk uit
    add_action('wp_ajax_mkbase_docs_html', function () {
        check_ajax_referer('mkbase_help');
        if (!current_user_can('edit_posts')) wp_send_json_error(null, 403);
        wp_send_json_success(['html' => mkbase_get_docs_html()]);
    });
?>
