# MKBase Theme — Changelog

> **Let op bij updaten naar v1.1.0:** vendor scripts (Fancybox, Owl Carousel, Swiper, Sameheight) worden niet meer automatisch geladen. Child themes die deze scripts gebruiken moeten na de update zelf `wp_enqueue_script('scriptnaam')` aanroepen.

**Optie 1 — alles in één keer** (in `functions.php` van het child theme, als de scripts sitebreed nodig zijn):
```
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('fancybox-js');
    wp_enqueue_script('owl-carousel-js');
    wp_enqueue_script('swiper-js');
    wp_enqueue_script('sameheight');
});
```

**Optie 2 — per blok** (in `render.php` van het blok dat het script gebruikt):
```
wp_enqueue_script('owl-carousel-js');
```

> **Nieuw in v1.1.0:** child thema's kunnen eigen blokken toevoegen aan de Documentatie-pagina via het filter `mkbase_docs_extra_blocks`. Voeg onderstaande code toe in `functions.php` of `inc/docs.php` van het child thema.

```
// mkbase_docs_section() is beschikbaar vanuit het core thema — niet opnieuw definiëren.
// $items: array van ['label' => '...', 'desc' => '...'] rijen.
// $icon: elk geldig Dashicons-icoon, bijv. 'dashicons-cover-image'.
add_filter('mkbase_docs_extra_blocks', function($html) {
    $html .= mkbase_docs_section('blok-hero', 'Hero banner', 'dashicons-cover-image', [
        ['label' => 'Wat doet het?', 'desc' => 'Toont een grote banner bovenaan de pagina.'],
        ['label' => 'Achtergrond',   'desc' => 'Kies een afbeelding of kleur als achtergrond.'],
    ]);
    return $html;
});
```

## v1.1.0 — 2026-06-02

### Developer guide — mobiel testen
- `mobile-testing-guide.html` toegevoegd aan het core thema: complete handleiding voor lokaal mobiel testen via ngrok (installatie, Laragon/Apache-configuratie, wp-config.php snippet, dagelijks gebruik en probleemoplossing).
- De guide is bereikbaar via **Mediakanjers → Documentatie → Developer → Mobiel testen** — uitsluitend zichtbaar en toegankelijk voor beheerders.
- Directe URL-toegang tot het bestand geblokkeerd via `.htaccess` in de theme-map — de inhoud wordt uitsluitend via een beveiligde WordPress admin-pagina geserveerd.

