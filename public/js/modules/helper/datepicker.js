define(['jquery', 'modules/config/datepicker'], function ($) {
  return {
    run: function (wrap, response) {
      $(wrap).datepicker()
          .on('changeDate clearDate', function (e) {
            $(this).trigger('focusout', true);
          })
          .bind("focusout", function (e, is_changeDate) {
            if (!is_changeDate) {
              e.stopPropagation();
            }
          });
    }
  };
});
