define(['jquery'], function ($) {
  return {
    run: function (wrap, responseFromCallback) {
      var
        afterAmdFromCallback  = responseFromCallback.after || [],
        afterAmdFromAttribute = $(wrap).data('after') ? $(wrap).data('after').split(',') : [],
        after                 = afterAmdFromCallback.concat(afterAmdFromAttribute),
        responseFromAttribute = $(wrap).data('response') || {},
        response              = $.extend({}, responseFromCallback, responseFromAttribute)
      ;

      var afterCall = [];
      for (i in after) {
        var afterParts = after[i].split('::');
        if (afterParts.length == 1) {
          afterCall[i] = 'run';
        } else {
          afterCall[i] = afterParts[1];
          after[i]     = afterParts[0];
        }
      }

      if (after) {
        require(after, function () {
          $.each(arguments, function (ii, module) {
            module[afterCall[ii]].call(module, wrap, response);
          });
        });
      }
    }
  };
});
