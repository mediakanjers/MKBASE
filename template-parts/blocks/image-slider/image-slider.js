document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.image-slider__track.swiper').forEach(function(el) {
        var slider       = el.closest('.image-slider');
        var itemsDesktop = parseInt(slider.dataset.itemsDesktop) || 3;
        var itemsTablet  = parseInt(slider.dataset.itemsTablet) || 2;
        var autoplay     = slider.dataset.autoplay === 'true';
        var itemCount    = el.querySelectorAll('.image-slider__item').length;
        var enableLoop   = itemCount > itemsDesktop;

        new Swiper(el, {
            loop: enableLoop,
            autoplay: autoplay ? {
                delay: 4000,
                pauseOnMouseEnter: true,
                disableOnInteraction: false,
            } : false,
            // Mobiel: 1 gecentreerde slide, buren gluren in beeld via de padding in image-slider.css
            slidesPerView: 1,
            centeredSlides: true,
            spaceBetween: 15,
            breakpoints: {
                600: {
                    slidesPerView: itemsTablet,
                    centeredSlides: false,
                    spaceBetween: 20,
                },
                980: {
                    slidesPerView: itemsDesktop,
                    centeredSlides: false,
                    spaceBetween: 20,
                }
            }
        });
    });
});
