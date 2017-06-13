(function ($) {

    // Grids
    function init_grids() {
        for (var i = 0; i < FWPBB.length; i++) {
            new FLBuilderPostGrid(FWPBB[i]);
        }
        clean_pager();
    }

    function clean_pager() {
        $('a.page-numbers').attr('href', '');
    }

    // Pagination
    $(document).on('click', 'a.page-numbers', function (e) {
        e.preventDefault();
        var clicked = $(this),
            page = clicked.text();
        if( clicked.hasClass('prev') ){
            // previous.
            page = parseInt( $('span.page-numbers.current').text() ) - 1;
        }
        if( clicked.hasClass('next') ){
            // next.
            page = parseInt( $('span.page-numbers.current').text() ) + 1;
        }
        $('.page-numbers').removeClass('current');
        clicked.addClass('current');

        FWP.paged = page;
        FWP.soft_refresh = true;
        FWP.refresh();
    })

    // Set Trigger
    $(document).on('facetwp-loaded', function () {
        init_grids();
    });
})(jQuery);