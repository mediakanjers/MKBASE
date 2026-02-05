jQuery(window).ready(function($) { 

    // $( '.divclass' ).mk_zelfdehoogte({ divClass: '.element' });
    (function ( $ ) {
        $.fn.mk_zelfdehoogte = function( options ) {
        if( $(this).length == 0 ) { return; }
    
        var settings = $.extend({
            divClass: "",
            loopRij: false,
            resize: true,
            break: 479,
            addClass: "",
            selector: $(this),
        }, options );

        mk_zelfdehoogte_loop( settings ); //the loop
        if( !settings.resize ) { return; }
        $( window ).resize( function() { 
            mk_zelfdehoogte_loop( settings ); //the loop resize
        });
        return;
    };


    function mk_zelfdehoogte_loop( set ) {
        $(set.selector).find(set.divClass).css('height', 'auto'); //reset height

        if( set.addClass != "" ) { $(set.selector).find(set.divClass).removeClass( set.addClass ); } //reset class
        if( $( window ).width() <= set.break ) { return; }

        var elementHeight = 0;

        if(set.loopRij) {
            $(set.selector).each(function() { 
            elementHeight = 0; //reset voor elke rij
            $(this).find(set.divClass).each(function() {

            if( $(this).outerHeight() > elementHeight ) {
                elementHeight = $(this).outerHeight();
            }
            });

            $(this).find(set.divClass).css({ height: elementHeight });
        });
        } else {
        $(set.selector).find(set.divClass).each(function() {
            if( $(this).outerHeight() > elementHeight ) {
            elementHeight = $(this).outerHeight();
            }
        });

        $(set.selector).find(set.divClass).css({ height: elementHeight });
        }
        if( set.addClass != "" ) { $(set.selector).find(set.divClass).addClass( set.addClass ); } //add class
    }
    }( jQuery ));
});