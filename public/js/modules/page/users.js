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
            if (response.status == 'success') {
              $('.alert-danger', wrap).addClass('hidden');
              helperDatatable.reload($('[data-datatable-name=user]'));
              helperDialog.close();

              if ('after' in response) {
                require(response.after, function () {
                  $.each(arguments, function (ii, module) {
                    module.run(null, response);
                  });
                });
              }
            } else {
              $('.alert-danger', wrap).html(response.msg).removeClass('hidden');
            }
          }
        });
      });
    }
  };
});
