# MKBase Theme — Changelog

## v1.4.0

### Nieuw: lightbox
- Nieuwe lightbox voor blokken en child themes, als vervanger van Fancybox voor nieuw werk: de eigen laag `mk-lightbox` (`assets/js/mk-lightbox.js`) met [Tobii](https://github.com/midzer/tobii) 3.2.0 (MIT, geen jQuery) eronder. Blokken gebruiken alleen de eigen data-attributen en nooit Tobii zelf, zodat de library later vervangen kan worden zonder dat blokken of sites aangepast hoeven te worden. Ondersteunt foto's, video (YouTube, Vimeo en eigen `.mp4`), iframes en eigen HTML-content:
  ```html
  <a href="foto.jpg" data-mk-lightbox="groep">…</a>
  <a href="https://youtu.be/ID" data-mk-lightbox data-mk-lightbox-type="video">…</a>
  <a href="https://…" data-mk-lightbox data-mk-lightbox-type="iframe">…</a>
  <a href="#element-id" data-mk-lightbox data-mk-lightbox-type="html">…</a>
  ```
  Links met dezelfde `data-mk-lightbox`-waarde vormen een galerij. Inladen met `wp_enqueue_script('mk-lightbox')`; de CSS laadt automatisch mee. YouTube opent via `youtube-nocookie.com` en Vimeo met `dnt=1`. Eigen HTML-content wordt verplaatst in plaats van gekopieerd, dus formulieren daarin blijven werken. SVG's zonder eigen breedte/hoogte (zoals veel logo's) worden ook correct getoond. Knopteksten zijn vertaalbaar (`mk-core`). Styling vanuit het child theme gaat via eigen CSS-variabelen die het core thema doorzet naar Tobii — gebruik deze, niet Tobii's eigen `--tobii-*`-variabelen of classes, zodat de styling blijft werken als de library ooit vervangen wordt:
  ```scss
  :root {
      --mk-lightbox-background: rgba(0, 0, 0, .9);   // overlay
      --mk-lightbox-button-color: #fff;              // sluiten, vorige/volgende
      --mk-lightbox-nav-background: #0057b8;         // achtergrond vorige/volgende
      --mk-lightbox-caption-background: rgba(0, 0, 0, .8);
      --mk-lightbox-caption-color: #fff;
      --mk-lightbox-counter-color: #fff;
      --mk-lightbox-z-index: 1337;
  }
  ```
  Voor detailstyling heeft de lightbox de eigen class `.mk-lightbox` als basisselector.
- **Image Slider**: nieuwe optie **Openen in lightbox**. Staat die aan, dan opent een klik op een afbeelding de lightbox, met bladeren door alle slides van die slider.

### Voorbereiding op jQuery 4
- Het **Image Slider**-blok draait nu op **Swiper 14** in plaats van Owl Carousel, en heeft geen jQuery meer nodig. Instellingen en gedrag zijn gelijk gebleven (items desktop/tablet, autoplay met pauze bij hover, loop, op mobiel één gecentreerde slide met de buren in beeld). De markup kreeg Swiper-classes (`.swiper`, `.swiper-wrapper`, `.swiper-slide`) naast de bestaande `image-slider__*`-classes; die laatste blijven dus gewoon bruikbaar in de SCSS van het child theme. De `.owl-carousel`-class op de track is verdwenen.
- Swiper 14 is beschikbaar voor nieuwe blokken via de handle `swiper-v14-js` (alleen geregistreerd — enqueue hem in de `render.php` van het blok; de CSS laadt automatisch mee).
- De CSS van Fancybox, Owl Carousel en Swiper laadt niet meer op elke pagina, maar alleen nog op pagina's waar het bijbehorende script ge-enqueued wordt — net zoals de scripts zelf sinds v1.1.0. Child themes en blokken hoeven hiervoor niets aan te passen: `wp_enqueue_script('owl-carousel-js')` (of `fancybox-js` / `swiper-js` / `swiper-v14-js`) laadt de CSS automatisch mee, ook vanuit een `render.php`. De vendor-CSS wordt daarbij altijd vóór `style-main.css` van het child theme geplaatst, op dezelfde plek in de cascade als voorheen, zodat de eigen styling nooit door vendor-CSS overschreven wordt.
- **Owl Carousel (`owl-carousel-js`) en Fancybox (`fancybox-js`) zijn verouderd**: ze worden niet meer onderhouden en werken niet met jQuery 4. Ze blijven geregistreerd en werkend voor bestaande sites die ze al gebruiken, maar zijn niet meer bedoeld voor nieuwe blokken. Ook Swiper 5 (`swiper-js`) blijft onder de oude handle staan voor bestaande sites.
- De ongebruikte mappen `assets/vendor/fancybox/v3.4.1/` en `v3.5.7/` zijn verwijderd; nergens werd ernaar verwezen. De Fancybox-bestanden in `assets/vendor/fancybox/` zelf blijven.

