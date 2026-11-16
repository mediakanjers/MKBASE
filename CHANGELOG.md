# MKBase Theme — Changelog

## v1.7.3

### Bugfix
- **Owl Carousel-sliders uit het child theme** zijn weer zichtbaar op websites die van 1.3.0 komen. Tot en met 1.3.0 laadde het Image Slider-blok van de core het Owl-script, en eigen sliders in het child theme (bijv. team, projecten) gebruikten dat mee zonder het zelf te laden. Sinds 1.4.0 draait de Image Slider op Swiper, waardoor zulke sliders niet meer startten en door de Owl-CSS verborgen bleven. Staat er nu een `owl-carousel` op de pagina en laadt Owl niet, dan zet de core het script er direct na jQuery bij. Pagina's zonder carrousel laden niets extra, en een child theme dat `owl-carousel-js` zelf uitschakelt, krijgt niets.
