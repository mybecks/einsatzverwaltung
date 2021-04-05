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


const counter = function () {

    // if ($('.ffbs-counter').isOnScreen()) {

    $({ Counter: 0 }).animate({
        Counter: $('.ffbs-counter').text()
    }, {
        duration: 2000,
        easing: 'swing',
        step: function () {
            $('.ffbs-counter').text(Math.ceil(this.Counter));
        }
    });
    // }
};



jQuery(document).ready(function ($) {
    // $(window).scroll(function () {
    counter();
    // });
});
