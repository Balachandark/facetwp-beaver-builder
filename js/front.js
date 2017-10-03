var FWPBB = FWPBB || {};

(function($) {

    // Prevent BB scroll
    FLBuilderLayout._scrollToElement = function(element, callback) { }

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
        var clicked = $(this);
        var page = clicked.text();

        if (clicked.hasClass('prev')) { // previous
            page = FWP.settings.pager.page - 1;
        }

        if (clicked.hasClass('next')) { // next
            page = FWP.settings.pager.page + 1;
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