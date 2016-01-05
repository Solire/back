define(['jquery', 'modules/helper/dialog'], function ($, moduleDialog) {
    return {
        run: function (wrap, response) {
            $(wrap).on('click', '.exec-onclick-zoom', function(e) {
                e.preventDefault();
                var zoomSrc = $(this).data('zoom-src');
                $('<img>', {
                    src: zoomSrc
                }).load(function() {
                    var easing, time;
                    // En ajoutant la classe soModalImageClose sur la modal, on ajoute la fermeture au clic sur elle-même
                    moduleDialog.run(
                        {
                            classPrefix : 'soModalImage',
                            modalClasses: 'soModalImageClose',
                            init: function(modal, overlay, alreadyOpened) {
                                modal.css({scale: 2, opacity: 0});
                                if (!alreadyOpened) {
                                    overlay.css({opacity: 0});
                                }
                            },
                            in: function(modal, overlay, alreadyOpened) {
                                modal.transition({
                                    scale: 1,
                                    opacity: 1
                                }, time, easing)
                                if (!alreadyOpened) {
                                    overlay.transition({opacity: 0.8}, time, easing);
                                }
                            },
                            out: function(modal, overlay, hasNextModal) {
                                modal.transition({scale: 2, opacity: 0}, time, easing);
                                if (!hasNextModal) {
                                    overlay.transition({opacity: 0}, time, easing);
                                }
                            }
                        },
                        {html: $(this).addClass('img-responsive')}
                    );
                });
            })
        },
    };
});