### Documentatie-pagina — instellingenknoppen
- Per documentatiesectie (Website instellingen, Menu's, Geavanceerd, Meldingen, 404, Gravity Forms, WP Rocket, Yoast SEO) staat er nu een directe knop onder de intro-tekst die naar de bijbehorende instellingenpagina navigeert.
- Typefout gecorrigeerd in het codevoorbeeld bij het filter `mkbase_docs_extra_blocks`: parameter heette `$dashicon`, is nu correct `$icon`.

### Vereisten bijgewerkt
- Minimale WordPress versie verhoogd naar **6.9**
- Getest op WordPress **6.9**
- Minimale PHP versie verhoogd naar **8.2**

### Notificatiebalk — CSS overschrijven
- Opgelost: wanneer het ACF-optieveld `css_overschrijven` op **Ja** staat, werd de `margin-top` van `.mk-header` onterecht via JavaScript overschreven. Dit veroorzaakte onnodige stijlwijzigingen wanneer de notificatiebalk uitgeschakeld is.
- De waarde van `css_overschrijven` wordt via `wp_localize_script` doorgegeven aan `notification.js`.

### Updater
- Updater URL verplaatst naar de constante `MKBASE_UPDATER_URL` in `functions.php` voor eenvoudiger beheer.

### Blokregistratie
- Blokregistratie verplaatst naar het core thema (`inc/acf/register-blocks.php`).
- Blokken worden automatisch geregistreerd vanuit `template-parts/blocks/` in zowel het core thema als het child thema — een map met een `block.json` is voldoende.
- De blok-categorie "Mediakanjers" wordt automatisch toegevoegd aan de WordPress blokeditor.
- In het child thema is geen registratiecode meer nodig.
- `mk_register_blocks()` en `mk_block_categories()` beveiligd met `function_exists()` guards — voorkomt fatal error als een child thema deze functies al zelf definieert.
- `mk_register_blocks()` gebruikt nu `mkbase_check_duplicate()` in plaats van kale `function_exists()` — toont een admin notice als het child thema de functie overschrijft.
- Blok-veldgroepen (Loop, Image Slider) worden niet meer geregistreerd als een child thema al een veldgroep heeft voor hetzelfde blok (`mkbase_block_has_fields()`). Voorkomt dubbele ACF-velden zonder bestaande blokdata te breken.

### ACF JSON-synchronisatie
- ACF veldgroepen worden automatisch geladen vanuit `acf-json/` in het core thema en (indien aanwezig) vanuit `acf-json/` in het child thema.
- Core velden blijven in PHP gedefinieerd (`inc/acf/fields.php`) en zijn niet bewerkbaar via de WP admin — aanpassingen vereisen een code-edit.
- Nieuwe veldgroepen aangemaakt via de WP admin worden automatisch opgeslagen als JSON in het child thema. De map `acf-json/` wordt aangemaakt als deze nog niet bestaat.
- Logica beheerd via `inc/acf/json.php`.

### Dashboard widget
- Widget toegevoegd op het WordPress dashboard die klanten wijst op de beschikbare documentatie.
- Toont het Mediakanjers logo, een korte introductietekst en twee knoppen: **Bekijk documentatie** en **Changelog**.
- Twee kaarten onderaan: één met een directe link naar de documentatie, één met een link naar mediakanjers.nl voor vragen.
- Widget verschijnt altijd bovenaan het dashboard en neemt de volledige breedte in.

### Documentatie-pagina
- Documentatie-pagina toegevoegd onder **Mediakanjers → Documentatie** in het WordPress admin menu.
- De pagina is bedoeld als naslagwerk voor klanten: uitleg over alle instellingen en blokken van het thema, rechtstreeks in de WordPress admin.
- De inhoud is verdeeld over vijf tabs — **Website instellingen** (logo, favicon, bedrijfsgegevens, socials), **Geavanceerd** (code-injectie in head/body/footer), **Meldingen** (notificatiebalk instellen en plannen), **404** (inhoud van de niet-gevonden-pagina) en **Blokken** (uitleg per ACF-blok).
- Elk onderdeel is beschreven in een inklapbare sectie met een duidelijk label en uitleg per veld.
- Bovenaan beide admin-pagina's (Changelog en Documentatie) is een gedeelde navigatie toegevoegd zodat je eenvoudig tussen de twee wisselt.
- De navigatie is opgebouwd als een zijbalk met drie groepen: **Thema** (Website instellingen, Menu's, Geavanceerd, Meldingen, 404), **Inhoud** (Blokken, Afbeeldingen) en **Plugins** (conditioneel op basis van actieve plugins).
- Elke plugin krijgt een eigen paneel in de zijbalk — Gravity Forms, WP Rocket en Yoast SEO verschijnen automatisch wanneer de betreffende plugin actief is.
- Het filter `mkbase_docs_extra_blocks` is beschikbaar zodat child thema's hun eigen blokken kunnen documenteren in de Documentatie-pagina. Zie het code-voorbeeld bovenaan deze pagina.

### Changelog-pagina
- Changelog-pagina toegevoegd onder **Mediakanjers → Changelog** in het WordPress admin menu.
- De pagina leest automatisch `CHANGELOG.md` uit en toont dit als gestructureerde HTML — geen handmatige updates nodig.
- Oudere versies worden automatisch geladen vanuit `changelog-archive/` en getoond onder een inklapbare knop.
- Bovenaan een samenvatting met het totaal aantal versies en de datum van de laatste update.
- Per changelog-item wordt automatisch een type-badge toegekend op basis van trefwoorden.
- Kopieerknop verwijderd uit de versiekoppen — was niet functioneel nodig.

### Image Slider blok
- Image Slider blok toegevoegd aan het core thema (`template-parts/blocks/image-slider/`).
- ACF-velden geregistreerd via `inc/acf/fields.php`: items desktop, items tablet, autoplay, aspect ratio en slides repeater.
- Post type select wordt dynamisch gevuld met alle publieke post types.
- Owl Carousel wordt automatisch geladen via `wp_enqueue_script()` in `render.php` — alleen op pagina's waar het blok aanwezig is.
- Initialisatie-JS (`image-slider.js`) staat in de blok-map zelf (`template-parts/blocks/image-slider/image-slider.js`) en wordt automatisch geladen via `render.php`.
- Styling via SCSS in het child thema op `.image-slider` — kan per website verschillen.

### Loop blok
- Loop blok toegevoegd aan het core thema (`template-parts/blocks/loop/`).
- ACF-velden geregistreerd via `inc/acf/fields.php`: post type, aantal berichten, handmatige selectie, kolommen, breedte en knop.
- Post type select wordt dynamisch gevuld met alle publieke post types.
- Styling via SCSS in het child thema op `.mk-loop`.

### Breadcrumbs — bugfix paginahiërarchie
- Opgelost: subpagina's toonden nooit hun ouder-links in de breadcrumb. De `is_page()` check zat in een `elseif` ná `is_singular()`, waardoor hij nooit bereikt werd — `is_singular()` matcht ook pagina's.
- De logica is herschreven: binnen `is_singular()` wordt nu expliciet onderscheid gemaakt tussen `page`, `post` en overige post types.

### Breadcrumbs blok
- Breadcrumbs-functie toegevoegd aan het core thema (`inc/breadcrumbs.php`).
- Ondersteunt automatisch: paginahiërarchie, blogcategorie, custom post types met archief, taxonomiepagina's, zoekresultaten en 404.
- Op de homepage worden geen breadcrumbs weergegeven.

### Duplicaat-detectie
- `inc/compat.php` toegevoegd met de helper `mkbase_check_duplicate()`.
- Core-functies zoals `mk_breadcrumbs()` tonen automatisch een admin notice als ze ook in het child thema gedefinieerd zijn.
- Blokregistratie detecteert automatisch duplicate blokken tussen core en child thema en toont een notice met de betrokken bloknamen.
- Nieuwe core-functies beveiligen met één regel: `if (!mkbase_check_duplicate('functienaam')):`.

### Geavanceerd — code-injectie
- ACF-velden onder **Mediakanjers → Geavanceerd** worden nu daadwerkelijk uitgelezen en op de juiste plek in de pagina geïnjecteerd.
- `in_de_head` wordt geladen via `wp_head` (binnen `<head>`).
- `direct_na_body` wordt geladen via `wp_body_open` (direct na `<body>`).
- `voor_de_sluitende_body` en `alleen_in_de_footer` worden geladen via `wp_footer` (voor `</body>`).
- `alleen_op_de_homepage` wordt geladen via `wp_footer`, alleen op de homepage.
- `wp_body_open()` toegevoegd aan `header.php` zodat de `wp_body_open`-hook correct vuurt.

### Beheerdersmeldingen samenvoegen
- Alle WordPress admin notices worden samengevoegd in een inklapbare balk boven de paginatitel.
- Toont het aantal meldingen en onthoudt de toestand via `sessionStorage`.
- Scripts en stijlen worden niet meer op elke adminpagina geladen — alleen op het dashboard, plugins, thema's, updates en Mediakanjers-pagina's.
- MutationObserver wordt automatisch stopgezet zodra de pagina geladen is en er geen notices aanwezig zijn.

### Image Slider blok
- Image Slider blok toegevoegd aan het core thema (`template-parts/blocks/image-slider/`).
- ACF-velden geregistreerd via `inc/acf/fields.php`: items desktop, items tablet, autoplay, aspect ratio en slides repeater.
- Owl Carousel wordt automatisch geladen via `wp_enqueue_script()` in `render.php` — alleen op pagina's waar het blok aanwezig is.

### Scripts — conditioneel laden
- Vendor scripts (Fancybox, Owl Carousel, Swiper, Sameheight) worden niet meer automatisch geladen op elke pagina. Ze zijn alleen geregistreerd — het child theme enqueued ze wanneer nodig via `wp_enqueue_script()`.
- `notification.js` wordt alleen geladen als "Melding tonen" op Ja staat én de huidige datum binnen de ingestelde datumrange valt. Server-side check, geen onnodige requests.
- **Hoe vendor scripts inladen:** voeg `wp_enqueue_script('naam')` toe in de `render.php` van een blok (laadt alleen op pagina's met dat blok), of in het child theme via `add_action('wp_enqueue_scripts', ...)` voor scripts die altijd nodig zijn. Beschikbare namen: `fancybox-js`, `owl-carousel-js`, `swiper-js`, `sameheight`.
- **Let op bij updaten naar v1.1.0:** vendor scripts worden niet meer automatisch geladen door het core theme. Child themes die Fancybox, Owl Carousel, Swiper of Sameheight gebruiken maar dit nog niet zelf inladen, zullen na de update die functionaliteit verliezen. Controleer elk child theme na de update en voeg waar nodig `wp_enqueue_script('owl-carousel-js')` (of de betreffende scriptnaam) toe — in de `render.php` van het blok dat het script gebruikt, of in de `functions.php` van het child theme als het sitebreed nodig is.

### Code-kwaliteit
- Boilerplate standaard-keys verwijderd uit de Meldingen-veldgroep in `fields.php` — consistent met de 404-groep.
- Typo in mapnaam hersteld: `template-parts/blocks/breadrumbs/` hernoemd naar `breadcrumbs/`.
- Herhaalde `if(is_front_page())` logica uit `index.php`, `single.php`, `page.php` en `archive.php` samengevoegd in de helper `mkbase_main_class()` in `setup.php`.
- Debug `console.log` verwijderd uit `notification.js`.
- Dubbele `add_theme_support('woocommerce')` verwijderd uit `setup.php` — de conditionele WooCommerce-check blijft behouden.
- Vertaalwrapper `__()` met placeholder `textdomain` verwijderd uit `menu.php` — labels zijn vaste Nederlandse teksten.
- Menu-aanmaak verplaatst van `after_setup_theme` (elke pageload) naar `admin_init` (alleen in de admin) — voorkomt onnodige database-aanroepen op de frontend.
- Fallback `[]` toegevoegd aan `get_theme_mod('nav_menu_locations')` in `menu.php` — voorkomt fout als er nog geen menulocaties opgeslagen zijn.
