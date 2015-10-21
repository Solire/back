define(['jquery', 'jquerySoModal'], function ($) {
    return {
        defaults: {
            closeHTML: '',
            init: function (modal, overlay, alreadyOpened) {
                modal.css({opacity: 0});
                if (!alreadyOpened) {
                    overlay.css({opacity: 0});
                    $('h3', modal).css({"transform": "translate3d(0, -80px, 0)"});
                    modal.css({"transform": "translate3d(0, -400px, 0)", opacity: 0});
                }
            },
            in: function (modal, overlay, alreadyOpened) {
                if (!alreadyOpened) {
                    overlay.transition({opacity: 0.54})
                    modal.transition(
                        {
                            "opacity": 1,
                            "transform": "translate3d(0, 0, 0)"
                        },
                        400,
                        'cubic-bezier(0.7,0,0.3,1)'
                    );
                    $('h3', modal).transition(
                        {
                            "transform": "translate3d(0, 0, 0)",
                            delay: 200
                        },
                        400, function() {
                            // Fix depth issue
                            $(this).css({"transform": "none",})
                        }
                    );
                } else {
                    modal.transition({opacity: 1});
                }
            },
            out: function (modal, overlay, hasNextModal) {
                if (!hasNextModal) {
                    modal.transition(
                        {
                            "opacity": 0,
                            "transform": "translate3d(0, 250px, 0)"
                        },
                        400,
                        'cubic-bezier(0.7,0,0.3,1)'
                    );
                    overlay.transition({opacity: 0});
                } else {
                    modal.transition({opacity: 0});
                }
            }
        },
        run: function (options, response) {
            options              = $.extend(true, {}, this.defaults, options);
            var soModalContainer = $('<div class="soModalContainer" />').html(response.html)
            $.soModal.open(soModalContainer, options);
        },
        close: function (options, response) {
            $.soModal.close();
        },
        updateSize: function () {
            $.soModal.updateSize();
        },
        updatePosition: function () {
            $.soModal.updatePosition();
        },
    };
});
