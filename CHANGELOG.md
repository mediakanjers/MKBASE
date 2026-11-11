# MKBase Theme — Changelog

## v1.6.0

### Nieuw
- MK-balk toegevoegd bovenaan alle Mediakanjers-pagina's: kruimelpad, tabbladen naar alle MK-pagina's, een knop **Opslaan** die altijd in beeld blijft, en de WordPress-meldingen samen achter één knop.
- Opslaan met **Ctrl+S** (Mac: Cmd+S) toegevoegd op de instellingenpagina's.
- Bevestiging na opslaan toegevoegd, met de melding dat de cache van de website is geleegd en een knop **Bekijk website**. De cache wordt nu bij elke MK-instellingenpagina geleegd, niet alleen bij Meldingen.
- Waarschuwing toegevoegd bij niet-opgeslagen wijzigingen: een bolletje in de MK-balk, en een venster met **Opslaan en verder**, **Niet opslaan** of **Blijven** als je de pagina verlaat. Zet je een wijziging terug, dan verdwijnt de waarschuwing weer.
- Donkere modus wordt nu per gebruiker onthouden, dus ook op een andere computer of browser.
- Uitleg met een **?** toegevoegd bij elk veld op de Mediakanjers-pagina's en in de MK-blokken in de editor. De ? toont een korte uitleg; **Meer uitleg** opent een zijpaneel met het juiste stuk uit de Documentatie, waarin het stuk over dat veld is uitgelicht. Onder het veld staat alleen nog een korte hint, zoals "Verplicht" of een voorbeeld. Alle teksten staan op één plek: `inc/admin/help.php`.
- Meldingen uitgebreid met nieuwe vormen en gedrag:
  - **Pop-up in het midden** van het scherm. Standaard wordt de pagina erachter donker en kan die niet scrollen (allebei uit te zetten). Sluiten kan met het kruisje, Esc of een klik ernaast. Een pop-up is altijd sluitbaar, dus de keuze Sluitbaar vervalt daar en "Daarna weer tonen" geldt altijd.
  - **Daarna weer tonen** heeft een nieuwe keuze: **Op de volgende pagina**.
  - **Zwevend blok** op een vaste plek: links of rechts, boven, midden of onder. Het blijft staan tijdens het scrollen, met een eigen **breedte** (standaard 360 px). Op een telefoon staan blokken onderaan, over de volle breedte.
  - Nieuw tabblad **Gedrag**: **Wanneer verschijnen** (direct, na een aantal seconden, of na een aantal px scrollen) en **Automatisch verdwijnen** na een aantal seconden, met een aftelbalkje. Het aftellen pauzeert zolang de bezoeker erop staat; daarna geldt "Daarna weer tonen". Sluitbaar en Daarna weer tonen staan nu ook hier.
  - Meerdere meldingen tegelijk kan, maar een pop-up en elke plek van een blok maar één tegelijk in dezelfde periode. Een bezette plek krijgt het label **Bezet** met uitleg, en opslaan wordt dan tegengehouden. Balken mogen onder elkaar.
  - Het live voorbeeld en de kop van elke melding tonen de nieuwe vorm, plek en het gedrag. Bestaande meldingen blijven precies zoals ze waren.
