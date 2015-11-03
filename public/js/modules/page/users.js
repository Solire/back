define([
  'jquery',
  'modules/helper/datatable',
  'modules/helper/dialog'
], function (
  $,
  helperDatatable,
  helperDialog
) {
  return {
    run: function (wrap, response) {
      console.log(wrap, response)

      $(wrap).submit(function (e) {
        var
          self = $(this),
          data = self.serialize(),
          method = self.attr('method'),
          action = self.attr('action')
        ;

        e.preventDefault();

        if (typeof method == 'undefined' || method == null) {
          method = 'post';
        }

        $.ajax({
          url: action,
          data: data,
          type: method,
          dataType: 'json',
          success: function (response) {
            helperDatatable.reload($('[data-datatable-name=user]'));
            helperDialog.close();
          }
        });
      });
    }
  };
});
