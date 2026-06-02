jQuery(document).ready(function($) {
    $('.image-slider__track.owl-carousel').each(function() {
        var $el          = $(this);
        var $slider      = $el.closest('.image-slider');
        var itemsDesktop = parseInt($slider.data('items-desktop')) || 3;
        var itemsTablet  = parseInt($slider.data('items-tablet')) || 2;
        var autoplay     = $slider.data('autoplay') === true;
        var itemCount    = $el.find('.image-slider__item').length;
        var enableLoop   = itemCount > itemsDesktop;

        $el.owlCarousel({
            margin: 20,
            loop: enableLoop,
            nav: false,
            dots: false,
            autoplay: autoplay,
            autoplayTimeout: 4000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    stagePadding: 30,
                    center: true,
                    margin: 15,
                },
                600: {
                    items: itemsTablet,
                },
                980: {
                    items: itemsDesktop,
                }
            }
        });
    });
});
