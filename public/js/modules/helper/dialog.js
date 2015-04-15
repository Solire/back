define(['jquery', 'jquerySoModal'], function($){
    return {
        defaults: {
            closeHTML : '',
            init: function(modal, overlay, alreadyOpened) {
                modal.css({opacity: 0});
                if (!alreadyOpened) {
                    overlay.css({opacity: 0});
                }
            },
            in: function(modal, overlay, alreadyOpened) {
                if (!alreadyOpened) {
                    overlay.transition({opacity: 0.54}, function() {
                        modal.transition({opacity: 1});
                    });
                } else {
                    modal.transition({opacity: 1});
                }
            },
            out: function(modal, overlay, hasNextModal) {
                if (!hasNextModal) {
                    modal.transition({opacity: 0});
                    overlay.transition({opacity: 0});
                } else {
                    modal.transition({opacity: 0});
                }
            }
        },
        run : function(options, response){
            options = $.extend(true, {}, this.defaults, options);
            var soModalContainer = $('<div class="soModalContainer" />').html(response.html)
            $.soModal.open(soModalContainer, options);
        }
    };
});
