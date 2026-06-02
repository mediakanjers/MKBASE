jQuery(document).ready(function($) {

    function adjustHeader() {
        if (typeof mkbaseConfig !== 'undefined' && mkbaseConfig.cssOverschrijven === 'ja') {
            return;
        }

        var $notification = $('.mk-notification');
        var $header = $('.mk-header');

        if ($notification.length && $notification.is(':visible')) {
            $header.css('margin-top', $notification.outerHeight() + 'px');
        } else {
            $header.css('margin-top', 0);
        }
    }
    adjustHeader();
    setTimeout(adjustHeader, 100);
    $(window).resize(adjustHeader);

    // Check of melding al gesloten is deze sessie
    if (sessionStorage.getItem('notification_closed')) {
        $('.mk-notification').hide();
        adjustHeader();
        return;
    }

    // Sluitmelding en onthouden in sessionStorage
    $('.mk-notification__inner__closex').on('click', function() {
        $(this).closest('.mk-notification').fadeOut(function() {
            adjustHeader();
        });
        sessionStorage.setItem('notification_closed', 'true');
    });

    // Scroll functie
    $(window).on('scroll', function() {
        var scroll = $(window).scrollTop();
        if(scroll > 100) {
            $('.mk-notification').addClass('scrolled');
        } else {
            $('.mk-notification').removeClass('scrolled');
        }
    });

    $('.mk-notification').each(function () {
        var $el = $(this);

        var beginDate = $el.data('begindatum'); // 20250101
        var endDate   = $el.data('einddatum');  // 20250131
        var showDate  = ($el.data('opgeven') + '');

        // Nee → altijd tonen
        if (showDate === "nee") {
            $el.show();
            return;
        }

        // Ja → datum check
        if (showDate === "ja") {
            var d = new Date();
            var today = d.getFullYear() +
                ('0' + (d.getMonth() + 1)).slice(-2) +
                ('0' + d.getDate()).slice(-2);

            $el.hide();

            // Alleen tonen als binnen range
            if ((!beginDate || today >= beginDate) && (!endDate || today <= endDate)) {
                $el.show();
            }
        }
    });
});
