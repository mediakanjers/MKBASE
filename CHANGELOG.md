# MKBase Theme — Changelog

## v1.2.10

### Nieuw
- De update-melding in wp-admin (Updates-tab en Verschijning > Thema's) toont voortaan direct wat er in de beschikbare MKBase-update zit. Eerder linkte "Bekijk versie X.X details" naar de GitHub releases-pagina, die WordPress in een iframe-popup probeert te laden — GitHub blokkeert dat (X-Frame-Options), waardoor die popup altijd leeg bleef. De link wijst nu naar een eigen pagina die de changelog van de nieuwe versie netjes toont.
- De changelog-pagina in het thema zelf toont bij een beschikbare update nu ook meteen de inhoud van die update, niet alleen het versienummer.

### Bugfix
- De datum bij elke versie in de changelog-pagina viel per ongeluk samen met de versietitel (bijv. "v1.2.7 — 2026-08-27" i.p.v. een los datum-label), doordat de changelog-bestanden Windows-regeleinden (CRLF) gebruiken die niet correct werden verwerkt. Regeleinden worden nu genormaliseerd, en de datum staat weer los, in het Nederlandse formaat (27-08-2026).
- Legacy ACF-blokken (via de oudere `acf_register_block_type()`-registratie, zonder `block.json`) werden bij de instelling "Alleen thema-blokken" niet herkend als eigen thema-blok en verdwenen daardoor uit de inserter. Oorzaak: de vergelijking van bestandspaden werkte niet op Windows-omgevingen (gemengd pad van `get_template_directory()` vs. het volledig genormaliseerde pad van `ReflectionFunction::getFileName()`). Beide paden worden nu genormaliseerd met `wp_normalize_path()` vóór de vergelijking.
