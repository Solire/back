define([
    'jquery',
    'jqueryCookie',
    'ladda',
    'modules/helper/uploader',
    'modules/helper/autocompleteFile'
], function (
        $,
        jqueryCookie,
        Ladda,
        helperUploader,
        helperAutocompleteFile
        ) {
    return {
        /* Paramètres pour l'uploader */
        uploaderParams: {
            basehref: $('base').attr('href'),
            runtimes: 'gears,html5,silverlight,flash,html4',
            max_file_size: '1000mb',
            multi_selection: false,
            chunk_size: '2mb',
            url: $('base').attr('href') + 'back/media/upload.html?'
                    + 'id_gab_page=' + $('[name=id_gab_page]').val() + '&'
                    + 'gabaritId=' + $('[name=id_gabarit]').val() + '&',
            flash_swf_url: $('base').attr('href') + 'public/default/back/js/plupload/plupload.flash.swf',
            silverlight_xap_url: $('base').attr('href') + 'public/default/back/js/plupload/plupload.silverlight.xap',
            filters: [
                {
                    title: 'Image files',
                    extensions: 'jpg,jpeg,gif,png,svg'
                },
                {
                    title: 'Zip files',
                    extensions: 'zip,rar,bz2'
                },
                {
                    title: 'Adobe',
                    extensions: 'pdf,eps,psd,ai,indd'
                },
                {
                    title: 'Fichiers vidéos',
                    extensions: 'mp4'
                }
            ],
            unique_names: false,
            multiple_queues: true,
            Init: function (base, up, files) {
                $(base).next('div').insertBefore(base)
            },
            FilesAdded: function (base, up, files) {
                up.refresh();
                up.start();
                var l = Ladda.create(base);
                l.start();
                $(base).data('ladda', l);
            },
            UploadProgress: function (base, up, file) {

            },
            FileUploaded: function (base, up, file, info) {
                var response = $.parseJSON(info.response);
                if (response.status != 'error') {
                    if ('id_temp' in response) {
                        $('input[name=id_temp]:first').val(response.id_temp);
                        $.cookie('id_temp', response.id_temp, {
                            path: '/'
                        });
                    }

                    // Fill the field
                    var select = $(base).parents('.form-group:first').find('select');

                    select.append('<option>' + response.filename + '</option>');
                    select.val(response.filename).trigger('change');
                    select.trigger("select2:select", {data: response});
                }

                $(base).data('ladda').stop();
                up.splice(0, 1);
            },
            Error: function (base, up, err) {
                err.file.error = true;
                up.refresh();
            }
        },
        run: function (wrap, response) {
            var currentModule = this;
            $.cookie('id_temp', 0, {
                path: '/'
            });

            helperUploader.run($(wrap), currentModule.uploaderParams);
        }
    };
});
