# MKBase Theme — Changelog

## v1.7.4

### Bugfix
- **Openingstijden: een afwijking met een lange periode** maakte de website traag of liet de pagina vastlopen. Bij een typfout in het jaar (bijv. tot 2099) kostte één blok Openingstijden ruim 0,4 seconde, en bij een einddatum ver in de toekomst liep het geheugen vol. De berekening kijkt nu alleen naar de dagen die getoond worden, en een periode duurt maximaal een jaar (melding bij Opslaan).

### Verbetering
- **Sneller: opties in één keer laden.** De Mediakanjers-pagina's (Website instellingen, Geavanceerd, Meldingen, Openingstijden, 404) laden hun waarden nu in één keer met de rest van de WordPress-instellingen. Eerder kostte elk veld een eigen databasequery: zo'n 250 per pagina, ook voor bezoekers. Bij de update worden de bestaande opties eenmalig omgezet (waarden vanaf 100 kB blijven apart).
- **Sneller: keuzelijsten alleen in het beheer.** De keuzes bij Roosters, Volgt, Gebruiken voor Google en Extra zichtbare blokken werden ook op de website uitgerekend, bij elke keer dat een melding of instelling werd gelezen. Dat gebeurt nu alleen nog als het formulier getoond wordt (`acf/prepare_field`).
- **Updates van de core:** de website vraagt het updatebestand op GitHub hoogstens één keer per 6 uur op, met een timeout van 10 seconden. Eerder ging dat twee keer per controle met elk tot 30 seconden wachten, waardoor een beheerpagina kon blijven hangen als GitHub traag was. Meteen kijken kan met **Dashboard → Updates → Opnieuw controleren**.

### Wijziging
- **Gebruikersnamen afgeschermd** voor wie niet is ingelogd: `/?author=1` stuurt door naar de voorpagina, en de gebruikerslijst staat niet meer in de REST API (`/wp-json/wp/v2/users`) en de sitemap van WordPress. Ingelogd, ook in de blokeditor, werkt alles zoals altijd; het inlogscherm verandert niet. Uitzetten: filter `mkbase_gebruikers_afschermen`.
- **Updates alleen uit de eigen GitHub-releases:** een update wordt alleen aangeboden als de download van `github.com/mediakanjers/MKBASE/releases` komt.
- De handleiding **Mobiel testen** staat niet meer in het thema, alleen nog lokaal. De scripts in `bin/` (voor GitHub Actions) zitten niet meer in de release-zip en draaien niet meer via de website.
- Documentatie: nieuw onderwerp **Developer → Snelheid en beveiliging**.
