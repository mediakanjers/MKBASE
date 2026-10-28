# MKBase Theme — Changelog

## v1.5.0

### Nieuw
- Meerdere meldingen toegevoegd: bij **Mediakanjers → Meldingen** staat nu een lijst, en meldingen die tegelijk live zijn komen onder elkaar. Per melding een eigen naam, aan/uit-schakelaar, planning, opmaak en zichtbaarheid.
- Planning op de minuut toegevoegd (vanaf / tot en met, in de tijdzone van de website). Op elk begin- en eindmoment wordt de cache van WP Rocket automatisch geleegd.
- Nieuwe opties per melding toegevoegd: een **knop**, vaste **stijlen** (Info, Succes, Waarschuwing, Actie) naast eigen kleuren, de **positie** (bovenaan vast, bovenaan meescrollend of onderaan zwevend), **sluitbaar** aan of uit, en wanneer een weggeklikte melding terugkomt (volgend bezoek, na een aantal dagen of nooit).
- Uitgebreide zichtbaarheid toegevoegd: hele website, alleen op gekozen pagina's, of overal behalve gekozen pagina's, en te kiezen uit pagina's, berichten en andere berichttypes.
- Diagnose-controles toegevoegd: actieve kopieën van core-veldgroepen in de database of in `acf-json` van het child theme worden gemeld, omdat die wijzigingen uit core-updates kunnen tegenhouden. Ook verlopen of lege meldingen die nog aan staan worden gemeld.

### Verbetering
- Meldingen-pagina in de admin verbeterd: vernieuwd in de stijl van Changelog, Documentatie en Diagnose, met dark mode, een overzicht van de status van alle meldingen en een live voorbeeld per melding dat meeverandert terwijl je typt.
- Meldingen geoptimaliseerd: CSS en JavaScript laden alleen nog op pagina's waar een melding zichtbaar is, en het script werkt zonder jQuery.
- Toegankelijkheid verbeterd: het kruisje is een echte knop met een label voor schermlezers, en heeft de tekstkleur van de melding zodat het ook op een donkere achtergrond zichtbaar is.
- Documentatie bij **Meldingen** verbeterd: herschreven, met een technisch deel voor developers (classes, CSS-variabelen en bestanden).

### Wijziging
- De losse melding van vóór 1.5.0 is vervangen door de lijst: bij de update wordt hij automatisch de eerste melding, met dezelfde tekst, kleuren, datums en pagina's. De oude instellingen blijven bewaard.
- Uitgeschakelde database-kopieën van core-veldgroepen (Meldingen, Geavanceerd, MK instellingen, 404) worden bij de update eenmalig verwijderd. Ingevulde waarden blijven bewaard.
- `notification.js` (jQuery) is vervangen door `notifications.js`. De classes `.mk-notification__inner`, `__inner__txt` en `__inner__closex` zijn gelijk gebleven voor bestaande eigen CSS.

### Bugfix
- Opgelost: buiten de ingestelde periode stond de melding toch op de website, zonder werkend kruisje en over de header heen.
- Opgelost: na het wegklikken van één melding verscheen in hetzelfde tabblad geen enkele melding meer, ook geen nieuwe.
- Opgelost: links in een melding kregen niet de tekstkleur van de melding.

## v1.4.1

### Nieuw
- Zoekresultatenpagina toegevoegd (`search.php`): titel met de zoekterm, zoekformulier, aantal resultaten, resultaten met uittreksel, paginering en een melding als er niets gevonden is. Zoeken viel eerder terug op `index.php`, dat niets toonde. De opmaak komt uit het child theme via de `.search-page__*`-classes.
- Toegevoegd: blokmappen die met `_` beginnen worden overgeslagen bij het registreren. Zo kan het child theme een voorbeeldblok (`blocks/_voorbeeld/`) meeleveren dat niet in de editor verschijnt.
- Helperfunctie `mkbase_image()` toegevoegd om afbeeldingen te tonen in blokken en templates. Geeft de passende maat (`srcset`), breedte en hoogte, lazy loading en de alt-tekst uit de mediabibliotheek, en werkt met een ACF-afbeeldingsveld (array, ID of URL). SVG's krijgen de juiste breedte en hoogte uit het bestand zelf. Uitleg staat in **Documentatie → Developer → Afbeeldingen**.
- Keuzeveld **Afbeeldingsformaat** toegevoegd aan de Image Slider, Kolommen, Loop en de 404-pagina: kies hoe groot de afbeeldingen worden ingeladen (Klein, Middel, Groot of Origineel). Bij Kolommen staat het veld naast de afbeelding, per kolom, en bepaalt het ook hoe groot de afbeelding getoond wordt (nooit breder dan de kolom; vraagt `max-width: 100%` in plaats van `width: 100%` op `.mk-kolommen__afbeelding` in het child theme). De standaard is gelijk aan wat het blok eerder laadde. Eigen blokken krijgen hetzelfde veld met `mkbase_image_size_field()`.
- Video's in Kolommen laden nu pas na een klik: eerst een voorbeeldafbeelding met afspeelknop. De pagina laadt sneller en YouTube plaatst geen cookies voordat de bezoeker afspeelt (via `youtube-nocookie.com`, Vimeo met `dnt=1`). Hulpfunctie `mkbase_video()` toegevoegd voor eigen blokken, met styling via `--mk-video-*`-variabelen.
- HTML-anker toegevoegd aan Kolommen, Loop en Image Slider (tabblad **Geavanceerd** in de editor), zodat een link als `#diensten` naar het blok scrolt. Eigen blokken gebruiken hiervoor `mkbase_block_attrs()`.
- Developer-uitleg toegevoegd onder **Documentatie → Developer → Blokken bouwen**: `mkbase_block_attrs()`, `mkbase_block_notice()`, `mkbase_video()` en de berichtkaart.

