define(['jquery', 'modules/helper/message'], function ($, helperMessage) {
    return {
        run: function (wrap, response) {
            var elem = $('#gab_page_' + response.page.id);
            elem.slideUp('fast', function () {
                elem.remove();
                helperMessage.run({
                    'title': 'Confirmation de suppression de "' + response.page.type + '"',
                    'content': '"' + response.page.type + '"'
                        + ' a été supprimé avec succès',
                    'closebuttontxt': 'Fermer',
                    'closeDelay': 2500
                });
            })
        }
    };
});