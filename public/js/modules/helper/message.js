define(['jquery', 'modules/helper/dialog'], function ($, helperDialog) {
    return {
        run: function (options) {
            var messageModalHtml =
                    $('<div class="modalHeader">' +
                            '<h3>' + options.title + '</h3>' +
                            '</div>' +
                            '<div class="modalBody">' +
                            '<p>' + options.content + '</p>' +
                            '</div>' +
                            '<div class="modalFooter">' +
                            '<a href="#" class="btn btn-default soModalDefaultClose">' +
                            '<i class="fa fa-remove"></i> ' +
                            options.closebuttontxt +
                            '</a> ' +
                            '</div>');

            var modalOptionsMessage = {};
            modalOptionsMessage.afterShow = function ($modal) {
                if (typeof options.closeDelay != 'undefined') {
                    messageModalHtml.delay(options.closeDelay).queue(function(nxt) {
                        $.soModal.close();
                        nxt(); // continue the queue
                    })
                }
            };

            $.soModal.open($(messageModalHtml), modalOptionsMessage)
        }
    };
});
