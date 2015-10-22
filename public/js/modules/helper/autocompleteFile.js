define(['jquery', 'modules/helper/autocomplete'], function ($, helperAutocomplete) {
    return {
        autocompleteOptions: {
            url: "back/media/autocomplete.html",
            templateResult: function (file) {
                if (file.id) {
                    var ext       = file.text.split('.').pop(),
                        thumbnail = '';

                    if (file.isImage) {
                        thumbnail = '<img class="img-thumbnail img-responsive" src="' + file.vignette + '">';
                    } else {
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

                    var markup = '<div  title="' + file.label + '" class="row" style="margin: 0">'
                        + '<div class="col-sm-3">' + thumbnail + '</div>'
                        + '<div class="col-sm-9">'
                        + '<dl class="dl-horizontal"><dt>Nom de fichier</dt><dd><span>' + file.label + '<span></dd><dt>Taille</dt><dd><span>' + file.size + '<span></dd>' + alert + '</dl>'
                        + '</div>'
                        + '</div>';

                    return markup;
                }
            },
            templateSelection: function (file) {
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
            var currentModule = this;
            options = $.extend(true, {}, this.autocompleteOptions, options);
            helperAutocomplete.run(wrap, options);

            wrap.on("select2:select", function (e, params) {
                // Dans le cas d'un trigger, on utilise params, sinon on utilise e.params
                if (typeof e.params != 'undefined') {
                    params = e.params;
                }

                var fileDiv     = $(this).parents('.form-group:first'),
                    file        = params.data;

                currentModule.selectFile(fileDiv, file)

            });
        },
        selectFile: function(fileDiv, file) {
            var fileInfoDiv = fileDiv.find('.field-file-info');

            if (typeof file.path != 'undefined') {
                if (file.isImage) {
                    $(this).siblings('.crop').show();
                    $(this).siblings('.solire-js-empty').show();
                } else {
                    $(this).siblings('.crop').hide();
                    $(this).siblings('.solire-js-empty').hide();
                }

                if (fileInfoDiv.length > 0) {
                    $('.field-file-link', fileInfoDiv).attr('href', file.url)
                        .show();
                    $('.field-file-value', fileInfoDiv).text(file.value);

                    if (file.isImage) {
                        $('.field-file-size', fileInfoDiv).text(file.size).show();
                        $('.field-file-size', fileInfoDiv).prev().show();
                        $('.field-file-crop', fileDiv).show();
                        $('.field-file-crop', fileDiv).data('crop-src', file.path)

                        $('.field-file-link', fileInfoDiv).data('zoom-src', file.path)
                        $('.field-file-thumbnail', fileInfoDiv).attr('src', file.vignette).show();
                    } else {
                        $('.field-file-size', fileInfoDiv).hide();
                        $('.field-file-size', fileInfoDiv).prev().hide();
                        $('.field-file-crop', fileDiv).hide();
                        $('.field-file-crop', fileDiv).removeData('crop-src')

                        $('.field-file-link', fileInfoDiv).data('zoom-src', '')
                        $('.field-file-thumbnail', fileInfoDiv).hide();
                    }
                }
            }
        }
    };
});