- Startlijst toegevoegd op het dashboard: zes dingen die op een nieuwe website ingevuld moeten zijn (logo, favicon, bedrijfsgegevens, sociale media, hoofdmenu en 404-pagina), met een voortgangsring en per stap een knop naar de juiste plek. Wat al is ingevuld, wordt automatisch afgevinkt; als alles klaar is, verdwijnt de lijst. Met **Verbergen** haal je hem per gebruiker weg; terugzetten kan onderaan **Mediakanjers → Documentatie**.
- Live voorbeeld toegevoegd op **Mediakanjers → Website instellingen**: naast de velden een vereenvoudigde website met het logo, de favicon in het tabblad, het hoofdmenu en de footer met contactgegevens en sociale media. Het loopt mee terwijl je typt, het veld waar je in zit licht op, en je kunt wisselen tussen **Computer** en **Mobiel**. Met de knop **Live voorbeeld** in de MK-balk zet je het aan of uit; op smalle schermen staat het uit.
- Live voorbeeld toegevoegd op **Mediakanjers → 404**: naast de velden zie je meteen hoe de 404-pagina eruitziet terwijl je typt.
- Knop **Bekijk op website** toegevoegd bij elke opgeslagen melding: opent de website met alleen die melding erop, ook als hij nog gepland is of uit staat, met een balk "alleen jij ziet dit". Alleen voor ingelogde gebruikers die meldingen mogen bewerken; zo'n pagina komt niet in de cache.
- Bij **Meldingen** toegevoegd: na verwijderen een melding met **Ongedaan maken** (definitief pas na Opslaan), en bij een lege lijst drie voorbeelden om mee te beginnen (gewijzigde openingstijden, actie of korting, storing).
- Knop **Annuleren** toegevoegd in de MK-balk: verschijnt zodra er iets gewijzigd is en zet alles terug naar de laatst opgeslagen versie.
- **Stijlgids** toegevoegd onder **Documentatie → Developer**: alle bouwstenen van de MK-admin (kleuren met hun waarde, tekst, knoppen, statuslabels, velden, schakelaar, bevestiging, sectie en de focusring), met de klasse eronder, in licht en donker. Nieuwe klassen voor eigen MK-pagina's: `.mk-h1`, `.mk-h2`, `.mk-text`, `.mk-hint`, `.mk-section`, `.mk-field` (met `--error`) en `.mk-switch`, plus de functie `mkbase_admin_section()`. Bestaande pagina's zijn niet veranderd.

### Verbetering
- WordPress-meldingen verbergen werkt nu blijvend: wat je wegklikt of met **Alles verbergen** verbergt, blijft per gebruiker weg, ook bij plugins die hun melding op elke pagina opnieuw tonen. Verandert de tekst, dan komt de melding terug. Elke melding heeft een kruisje, en onderaan staat "N verborgen · **Weer tonen**". Dit geldt in de MK-balk én op alle WordPress-pagina's (Dashboard, Berichten, Pagina's, Media, WooCommerce, ...; niet in de blokeditor), waar de oude zwart-groene knop is vervangen door dezelfde blauwe meldingenknop. Meldingen met de oude klasse `error` (bijv. van WooCommerce) tellen nu ook mee.
- Sociale media tonen in de beheeromgeving de echte icoontjes (dezelfde als op de website, uit `assets/images/socials/` van het child theme) in plaats van letters.
- Teksteditor (bijv. bij Meldingen en in het Kolommen-blok): de tekst staat niet meer tegen de rand en heeft een gewone lettergrootte.
- Kleine opmaakpunten: de kleurkiezer heeft één doorlopende rand, alle sleepgrepen zijn hetzelfde blokje van 3 × 3 puntjes, het live voorbeeld (Website instellingen en 404) ligt gelijk met de kaart links, en de kaarten op Diagnose sluiten aan zonder gaten.
- Bewerkvenster van de blokken in de editor verbeterd:
  - In de werkbalk staat nu **Bewerken** bij het potlood. Het venster heeft een kop met icoon en een link **uitleg** naar het juiste stuk uit de Documentatie, en onderaan **Klaar**. Alles in MK-blauw.
  - **Kolommen:** de verhouding kies je met plaatjes van de indeling. Elke kolom heeft een kop met wat erin staat en de breedte, en is daar in en uit te klappen. **Opmaak van deze kolom** staat ingeklapt in de kolom zelf. Bij een grijze **Kolom toevoegen** staat waarom. De twee achtergrondkleuren heten nu **Kleur van de hele strook** en **Kleur achter de kolommen**, met een schetsje. Het tekstveld heeft alleen nog vet, cursief, lijsten en links. "Knop / CTA" en "Cijfer / statistiek" heten nu **Knop** en **Cijfer**.
  - **Kleuren:** bij elke kleurkiezer in een blok staan de huisstijlkleuren uit `theme.json` van het child theme bovenaan, plus wit en zwart.
  - **Fotoslider:** met **Afbeeldingen toevoegen** kies je er meerdere tegelijk. De afbeeldingen staan als kleine tegels naast elkaar en zijn te verslepen.
  - **Berichtenoverzicht:** **Alles tonen** in plaats van het getal -1 (bestaande blokken blijven werken). Kies je zelf berichten, dan worden Soort inhoud en Aantal grijs, met uitleg.
  - **Kruimelpad:** geen knop Bewerken meer, want er valt niets in te stellen.
