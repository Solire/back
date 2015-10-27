define(['jquery', 'modules/helper/message', 'modules/helper/datatable'], function ($, helperMessage, helperDatatable) {
    return {
        run: function (wrap, response) {
            var elem = $('#gab_page_' + response.page.id);

            if (elem.length > 0) {
                // Cas liste des pages
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
            } else {
                // Cas tableau de bord
                helperDatatable.reload($('[data-datatable-name="board"]'));
                helperMessage.run({
                    'title': 'Confirmation de suppression de "' + response.page.type + '"',
                    'content': '"' + response.page.type + '"'
                    + ' a été supprimé avec succès',
                    'closebuttontxt': 'Fermer',
                    'closeDelay': 2500
                });
            }

        }
    };
});