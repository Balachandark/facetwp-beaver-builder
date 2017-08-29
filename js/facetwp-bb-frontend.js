var FWPBB = FWPBB || {};

(function($) {

    // Grids
    FWPBB.init_grids = function() {
        $.each(FWPBB.modules, function(id, obj) {
            new FLBuilderPostGrid(obj);
            if ('grid' === obj.layout) {
                $('.fl-node-' + id + ' .fl-post-grid').masonry('reloadItems');
            }
        });
        clean_pager();
    }

    function clean_pager() {
        $('a.page-numbers').attr('href', '').each(function() {
            $(this).trigger('init');
        });
    }

    // Pagination
    $(document).on('click init', 'a.page-numbers', function(e) {
        e.preventDefault();
        var clicked = $(this),
            page = clicked.text(),
            currentpage = FWP.paged;

        if (clicked.hasClass('prev')) { // previous
            page = parseInt($('span.page-numbers.current').text()) - 1;
        }

        if (clicked.hasClass('next')) { // next
            page = parseInt($('span.page-numbers.current').text()) + 1;
        }

        $('.page-numbers').removeClass('current');
        clicked.addClass('current');

        if (e.type === 'click') {
            FWP.paged = page;
            FWP.soft_refresh = true;
            FWP.refresh();
        }
        else {
            FWP.facets['paged'] = page;
            clicked.attr('href', '?' + FWP.build_query_string());
            FWP.paged = currentpage;
        }
    });

    // Set Trigger
    $(document).on('facetwp-loaded', function() {
        if (FWP.loaded) {
            FWPBB.init_grids();
        }
    });
    $(document).on('facetwp-refresh', function() {
        if ($('.facetwp-template:first').hasClass('facetwp-bb-module')) {
            FWP.template = 'wp';
        }
    });

})(jQuery);