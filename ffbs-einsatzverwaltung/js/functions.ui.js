var postinfo = function () {
    $('.post-info').prependTo('.entry-content');
    $('.open-post-info').prependTo('.entry-content');

    $('.post-info').hide();
    $('.open-post-info').click(function () {
        var id = $(this).attr('id');
        $('.post-info-' + id).slideToggle("medium", function () {
            $(this).prev().toggleClass("toggled");
        });

        return false;
    });
};

jQuery(document).ready(function ($) {
    postinfo();
});
