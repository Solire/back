define(['jquery'], function ($, helperMessage, helperDatatable) {
  return {
    run: function (wrap, response) {
      var signinForm = $('#sign-in');
      if (response.status !== 'success') {
        // clear the fields to discourage brute forcing :)

        $('#log', signinForm).val('');
        $('#pwd', signinForm).val('');
      } else {
        $(signinForm).delay(800).queue(function () {
          document.location.href = $('base').attr('href') + signinForm.attr('action');
        });
      }
    }
  };
});