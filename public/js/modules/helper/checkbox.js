define(['jquery'], function ($) {
  return {
    run: function (wrap, response) {
      $(wrap).change(function(e){
        $(this).next().val($(this).is(':checked') ? '1' : '0');
      });
    }
  };
});
