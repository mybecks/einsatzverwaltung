const $ = jQuery;

$.fn.isOnScreen = function () {

    var win = $(window);

    var viewport = {
        top: win.scrollTop(),
        left: win.scrollLeft()
    };
    viewport.right = viewport.left + win.width();
    viewport.bottom = viewport.top + win.height();

    var bounds = this.offset();
    bounds.right = bounds.left + this.outerWidth();
    bounds.bottom = bounds.top + this.outerHeight();

    return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
};

let ffbsCounterExecuted = false;
const ffbsCounter = function () {

    if ($('.ffbs-counter') && $('.ffbs-counter').length > 0 && $('.ffbs-counter').isOnScreen()) {
        ffbsCounterExecuted = true;
        $({Counter: 0}).animate({
            Counter: $('.ffbs-counter').text()
        }, {
            duration: 2000,
            easing: 'swing',
            step: function () {
                $('.ffbs-counter').text(Math.ceil(this.Counter));
            }
        });
    }
};


jQuery(document).ready(function ($) {
    ffbsCounter();
    $(window).scroll(function () {
        if (!ffbsCounterExecuted) {
            ffbsCounter();
        }
    });
});
