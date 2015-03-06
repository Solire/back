define(['jquery'], function ($) {
    return {
        run: function (wrap, response) {
            var currentModule = this;
            $(wrap).on('click', '.exec-onclick-addblock', function(e) {
                e.preventDefault();

                var that = this,
                    adupliquer = $(wrap).find('.block-to-duplicate:first'),
                    sortBox = wrap,
                    clone;

                clone = adupliquer.clone(false);
                currentModule.resetBlock(clone);
                clone.insertBefore($(that));
                currentModule.initBlock(clone);
            });
        },
        resetBlock: function(blockClone) {
            blockClone.find('input, textarea, select').not('[name="visible[]"]')
                .not('.join-param')
                .not('.extensions')
                .each(function () {
                    if ($(this).is('input'))
                        $(this).val('');
                    else {
                        if ($(this).is('textarea')) {
                            $(this).val('');
                        }
                        else {
                            if ($(this).is('select'))
                                $(this).val($(this).children('option:first').val());
                        }
                    }
            });

            blockClone.find('.previsu').attr('href', '');
            blockClone.find('.previsu').hide();
            blockClone.find('.crop').hide();

            $('legend', blockClone).html('Bloc en cours de création');
        },
        initBlock: function(blockClone) {
            var idnew;
            blockClone.find('input, textarea, select').not('[name="visible[]"]')
                .not('.join-param')
                .not('.extensions')
                .each(function () {
                    idnew = $(this).attr('id') + 'a';
                    $(this).attr('id', idnew);
                    $(this).prev('label').attr('for', idnew);
            });
        }
    };
});
