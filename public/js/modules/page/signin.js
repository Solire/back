define(['jquery', 'noty'], function ($) {
    return {
        run: function (wrap, response) {
            /* Soumission du formulaire de connexion en ajax */
            $("#sign-in").submit(function (e) {
                e.preventDefault()

                var signinForm = $(this);
                $.post(signinForm.attr('action'), signinForm.serialize(), function (data) {
                    if (data.success) {
                        noty({text: data.message, type: 'success'});
                        $(signinForm).delay(800).queue(function () {
                            document.location.href = signinForm.attr('action');
                        })
                    } else {
                        noty({text: data.message, type: 'error'});

                        // clear the fields to discourage brute forcing :)
                        $("#log").val("");
                        $("#pwd").val("");
                    }


                }, 'json');
            });

        }
    }
});