define(['jquery', 'modules/helper/dialog', 'modules/helper/amd'], function ($, helperDialog, helperAmd) {
    return {
        dialogOptions: {
            afterShow: function(modal) {
                helperAmd.run($(modal));
            },
        },
        run: function () {
            var currentModule = this;
            $(document.body).on('click', '.exec-onclick-ajaxdialog', function () {
                var that = this;
                currentModule.ajaxCall.call(that, currentModule.dialogOptions)
                return false;
            })
        },
        ajaxCall: function (dialogOptions) {
            var that        = this,
                data        = $(that).data(),
                ajaxCallUrl = data.url;

            if (typeof data.url == 'undefined') {
                if ($(that).attr('href')) {
                    ajaxCallUrl = $(that).attr('href');
                } else {
                    return;
                }
            }

            $.ajax({
                url: ajaxCallUrl,
                type: 'POST',
                success: function (htmlResponse) {
                    helperDialog.run(dialogOptions, {html: htmlResponse});
                },
                error: function () {
                    helperDialog.run(
                        null,
                        {html: 'Une erreur est survenue.'}
                    );
                }
            });
        }
    };
});