- Bloknamen in gewone taal: **Berichtenoverzicht** (was Post type loop), **Fotoslider** (was Image Slider) en **Kruimelpad** (was Breadcrumbs), met een duidelijke omschrijving. Bij het toevoegen zie je van Kolommen en Berichtenoverzicht een voorbeeld. Bestaande blokken blijven gewoon werken.
- Welkomstblok op het dashboard verbeterd naar de nieuwe stijl: knoppen **Bekijk de uitleg** en **Wat is er nieuw**, en tegels naar Meldingen, Website instellingen, Uitleg en Hulp. Bij Meldingen staat hoeveel meldingen er nu live zijn. De code staat nu in `inc/admin/dashboard.php`.
- Admin-stijl van de Mediakanjers-pagina's verbeterd: Website instellingen, Geavanceerd en 404 zijn kaarten met links een korte uitleg en rechts de velden, in licht en donker. Toetsenbordgebruikers zien altijd duidelijk waar de focus staat.
- Indeling verbeterd: Website instellingen is opgedeeld in **Huisstijl**, **Bedrijfsgegevens** en **Sociale media**, en Geavanceerd in **Scripts en tracking** en **Editor**. Alle velden en ingevulde waarden zijn gelijk gebleven.
- Afbeeldingsvelden verbeterd: knoppen **Vervangen** en **Verwijderen** onder de afbeelding, met het bestandstype en de bestandsgrootte.
- Sociale media verbeterd: elk kanaal is een eigen rij met een gekleurd icoon, te verslepen en met een kruisje te verwijderen.
- Meldingen-pagina verbeterd naar de nieuwe stijl: elke melding is een kaart met in de kop naam, positie, planning, status, een aan/uit-schakelaar, dupliceren en verwijderen. De instellingen staan in tabbladen met het live voorbeeld ernaast, stijl en positie kies je met knoppen met een kleur of schets, en bij Planning laat een tijdlijn zien wanneer de melding zichtbaar is. De statuslabels naast de titel lopen mee terwijl je wijzigt.
- Labels van velden verbeterd naar gewone taal, bijv. **Soort inhoud** (was Post type), **Vorm** (was Aspect ratio), **Automatisch afspelen** (was Autoplay), **Binnenruimte** (was Padding) en **Onderaan de pagina** (was Voor de sluitende /body). De velden en waarden zelf zijn gelijk gebleven.
- Changelog verbeterd naar de nieuwe stijl: per versie een kaart met een label per wijziging (op basis van het kopje), **Jouw versie** bij de geïnstalleerde versie, rechts de updatestatus en een lijst om naar een versie te springen, en **zoeken** naast de filters.
- Documentatie verbeterd naar de nieuwe stijl: menu links met **zoeken** door alle onderwerpen, per onderwerp een titel met knop naar de instellingen, genummerde kaarten met vragen die je los openklapt, en directe links naar een onderwerp (bijv. `#meldingen`). Nieuw onderwerp **Werken in het beheer** (opslaan, wijzigingen, uitleg bij velden, meldingen van WordPress en de startlijst) bovenaan, nieuw onderwerp **Editor** onder Geavanceerd, en de uitleg bij Bedrijfsgegevens past weer bij de velden.
- Diagnose verbeterd naar de nieuwe stijl: samenvatting bovenaan, daaronder de bevindingen, en de controles per onderdeel in kaarten met statuslabels.
- Codevelden onder Geavanceerd verbeterd: donkere code-weergave met regelnummers, en een waarschuwing boven aan de pagina.

### Wijziging
- De oude donkere kop, het tweede menu, de voortgangsbalk en de knop "naar boven" op Changelog, Documentatie en Diagnose zijn verwijderd; de MK-balk vervangt ze. `admin-shared.css` en `mkbase_admin_page_nav()` zijn daarmee vervallen.
- Het veld **Alleen in de footer** onder **Mediakanjers → Geavanceerd** is opgegaan in **Onderaan de pagina** (was Voor de sluitende /body): beide laadden op dezelfde plek, onderaan elke pagina. Bij de update wordt bestaande code automatisch onder de code in **Onderaan de pagina** gezet, ook per taal bij WPML. De oude instelling blijft bewaard.

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