### Verbetering
- Afbeeldingen verbeterd in de Image Slider, Kolommen (afbeelding en icoon), Loop en de 404-pagina: ze gebruiken nu `mkbase_image()`. Telefoons laden een kleinere versie in plaats van het origineel, de pagina verspringt niet meer tijdens het laden, en de alt-tekst komt uit de mediabibliotheek.
- Documentatie verbeterd: het Kolommen-blok heeft nu uitleg, en het paneel **Afbeeldingen** legt nu uit hoe kleinere versies en het afbeeldingsformaat werken.
- Archiefpagina verbeterd (`archive.php`, voor categorieën, tags, auteurs en datums): toont nu de titel, de beschrijving, de berichten als kaarten in een grid en paginering. Eerder stond de volledige inhoud van elk bericht onder elkaar, zonder titel. Gebruikt dezelfde kaart en opmaak als het Loop-blok (template-part `template-parts/content/card.php`), dus bestaande sites zijn meteen opgemaakt.
- Kolommen verbeterd: in de editor verschijnt een melding als het blok leeg is, of als het aantal kolommen niet past bij de gekozen verhouding.
- WPML-configuratie verbeterd: `afbeeldingsformaat`, `404_afbeeldingsformaat` en `lightbox` (Image Slider) staan nu in `wpml-config.xml`, zodat WPML ze uit de brontaal overneemt.

### Bugfix
- Opgelost: "Volledige breedte" in de blokwerkbalk en "Extra CSS-klasse(n)" in de editor deden niets bij Kolommen, Loop en Image Slider. Volledige breedte laat het blok nu buiten de inhoudsbreedte de hele breedte vullen (`assets/css/components/mk-align.css`, laadt alleen waar nodig en werkt ook met bestaande child themes). Bij Breadcrumbs is het werkingsloze veld voor extra classes verborgen.
- Opgelost: de knopteksten van de lightbox gebruikten tekstdomein `mk-core` in plaats van `mk`, waardoor WPML ze niet bij de overige thema-teksten vond.

## v1.4.0

### Nieuw
- Lightbox toegevoegd voor foto's, video (YouTube, Vimeo en eigen `.mp4`), iframes en eigen HTML-content. Draait op Tobii 3.2.0 (zonder jQuery) via de eigen laag `mk-lightbox`, en vervangt Fancybox voor nieuw werk. Uitleg voor developers staat in **Documentatie → Developer → Lightbox**.
- Image Slider: optie **Openen in lightbox** toegevoegd. Een klik op een afbeelding opent die groot, met bladeren door alle slides van die slider.
- Lightbox-styling vanuit het child theme toegevoegd via eigen CSS-variabelen (`--mk-lightbox-*`) en de class `.mk-lightbox`, los van de library eronder.
- Swiper 14 toegevoegd voor sliders in blokken (`swiper-v14-js`); de CSS laadt automatisch mee.
- Diagnose-pagina toegevoegd onder **Mediakanjers → Diagnose**: overzicht van omgeving, performance, beveiliging, SEO, server en thema-instellingen, plus waarschuwingen zoals child-theme-conflicten of een te oude core-versie (`Requires MKBase: X.Y.Z`). Bevindingen komen ook in `debug.log`.
- GitHub Action `lint.yml` toegevoegd: bij elke pull request naar `dev`/`main` een PHP-syntaxcheck, een check op de `block.json`-bestanden en een controle dat versienummer en changelog bij elkaar passen.

### Verbetering
- Image Slider verbeterd: draait nu op Swiper 14 in plaats van Owl Carousel en werkt zonder jQuery. Instellingen en gedrag zijn gelijk gebleven, en de `image-slider__*`-classes blijven bruikbaar in het child theme.
- Vendor-CSS geoptimaliseerd: de CSS van Fancybox, Owl Carousel en Swiper laadt alleen nog op pagina's waar het script geladen wordt, en altijd vóór `style-main.css`, zodat de eigen styling wint. Child themes hoeven niets aan te passen.
- Eigen scripts verbeterd voor jQuery 4: verouderde schrijfwijzen in `form.js`, `notification.js`, `sameheight.js` en `image-slider.js` zijn aangepast. De werking is gelijk gebleven.
- Cache-busting verbeterd met de centrale helper `mkbase_asset_version()`; een ontbrekend bestand geeft geen PHP-warning meer.
- Pingback-link geoptimaliseerd: alleen nog op berichten en pagina's waar pingbacks openstaan.

### Wijziging
- Owl Carousel (`owl-carousel-js`) en Fancybox (`fancybox-js`) zijn verouderd en werken niet met jQuery 4. Ze blijven werken op bestaande sites, maar zijn voor nieuwe blokken vervangen door Swiper 14 en `mk-lightbox`.
- Ongebruikte Fancybox-mappen `v3.4.1/` en `v3.5.7/` verwijderd.
- Overbodige `-webkit-`/`-moz-appearance`-prefixes verwijderd uit `editor-blocks.css`.

### Bugfix
- Beveiligingslek opgelost in de `[mk_theme_module]`-shortcode: met `../` konden bestanden buiten `template-parts/` worden ingeladen. Het pad wordt nu gecontroleerd.
- Opgelost: jQuery werd onnodig expliciet ingeladen, terwijl WordPress dit zelf al regelt.
- Opgelost: de notificatiebalk gebruikte de servertijd in plaats van de WordPress-tijdzone, waardoor de melding op de verkeerde dag kon aan- of uitgaan.
- Opgelost: de verouderde `wp_title()` in `header.php` botste met de `<title>`-tag van WordPress. WordPress maakt de titel nu zelf aan.
