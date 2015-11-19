define(['jquery', 'modules/helper/autocomplete'], function ($, helperAutocomplete) {
    return {
        autocompleteOptions: {
            url: "back/page/autocompletejoin.html",
            data: function (params) {
                if ($('.select2-container--open').length == 0) {
                    return false;
                }

                var field = $('.select2-container--open').prev();

                var form = field.parents('form'),
                    id_version = $('[name=id_version]', form).val(),
                    id_gab_page = $('[name=id_gab_page]', form).val(),
                    sortBox = field.parents('fieldset:not(.block-to-sort):first'),
                    id_champ = field.attr('name').match(/champ(\d+)\[\]/).pop();

                
                var ids = [];
                $('[name="champ' + id_champ + '[]"]', sortBox).not(field).each(function() {
                    var v = $(this).val();
                    if (v !== '' && !isNaN(parseInt(v))) {
                        ids.push(v);
                    }
                });

                return {
                    term         : params.term,
                    ids          : ids,
                    id_champ     : id_champ,
                    id_version   : id_version,
                    id_gab_page  : id_gab_page
                };
            },
        },
        run: function (wrap, options) {
            options = $.extend(true, {}, this.autocompleteOptions, options);
            helperAutocomplete.run(wrap, options);
        }
    };
});
