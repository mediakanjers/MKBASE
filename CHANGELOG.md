# MKBase Theme — Changelog

## v1.2.2

### Bugfixes
- Blokkeneditor kon nog steeds crashen (`SecurityError`) bij `mk/`-blokken met een echte `<a href>` in hun preview, ook na de v1.2.1-fix — de eerdere click-guard onderschepte alleen een náve muisklik op de link, maar ACF's eigen React-rendering van de blok-preview kon zelf ook interactie met zo'n link veroorzaken, buiten die klik-listener om.
- Vervangen door een robuustere, nog steeds volledig blok-agnostische aanpak in `editor-blocks.js`: in plaats van klikken te onderscheppen, wordt het `href`-attribuut zelf verwijderd bij elke link binnen een `mk/`-blok in de canvas-iframe (met een `MutationObserver`, zodat dit ook werkt als de preview later opnieuw rendert). Zonder `href` is er domweg niets om naartoe te navigeren, ongeacht via welk mechanisme iets ermee interacteert — dit dekt zowel core- als child-theme-blokken, zonder dat daar iets voor hoeft te veranderen.