### Bugfix
- De `[mk_theme_module]` shortcode gebruikte de `folder`/`file`-attributen ongefilterd in een bestandspad, waardoor met `../`-reeksen bestanden buiten `template-parts/` ingeladen konden worden. Het opgeloste pad wordt nu met `realpath()` gecontroleerd en moet binnen `template-parts/` vallen voordat het geïncluded wordt.
- jQuery werd expliciet ge-enqueued terwijl WordPress dit al standaard registreert. De overbodige `wp_enqueue_script('jquery')`-regel is verwijderd; scripts die jQuery als dependency opgeven laden het nog altijd automatisch.
- De begin-/einddatum-check van de notificatiebalk gebruikte `date('Ymd')` (servertijd) in plaats van de WordPress-tijdzone, waardoor de melding op de verkeerde dag kon in- of uitschakelen. Vervangen door `wp_date('Ymd')`.
- `header.php` riep de sinds WordPress 4.4 verouderde `wp_title('')` aan voor de `<title>`-tag, wat botste met de al aanwezige `add_theme_support('title-tag')`. De handmatige tag is verwijderd; WordPress genereert de title-tag nu correct via `wp_head()`.

### Developer experience
- Nieuwe GitHub Action (`lint.yml`) draait voortaan bij elke pull request naar `dev`/`main`: een PHP-syntaxcheck op gewijzigde bestanden, een structuurcheck op de vier `block.json`-bestanden, en een controle dat een versiebump in `style.css` altijd samen met een `CHANGELOG.md`-wijziging gebeurt (en omgekeerd). Draait los van de bestaande release-workflow.
- De 19 plekken die voor cache-busting van assets direct `filemtime()` aanriepen gebruiken nu één centrale helper, `mkbase_asset_version()` (`inc/helpers/asset-version.php`). Die controleert eerst of het bestand bestaat, zodat een ontbrekend asset (bijv. een child-theme zonder `dist`-build) geen PHP-warning meer geeft.
- Verouderde jQuery-schrijfwijzen in de eigen scripts vervangen: `jQuery(document).ready(...)` / `jQuery(window).ready(...)` (verouderd sinds jQuery 3.0) is nu `jQuery(function($){...})` in `form.js`, `notification.js`, `sameheight.js` en `image-slider.js`, en de shorthand `$(window).resize(...)` (verouderd sinds jQuery 3.3) is nu `.on('resize', ...)`. Werking is ongewijzigd.
- Overbodige `-webkit-appearance`/`-moz-appearance`-prefixes verwijderd uit `editor-blocks.css`; de standaard `appearance: none` stond er al en wordt door alle huidige browsers ondersteund.
- De `<link rel="pingback">` in `header.php` wordt alleen nog uitgegeven op losse berichten/pagina's waar pingbacks openstaan, in plaats van op elke pagina.
- Nieuwe pagina **Mediakanjers → Diagnose** verzamelt governance-bevindingen op één plek: child-theme/core-functienaamconflicten en een child-theme dat een te oude core-versie nodig heeft (via een optionele `Requires MKBase: X.Y.Z`-regel in het child-theme's `style.css`). Elke bevinding wordt ook naar de PHP-errorlog geschreven (zichtbaar in `wp-content/debug.log` zodra `WP_DEBUG_LOG` aan staat), zodat een developer ook kan meekijken wanneer de wp-admin zelf niet bereikbaar is.
- De Diagnose-pagina toont nu ook: een Omgeving-overzicht (WordPress-/PHP-versie t.o.v. wat het thema vereist, `memory_limit`, actieve plugins, database table-prefix, of een SEO-plugin actief is), een Performance-overzicht (grootte van `debug.log` en van de autoload-opties in `wp_options` (inclusief de sinds WordPress 6.6 gebruikte autoload-waarden zoals `on` en `auto`) — beide een bekende WordPress-performanceval als ze te groot worden), de status van de bestaande thema-toggles (Alleen thema-blokken, Paginastructuur vergrendeld, Bestandseditor), en of een child-theme de ACF-velden van de blokken Loop/Image Slider/Kolommen overschrijft (was al mogelijk via `mkbase_block_has_fields()`, maar gebeurde tot nu toe onzichtbaar).
- Nieuwe beveiligings- en SEO-checks op de Diagnose-pagina: HTTPS actief, `debug.log` niet publiek benaderbaar via de browser (met een 1 uur gecachete check om geen herhaalde externe requests te doen), site niet per ongeluk op "ontmoedig zoekmachines" ingesteld, favicon en 404-pagina ingevuld. Plus: eventuele legacy ACF-blokken (geregistreerd zonder `block.json`) worden nu gemeld — dit werd al bijgehouden door `mkbase_track_legacy_theme_block()`, maar nergens getoond.
- Nog meer bevindingen op de Diagnose-pagina: menu-locaties zonder gekoppeld menu, een notificatiebalk die aanstaat maar buiten de ingestelde periode valt, een beschikbare MKBase-update (hergebruikt de bestaande updater-transient, geen extra requests), permalink-structuur op "Gewoon" i.p.v. nette URL's, en of "CSS overschrijven" bij Meldingen per ongeluk aanstaat op een klantsite. Nieuwe kaart **Server**: schrijfbaarheid van de uploads-/wp-content-map, aanwezigheid van `.htaccess`, vrije schijfruimte, ACF-JSON-syncstatus en belangrijke PHP-extensies (curl, mbstring, gd, imagick). Plus: persistent object cache-status in de Performance-kaart, en welke code-injectiepunten (Geavanceerd) en extra toegestane blokken actief staan in Thema-instellingen.
