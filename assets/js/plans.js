jQuery(function($) {
    $('.woocommerce dl.plan[data-view-href^="http"]').live('click', function () {
        window.location.assign(this.dataset.viewHref);
    });

    $('table.passengers').wrapAll('<div style="overflow: auto"/>');
});
