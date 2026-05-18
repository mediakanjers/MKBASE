jQuery(window).ready(function ($) {
    (function ($) {
        $.fn.mk_zelfdehoogte = function (options) {
            if ($(this).length === 0) return;

            var settings = $.extend({
                divClass: "",
                loopRij: false, 
                resize: true,
                break: 479,
                addClass: "",
                selector: $(this),
            }, options);

            mk_zelfdehoogte_loop(settings);

            if (settings.resize) {
                $(window).on('resize', function () {
                    mk_zelfdehoogte_loop(settings);
                });
            }

            return this;
        };

        function mk_zelfdehoogte_loop(set) {
            var $items = $(set.selector).find(set.divClass);

            // reset
            $items.css('height', 'auto');

            if (set.addClass !== "") {
                $items.removeClass(set.addClass);
            }

            if ($(window).width() <= set.break) return;

            // =====================================
            // PER RIJ GELIJK MAKEN
            // =====================================
            if (set.loopRij) {

                var rows = [];

                $items.each(function () {
                    var top = $(this).offset().top;

                    // bestaande rij zoeken
                    var found = false;

                    $.each(rows, function (index, row) {
                        if (Math.abs(row.top - top) < 5) {
                            row.items.push($(this));
                            found = true;
                            return false;
                        }
                    }.bind(this));

                    if (!found) {
                        rows.push({
                            top: top,
                            items: [$(this)]
                        });
                    }
                });

                // per rij hoogste bepalen
                $.each(rows, function (index, row) {

                    var maxHeight = 0;

                    $.each(row.items, function () {
                        if ($(this).outerHeight() > maxHeight) {
                            maxHeight = $(this).outerHeight();
                        }
                    });

                    $.each(row.items, function () {
                        $(this).css('height', maxHeight);
                    });

                });

            } else {
                // =====================================
                // ALLES 1 HOOGTE
                // =====================================
                var maxHeight = 0;

                $items.each(function () {
                    if ($(this).outerHeight() > maxHeight) {
                        maxHeight = $(this).outerHeight();
                    }
                });

                $items.css('height', maxHeight);
            }

            if (set.addClass !== "") {
                $items.addClass(set.addClass);
            }
        }
    }(jQuery));
});