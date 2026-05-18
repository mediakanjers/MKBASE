//when loaded is faster than ready
var documentloaded = false;
document.addEventListener("DOMContentLoaded", function () { documentloaded = true; });

//jquery
jQuery(document).ready(function($) {
    function toggleFloatingLabel(field) {
        var input = field.find('input, textarea');
        var value = input.val();

        if (value.length > 0) {
            field.addClass('is-filled');
        } else {
            field.removeClass('is-filled');
        }
    }

    // On focus
    $(document).on('focus', '.gfield input, .gfield textarea', function () {
        $(this).closest('.gfield').addClass('is-active');
    });

    // On blur
    $(document).on('blur', '.gfield input, .gfield textarea', function () {
        var field = $(this).closest('.gfield');
        field.removeClass('is-active');
        toggleFloatingLabel(field);
    });

    // On input (live typing)
    $(document).on('input', '.gfield input, .gfield textarea', function () {
        toggleFloatingLabel($(this).closest('.gfield'));
    });

    // On page load (important for autofill)
    $('.gfield').each(function () {
        toggleFloatingLabel($(this));
    })
});