# Ontwikkelaarsgids — MKBase

## Git-branches

| Branch | Doel |
|--------|------|
| `main` | Stabiele releases — wat klanten ontvangen via de auto-updater |
| `dev`  | Lopende ontwikkeling — hier worden features gebouwd en getest |

**Werkwijze:** ontwikkel op `dev`, merge naar `main` pas bij een officiële release.

---

## Bestandsstructuur (release-relevant)

```
mkbase/
├── .github/
│   └── workflows/
│       └── release.yml       ← GitHub Actions: bouwt zip + publiceert release automatisch
├── updater/
│   └── updater.php           ← GitHub-gebaseerde auto-updater (leest mkbase-update.json)
├── style.css                 ← themaheader — Version: hier moet overeenkomen met de release
├── CHANGELOG.md              ← bevat altijd de nieuwste (nog te releasen of net gereleasede) versie
├── changelog-archive/        ← eerdere releases, één bestand per versie, met releasedatum in de kop
├── mkbase-update.json        ← update-manifest op main — wordt automatisch bijgewerkt door de Action
└── functions.php             ← definieert MKBASE_UPDATER_URL, laadt updater/updater.php
```

---

## CHANGELOG.md — hoe het werkt

`CHANGELOG.md` bevat op elk moment **de nieuwste versie** — of die nu al officieel uitgebracht is of nog in ontwikkeling. Het onderscheid zit in de kop:

- **Kop zonder datum** (`## v1.2.0`) → nog niet gereleased, dit is waar je tijdens development aan toevoegt.
- **Kop met datum** (`## v1.2.0 — 2026-08-13`) → al gereleased, staat gearchiveerd in `changelog-archive/`.

Tijdens het bouwen van nieuwe features: voeg wijzigingen toe onder de bestaande `## v{volgende-versie}`-kop in `CHANGELOG.md` (nog zonder datum). Staat er nog geen kop voor de nieuwe versie, maak die dan aan.

Bij het publiceren van een release doet de GitHub Action dit **automatisch**:
1. Voegt de releasedatum toe aan de kop.
2. Verplaatst de hele sectie naar `changelog-archive/{tag}.md`.
3. Reset `CHANGELOG.md` (alleen de titel blijft staan) zodat de volgende ontwikkelronde met een schone lei begint.

Je hoeft dit dus nooit handmatig te doen — alleen de inhoud van de sectie zelf bijhouden tijdens development.

De admin-pagina **Mediakanjers → Changelog** (`inc/changelog.php`) leest `CHANGELOG.md` + alle bestanden in `changelog-archive/` en plakt ze aan elkaar tot één overzicht — de nieuwste versie staat open, oudere versies staan ingeklapt onder "Bekijk oudere versies".

---

## Release — een nieuwe versie uitbrengen

Het thema heeft een ingebouwde auto-updater (`updater/updater.php`) die `mkbase-update.json` van de `main`-branch op GitHub leest. WordPress-sites controleren dit bestand periodiek (standaard WP-gedrag voor thema-updates).

### Stap voor stap

1. **Bump het versienummer** in `style.css` — de `Version:`-regel in de themaheader. Dit is de enige plek waar de versie staat (geen aparte constante zoals bij plugins).

2. **Ontwikkel en test op `dev`.** Vul `CHANGELOG.md` aan tijdens het werk — elke noemenswaardige wijziging als bullet onder de juiste `###`-sectie, onder de (nog datumloze) versie-kop.

3. **Merge `dev` → `main`** en push naar GitHub.

4. **Publiceer een GitHub Release**:
   - Ga naar **github.com/mediakanjers/MKBASE → Releases → Draft a new release**
   - Kies tag `v{versienummer}`, bijv. `v1.2.0` — moet exact overeenkomen met de `Version:` in `style.css`
   - Klik **Publish release**

5. **GitHub Action doet de rest automatisch:**
   - Bouwt de thema-zip (geen build-stap nodig, mkbase heeft geen eigen SCSS/JS-compilatie) en voegt hem toe aan de Release.
   - Werkt `mkbase-update.json` op `main` bij (nieuwe versie + download-URL).
   - Archiveert de `CHANGELOG.md`-sectie naar `changelog-archive/v{versienummer}.md` met de releasedatum, en reset `CHANGELOG.md`.

   Klanten ontvangen de update bij de volgende WordPress-updatecheck.

### Hoe werkt de updater?

`updater/updater.php` haalt `mkbase-update.json` op van:
```
https://raw.githubusercontent.com/mediakanjers/MKBASE/main/mkbase-update.json
```
Is de versie in dat bestand hoger dan de geïnstalleerde versie (`version_compare`), dan toont WordPress de bekende "Update beschikbaar"-melding in het Thema's-scherm.

---

## Kind-thema's

MKBase is een core/parent-thema; klantsites draaien een eigen child-thema (bijv. Mediakanjers) bovenop. Blokken, ACF-velden en PHP-logica horen in het core-thema; visuele SCSS-styling hoort in het child-thema — zie de bestaande blokken (Loop, Image Slider, Breadcrumbs, Kolommen) als voorbeeld van dit patroon.
