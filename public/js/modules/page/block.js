define(['jquery', 'modules/helper/amd', 'modules/helper/wysiwyg', 'modules/helper/confirm'], function ($, helperAmd, HelperWysiwyg, helperConfirm) {
    return {
        run: function (wrap, response) {
            var currentModule = this;
            $(wrap).on('click', '.exec-onclick-addblock', function (e) {
                e.preventDefault();

                var that       = this,
                    adupliquer = $('.block-to-duplicate:last', wrap),
                    sortBox    = wrap,
                    clone;

                var wysiwygToInit = [];
                $('.wysiwyg-initialised', adupliquer).each(function() {
                    wysiwygToInit.push($(this));
                    HelperWysiwyg.destroy($(this));
                });

                clone = adupliquer.clone(false);

                $.each(wysiwygToInit, function(id, item) {
                    HelperWysiwyg.run($(this));
                });

                currentModule.resetBlock(clone);
                clone.insertAfter($('.block-to-duplicate:last', wrap));
                currentModule.initBlock(clone);

                //On autorise la suppression car plus qu'un bloc
                $('.block-to-duplicate', sortBox).each(function () {
                    $('.exec-onclick-removeblock, .block-to-sort-handle', this).prop('disabled', false)
                })
            });

            // Event click remove block
            $(wrap).on('click', '.exec-onclick-removeblock', function (e) {
                var helperConfirmOptions = {
                    title: 'Suppression d\'un bloc',
                    content: 'Êtes-vous sûr de vouloir supprimer ce bloc ?',
                    confirmbuttontxt: 'Confirmer',
                    cancelbuttontxt: 'Annuler',
                    autoclose: true,
                    callbackParams: [this],
                    callback: function() {
                        var that = this;
                        $(that).parents('.block-to-duplicate:first').slideUp('fast', function () {
                            $(this).remove();
                            //On autorise la suppression que si plus d'un bloc
                            if ($('.block-to-duplicate', wrap).length > 1) {
                                $('.block-to-duplicate', wrap).each(function () {
                                    $('.exec-onclick-removeblock, .block-to-sort-handle', this).prop('disabled', false)
                                })
                            } else {
                                $('.block-to-duplicate', wrap).find('.exec-onclick-removeblock, .block-to-sort-handle').prop('disabled', true)
                            }
                        });
                    }
                }

                helperConfirm.run(helperConfirmOptions)
            });

            // Event click toggle visible
            $(wrap).on('click', '.exec-onclick-togglevisible', function (e) {
                e.preventDefault();
                var that = this,
                    currentBlock = $(that).parents('.block-to-duplicate:first');
                if ($(that).hasClass('btn-success')) {
                    $(that).removeClass("btn-success")
                        .addClass("btn-default");

                    $(that).find("i").addClass("fa-eye-slash").removeClass("fa-eye");
                    currentBlock.find('.changevisible').prop('checked', false);
                    currentBlock.find('[name="visible[]"]').val('0');
                } else {
                    $(that).removeClass("btn-default")
                        .addClass("btn-success");

                    $(that).find("i").removeClass("fa-eye-slash").addClass("fa-eye");
                    currentBlock.find('.changevisible').prop('checked', true);
                    currentBlock.find('[name="visible[]"]').val('1');
                }
            });
        },
        resetBlock: function (blockClone) {
            blockClone.find('input, textarea, select').not('[name="visible[]"]')
                .not('.join-param')
                .not('.extensions')
                .each(function () {
                    if ($(this).is('input')) {
                        $(this).val('');
                    } else {
                        if ($(this).is('textarea')) {
                            $(this).val('');
                        } else {
                            if ($(this).is('select')) {
                                $(this).val($(this).children('option:first').val());
                            }
                        }
                    }
                });

            var fileDiv     = $(this).parents('.form-group:first');

            blockClone.find('.field-file').addClass('hidden');
            blockClone.find('.select2-wrapper').removeClass('col-sm-offset-3')
            blockClone.find('.select2-wrapper').removeClass('col-sm-offset-3')
            blockClone.find('.field-file-crop').hide();
            blockClone.find('.select2').remove();

            var iconFolder = $('legend i', blockClone);
            $('legend', blockClone).html(' Bloc en cours de création').prepend(iconFolder);
        },
        initBlock: function (blockClone) {
            var idnew;
            blockClone.find('input, textarea, select').not('[name="visible[]"]')
                .not('.join-param')
                .not('.extensions')
                .each(function () {
                    idnew = $(this).attr('id') + 'a';
                    $(this).attr('id', idnew);
                    $(this).prev('label').attr('for', idnew);
                });

            helperAmd.run(blockClone);
        }
    };
});
