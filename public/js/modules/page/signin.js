define(['jquery', 'noty'], function ($) {
  return {
    run: function (wrap, response) {

      // Lien pour passer du formulaire de connexion au formulaire de mot de passe perdu
      $(document).on('click', '.forgot-password-link, .sign-in-link', function () {
        $('form').toggleClass('hidden');
      })
    }
  }
});