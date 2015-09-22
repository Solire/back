define(['jquery', 'modules/helper/dialog', 'modules/helper/crop'], function ($, helperDialog, helperCrop) {
    return {
        run: function (wrap, options) {
            // Event click to open crop modal
            $(wrap).on('click', function (e) {
                e.preventDefault();
                if ($('#modalCrop').length == 0) {
                    return false;
                }

                var modalCrop = $('#modalCrop').clone().removeClass('hidden'),
                    select    = $(wrap).parents('.form-group:first').find('select'),
                    src       = $(wrap).parents('.form-group:first').find('.field-file-link').attr('href'),
                    minWidth  = select.data('min-width'),
                    minHeight = select.data('min-height');

                $('<img>', {
                    src: src
                }).load(function() {
                    $('#crop-target', modalCrop).attr('src', src);

                    var dialogParams = {
                        'html': modalCrop
                    }

                    helperDialog.run(null, dialogParams);
                    helperCrop.run(
                        $('#crop-target', modalCrop),
                        {
                            params: {
                                'gabaritId': $('[name=id_gabarit]').val(),
                                'id_gab_page': $('[name=id_gab_page]').val()
                            },
                            minSize: [minWidth, minHeight],
                            onclickCrop: ".form-crop-submit",
                            src: src,
                            dest: src
                        }
                    );

                    $('#crop-target', modalCrop).Jcrop('api').container.on('cropmove cropend',function(e,s,c){
                        $('.wShow', modalCrop).val(c.w);
                        $('.hShow', modalCrop).val(c.h);
                    });

                    $('.wShow, .hShow', modalCrop).on('input', function() {
                        var cropData = $('#crop-target', modalCrop).data('crop');

                        if (typeof cropData == 'undefined') {
                            cropData.x = 0;
                            cropData.y = 0;
                        }

                        var x = cropData.x,
                            y = cropData.y,
                            w = parseInt($('.wShow', modalCrop).val()),
                            h = parseInt($('.hShow', modalCrop).val());

                        $('#crop-target', modalCrop).Jcrop('api').setSelect([
                            x,
                            y,
                            w,
                            h
                        ]);

                    });

                    $('#crop-target', modalCrop).on( "croped.helper.crop", function(e, response) {
                        helperDialog.close();
                        select.append('<option>' + response.filename + '</option>');
                        select.val(response.filename).trigger('change');
                        select.trigger("select2:select", {data: response});
                    });
                });
            });
        }
    };
});
