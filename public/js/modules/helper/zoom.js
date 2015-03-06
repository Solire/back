define(['jquery', 'modules/helper/dialog'], function ($, moduleDialog) {
    return {
        run: function (wrap, response) {
            $(wrap).on('click', '.exec-onclick-zoom', function(e) {
                e.preventDefault();
                var zoomSrc = $(this).data('zoom-src');
                moduleDialog.run(
                    null,
                    {html: $('<img>').addClass('img-responsive').attr('src', zoomSrc)}
                );
            })
        },
    };
});
