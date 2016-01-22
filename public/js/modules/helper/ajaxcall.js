define(['jquery', 'modules/helper/dialog', 'modules/helper/confirm', 'modules/helper/after'], function ($, moduleDialog, moduleConfirm, HelperAfter) {
  return {
    run: function (wrap, response) {
      var currentModule = this;

      $(wrap).on('click', '.exec-onclick-ajax', function () {
        var self = this;

        if ($(self).data('confirm') == true) {
          var confirmOptions = $(self).data();
          confirmOptions.callback = currentModule.ajaxCall;
          confirmOptions.callbackParams = [self];
          moduleConfirm.run(confirmOptions);
        } else {
          currentModule.ajaxCall.call(self);
        }
      })
    },
    ajaxCall: function () {
      var
        wrap = $(this),
        data = wrap.data(),
        ajaxCallUrl = data.url
      ;

      if (typeof data.url == 'undefined') {
        return;
      }

      /*
       * Correction d'une erreur causée par le plugin ripple qui s'ajoute
       * au metadata de l'objet
       */
      if (typeof data.plugin_ripples != 'undefined') {
        delete data.plugin_ripples;
      }

      $.ajax({
        url: ajaxCallUrl,
        data: data,
        dataType: 'json',
        type: 'POST',
        success: function (response) {
          HelperAfter.run(wrap, response);
        },
        error: function (response) {
          moduleDialog.run(
            null,
            {
              html: '<div class="soModalContainer">'
                  + '<div class="modalHeader">'
                  + '<a class="soModalDefaultClose" href="#" tabindex="0">'
                  + '<i class="fa fa-close"></i>'
                  + '</a>'
                  + '<h3>Erreur rencontrée</h3>'
                  + '</div>'
                  + '<div class="modalBody">'
                  + '<p>Une erreur est survenue.</p>'
                  + '</div>'
                  + '<div class="modalFooter"></div>'
                  + '</div>'
            }
          );
        }
      });
    }
  };
});
