define(['jquery', 'modules/helper/dialog'], function ($, helperDialog) {
  return {
    run: function (options, response) {
      if (options instanceof jQuery || options == null) {
        options = response;
      }

      var messageModalHtml =
              $('<div class="modalHeader">' +
                  '<a class="soModalDefaultClose" tabindex="0"><i class="fa fa-close"></i></a>' +
                  '<h3>' + options.title + '</h3>' +
                  '</div>' +
                  '<div class="modalBody">' +
                  '<p>' + options.content + '</p>' +
                  '</div>' +
                  '<div class="modalFooter">' +
                  '<a class="btn btn-default soModalDefaultClose">' +
                  '<i class="fa fa-remove"></i> ' +
                  options.closebuttontxt +
                  '</a> ' +
                  '</div>');

      var modalOptionsMessage       = {};
      modalOptionsMessage.afterShow = function ($modal) {
        if (typeof options.closeDelay != 'undefined') {
          messageModalHtml.delay(options.closeDelay).queue(function (nxt) {
            helperDialog.close();
            nxt(); // continue the queue
          })
        }
      };

      var response = {
        html: $(messageModalHtml)
      }

      helperDialog.run(modalOptionsMessage, response);
    }
  };
});
