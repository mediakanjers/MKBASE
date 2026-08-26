# MKBase Theme — Changelog

## v1.2.0

### WooCommerce
- WooCommerce documentatie toegevoegd aan de Documentatie-pagina onder **Plugins → WooCommerce** — verschijnt automatisch wanneer WooCommerce actief is.
- Documentatie dekt: producten beheren, bestellingen, winkel instellingen, betaalmethoden en verzending.

### Kolommen blok
- Nieuw blok toegevoegd aan het core thema (`template-parts/blocks/kolommen/`) voor flexibele kolommenlayouts — tekst naast afbeelding, meerdere kolommen met eigen breedteverdeling, etc.
- Verhouding instelbaar: 1 t/m 4 kolommen met vaste breedteverdelingen (50/50, 66/33, 33/66, 25/75, 75/25, 33/33/33, 25/50/25, 25/25/25/25).
- Elke kolom kan meerdere content-types tegelijk bevatten — Titel (H1 t/m H6), Tekst, Afbeelding, Video, Knop/CTA, Icoon + tekst, Cijfer/statistiek — bijvoorbeeld een titel met tekst en een knop eronder in dezelfde kolom.
- Verticale uitlijning instelbaar (boven/midden/onder/uitgerekt).
- Padding op drie niveaus, elk apart instelbaar (geen/klein/normaal/groot): rondom het hele blok (de kleurband), rondom de grid-achtergrond, én per kolom afzonderlijk.
- Twee onafhankelijke achtergrondkleuren te combineren: één die tot de randen van het scherm loopt, één die binnen de reguliere paginabreedte blijft — de laatste met een optie voor afgeronde hoeken.
- Tekstkleur instelbaar op blokniveau; per kolom ook een eigen achtergrondkleur, tekstkleur, padding en afgeronde hoeken.
- Kleurvelden tonen automatisch de kleuren uit het `theme.json` van het actieve (child) thema als suggesties — voegt het child thema later een kleur toe, dan verschijnt die vanzelf als optie.
- Nieuw `.mk-button`-component toegevoegd aan het child thema — stijlt met terugwerkende kracht ook de knop van het Loop-blok, die voorheen onopgemaakt was.
- Verplichte velden per gekozen type voorkomen lege kolommen; ontbrekende kolommen worden automatisch aangevuld bij het wijzigen van de verhouding; de knop "Kolom toevoegen" vergrendelt zodra het aantal kolommen van de gekozen verhouding is bereikt; een duidelijke waarschuwing (met visuele markering) verschijnt als er toch meer kolommen staan dan de verhouding toestaat.
- Live voorbeeldbalk in de editor toont de kolomverdeling, uitlijning, padding en achtergrondkleuren direct bij het aanpassen van de instellingen.

### Blokkeneditor — alleen thema-blokken
- Nieuwe instelling onder **Mediakanjers → Geavanceerd**: "Alleen thema-blokken in de editor" verbergt alle standaard WordPress-blokken uit de blokkeneditor, voor alle rollen inclusief beheerders — alleen de blokken van het thema (en child thema) blijven beschikbaar.
- Extra veld "Extra zichtbare blokken" om specifieke standaardblokken alsnog toe te staan naast de thema-blokken.

### Preview uitgeschakeld
- De WordPress preview-functionaliteit is sitebreed uitgeschakeld — zowel de preview-knop als directe `?preview=true`-links sturen door naar de live pagina (of homepage als die nog niet bestaat).
- De "Switch to Preview/Edit"-knop op losse blokken is verwijderd voor alle `mk/`-blokken, in zowel het core als het child thema — blokken tonen altijd het bewerk-formulier.

### Blokkeneditor — styling
- De ACF-velden van eigen (`mk/`) blokken hebben in de blokkeneditor een verzorgde, op de huisstijl afgestemde vormgeving gekregen: kaartweergave, duidelijkere labels, gestylede select/checkbox/button-group-velden, nettere repeater-rijen en afbeeldings-/video-previews.
- Het paginatitel-veld heeft een duidelijk kader gekregen zodat het zich onderscheidt van de opgemaakte pagina-inhoud eronder.

### Bugfixes
- Blokkeneditor kon crashen (`SecurityError`) en verloor daarbij soms alle eigen blokken uit het inserter-paneel, na een klik op een link binnen een blok-preview — bijvoorbeeld een kaart van het Post type loop-blok, de knop van het Kolommen-blok of een breadcrumb-link. Zo'n klik navigeerde de editor-iframe zelf weg naar de link-URL, wat Gutenberg's eigen opruimlogica liet crashen.
- Opgelost op twee niveaus: de betreffende blokken (Loop, Kolommen, Breadcrumbs) renderen in de editor voortaan geen `href` meer, én een nieuwe, blok-agnostische guard in `editor-blocks.js` onderschept elke klik op een link binnen een `mk/`-blok in de canvas — dit geldt automatisch ook voor nieuwe blokken die later worden toegevoegd, zonder dat hun `render.php` hier zelf rekening mee hoeft te houden.
- Het Breadcrumbs-blok stond nog op de oude ACF Blocks-vorm (`"mode": "preview"`) terwijl de andere `mk/`-blokken al op ACF Blocks V3 (`apiVersion: 3` / `acf.blockVersion: 3`) draaiden — die mix van oude en nieuwe blokstijl kon de hele inserter (niet alleen de eigen blokken) laten leeglopen ("No results found"). Breadcrumbs is nu ook op V3 gebracht.
- Het Image Slider-blok toonde in de editor-canvas een kale, verticaal gestapelde lijst afbeeldingen omdat de echte slider-opmaak (`owl.carousel.js` + eigen CSS) alleen op de live site laadt. De canvas toont nu een statisch grid-voorbeeld van de gekozen afbeeldingen in plaats van een ongestylede lijst.
- `editor-blocks.css` bereikte de block-editor-canvas (de `<iframe name="editor-canvas">`) helemaal niet — het werd alleen via `enqueue_block_editor_assets` in het hoofd-adminvenster geladen, wat prima werkt voor het ACF-uitklap-paneel maar niet voor styling die de daadwerkelijke blok-preview zelf moet raken (zoals de Image Slider-fix hierboven). Nu ook via `add_editor_style()` geregistreerd, zodat WP het automatisch in de canvas kloont — dit repareert in één keer alle canvas-gerichte regels in dat bestand, niet alleen de Image Slider.

### Release-automatisering
- GitHub Action toegevoegd (`.github/workflows/release.yml`) die bij het publiceren van een GitHub Release automatisch de thema-zip bouwt, uploadt, `mkbase-update.json` bijwerkt en de changelog archiveert.
- `DEVELOPMENT.md` toegevoegd met het volledige releaseproces.

