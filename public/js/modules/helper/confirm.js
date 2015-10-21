define(['jquery', 'modules/helper/dialog'], function ($, helperDialog) {
    return {
        run: function (options) {
            var confirmModalHtml =
                    $('<div class="modalHeader">' +
                        '<a class="soModalDefaultClose" href="#"><i class="fa fa-close"></i></a>' +
                        '<h3>' + options.title + '</h3>' +
                        '</div>' +
                        '<div class="modalBody">' +
                        '<p>' + options.content + '</p>' +
                        '</div>' +
                        '<div class="modalFooter">' +
                        '<a href="#" class="btn btn-default soModalDefaultClose">' +
                        '<i class="fa fa-remove"></i> ' +
                        options.cancelbuttontxt +
                        '</a> ' +
                        '<a href="#" id="confirmButton" class="btn btn-danger">' +
                        '<i class="fa fa-check"></i> ' +
                        options.confirmbuttontxt +
                        '</a>' +
                        '</div>');

            var modalOptionsConfirm = {};
            modalOptionsConfirm.afterShow = function ($modal) {
                $modal.find('#confirmButton').click(function (event) {
                    event.preventDefault()
                    var that = null,
                        callbackParams = null;

                    if (typeof options.callbackParams != 'undefined') {
                        that = options.callbackParams.shift();
                        callbackParams = options.callbackParams.shift();
                    }
                    if (typeof options.callback == 'function') {
                        options.callback.apply(that, callbackParams);
                    }
                });
            };

            var response = {
                html: $(confirmModalHtml)
            }

            helperDialog.run(modalOptionsConfirm, response)
        }
    };
});
