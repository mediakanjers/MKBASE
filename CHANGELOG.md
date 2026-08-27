# MKBase Theme — Changelog

## v1.2.7

### Nieuw
- De mkbase core-thema-versie staat nu rechtsonder in wp-admin, naast WordPress' eigen versienummer.

### Bugfix
- De bescherming tegen wegnavigeren/crashen van de blokkeneditor-iframe (links, formulieren, carousel-fallback) werkte alleen voor blokken met een `mk/`-naam. Kindthema's die blokken nog via de oudere `acf_register_block_type()`-PHP-registratie aanmaken (zonder `block.json`) krijgen van ACF automatisch een `acf/`-naam en vielen daardoor buiten de bescherming. Nu gescoped op `.acf-block-preview` — de class die ACF zelf om elke server-gerenderde blok-preview zet, ongeacht naamgeving of registratiemethode.
- De instelling "Alleen thema-blokken" verborg per ongeluk ook de eigen blokken van een kindthema als die nog via de oudere `acf_register_block_type()`-PHP-registratie zijn aangemaakt (zonder `block.json`) — die blokken werden niet meegeteld in de lijst van "eigen" blokken. Zulke blokken worden nu herkend via de bestandslocatie van hun render-functie (binnen de thema-map), niet via hun naam, zodat ze weer gewoon in de inserter verschijnen.
