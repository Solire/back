define(['jquery', 'modules/helper/autocomplete'], function ($, helperAutocomplete) {
    return {
        autocompleteOptions: {
            url: "back/media/autocomplete.html",
            format: function (file) {
                var ext = file.text.split('.').pop(),
                    thumbnail = '';

                if (file.isImage) {
                    thumbnail = '<img class="img-thumbnail img-responsive" src="' + file.vignette + '">';
                } else {
                    thumbnail = '<img src="public/default/back/img/filetype/' + ext + '.png" height="25">';
                }

                /* @todo À commenter */
                var inputs = [];
                $('.form-file').not(this).filter(function () {
                    return $(this).val() == file.text;
                }).each(function () {
                    inputs.push($(this).val());
                });

                /* Alert si image trop petite */
                var alert = '';
                if (file.isImage && $(this).attr('data-min-width') && $(this).attr('data-min-width') > 0) {
                    var size = file.size.split('x');
                    if (parseInt(size[0]) < $(this).attr('data-min-width')) {
                        alert = '<dt style="color: red">Attention</dt><dd><span style="color: red">La largeur de l\'image est trop petite<span></dd>';
                    }
                }

                var markup = '<div class="row">'
                                + '<div class="col-sm-4">' + thumbnail + '</div>'
                                + '<div class="col-sm-8">'
                                + '<dl class="dl-horizontal"><dt>Nom de fichier</dt><dd><span>' + file.label + '<span></dd><dt>Taille</dt><dd><span>' + file.size + '<span></dd>' + alert + '</dl>'
                                + '</div>'
                            + '</div>'

                return markup;
            },
            formatSelection: function (file) {
                var previsu = $(this).parents('.form-group:first').find('.previsu');
                if (typeof file.path != 'undefined') {
                    var ext = file.path.split('.').pop();
                    if (file.isImage) {
                        $(this).siblings('.crop').show();
                        $(this).siblings('.solire-js-empty').show();
                    } else {
                        $(this).siblings('.crop').hide();
                        $(this).siblings('.solire-js-empty').hide();
                    }

                    if (previsu.length > 0) {
                        previsu.attr('href', file.path);
                        previsu.show();
                        $('.champ-image-value', previsu).text(file.value);

                        if (file.isImage) {
                            $('.champ-image-size', previsu).text(file.size).show();
                            $('.champ-image-size', previsu).prev().show();

                            $('.champ-image-vignette', previsu).attr('src', file.vignette).show();
                        } else {
                            $('.champ-image-size', previsu).hide();
                            $('.champ-image-size', previsu).prev().hide();

                            $('.champ-image-vignette', previsu).hide();
                        }
                    }

    //                if (file.isImage) {
    //                    openCropDialog.call($(this).siblings('.crop'));
    //                }
                }

                return file.text;
            },
            data: function (params) {
                if ($('.select2-container--open').length == 0) {
                    return false;
                }

                return {
                    term: params.term,
                    id_gab_page: $('[name=id_gab_page]').val(),
                    id_temp: $('[name=id_temp]').val(),
                    extensions: $('.select2-container--open').prev().data('extensions'),
                    page: params.page
                };
            },
        },
        run: function (wrap, options) {
            options = $.extend(true, {}, this.autocompleteOptions, options);
            helperAutocomplete.run(wrap, options);
        }
    };
});
