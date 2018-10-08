var FWPBB = FWPBB || {};

(function($) {

    // Prevent BB scroll
    FLBuilderLayout._scrollToElement = function(element, callback) { }

    // Grids
    FWPBB.init_grids = function() {
        $.each(FWPBB.modules, function(id, obj) {
            if ('grid' === obj.layout) {
                if ('post-grid' === obj.type) {
                    new FLBuilderPostGrid(obj);
                    $('.fl-node-' + id + ' .fl-post-grid').masonry('reloadItems');
                }
                else if ('pp-content-grid' === obj.type) {
                    new PPContentGrid(obj);
                }
                else if ('blog-posts' === obj.type) {
                    new UABBBlogPosts(obj);
                }
            }
            else if ('gallery' == obj.layout) {
                new FLBuilderPostGrid(obj);

                $('.fl-post-gallery-img').each(function() {
                    $(this)[0].style.setProperty('max-width', '100%', 'important');
                });
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
        if (FWP.loaded || FWP.is_bfcache) {
            FWPBB.init_grids();
        }
    });
    $(document).on('facetwp-refresh', function() {
        if ($('.facetwp-template:first').hasClass('facetwp-bb-module')) {
            FWP.template = 'wp';
        }
    });

})(jQuery);