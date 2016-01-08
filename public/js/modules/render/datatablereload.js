define([
  'jquery',
  'modules/helper/datatable',
], function (
  $,
  helperDatatable
) {
  return {
    run: function (wrap, response) {
      helperDatatable.reload($('[data-datatable-name="' + response.datatableName + '"]'));
    }
  };
});
