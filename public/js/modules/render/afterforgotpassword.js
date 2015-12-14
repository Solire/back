define(['jquery'], function ($, helperMessage, helperDatatable) {
  return {
    run: function (wrap, response) {
      // clear the fields to discourage brute forcing :)
      var forgotPasswordForm = $('#forgot-password');
      $('#log', forgotPasswordForm).val('');
    }
  };
});