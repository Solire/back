define(['jquery'], function ($) {
  return {
    run: function (wrap, response) {
      var
        others = $('[data-url="' + wrap.data('url') + '"][data-id="' + wrap.data('id') + '"][data-id_version="' + wrap.data('id_version') + '"]'),
        title = wrap.attr('title')
      ;

      if (response.visible) {
        wrap
          .add(others).removeClass('btn-default')
          .addClass('btn-success')
          .attr('title', title.substr(0, title.length - 19) + 'invisible sur le site')
          .data('visible', response.visible)
        ;

        wrap
          .find('i')
          .removeClass('fa-eye-slash')
          .addClass('fa-eye')
        ;
      } else {
        wrap
          .add(others)
          .removeClass('btn-success')
          .addClass('btn-default')
          .attr('title', title.substr(0, title.length - 21) + 'visible sur le site')
          .data('visible', response.visible)
        ;

        wrap
          .find('i')
          .removeClass('fa-eye')
          .addClass('fa-eye-slash')
        ;
      }

      if ($('[data-datatable-name="board"]').length > 0) {
        require(['modules/helper/datatable'], function (helperDatatable) {
          helperDatatable.reload($('[data-datatable-name="board"]'));
        });
      }
    }
  };
});