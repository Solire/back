define(['jquery', 'modules/helper/dialog', 'modules/helper/confirm'], function ($, moduleDialog, moduleConfirm) {
    return {
        run: function (wrap, response) {
            var currentModule = this;
            $(document.body).on('click', '.exec-onclick-ajax', function() {
                var that = this;
                if ($(that).data('confirm') == true) {
                    var confirmOptions = $(that).data();
                    confirmOptions.callback = currentModule.ajaxCall;
                    confirmOptions.callbackParams = [that];
                    moduleConfirm.run(confirmOptions) 
                } else {
                    currentModule.ajaxCall.call(that)
                }
            })
        },
        ajaxCall: function() {
            var that = this,
                data = $(that).data(),
                ajaxCallUrl = data.url;
                if (typeof data.url == 'undefined') {
                    return;
                }

               /*
                * Correction d'une erreur causée par le plugin ripple qui s'ajoute
                * au metadata de l'objet
                */
                if (typeof data.plugin_ripples != 'undefined') {
                    delete data.plugin_ripples
                }
                $.ajax({
                    url: ajaxCallUrl,
                    data: data,
                    dataType : 'json',
                    type : 'POST',
                    success  : function(response){
                        if ('after' in response) {
                            require(response.after, function(){
                                $.each(arguments, function(ii, module){
                                    module.run($(that), response);
                                });
                            });
                        }
                    },
                    error    : function(response){
                        moduleDialog.run(
                            null,
                            {html: 'Une erreur est survenue.'}
                        );
                    }
                });
            return false;
        }
    };
});
