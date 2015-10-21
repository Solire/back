define([
    'jquery',
    'jqueryCookie',
    'modules/helper/datatable',
    'modules/helper/uploader',
    'modules/helper/dialog',
    'modules/helper/zoom'
], function (
        $,
        jqueryCookie,
        helperDatatable,
        helperUploader,
        helperDialog,
        helperZoom
        ) {
    return {
        /* Paramètres pour l'uploader */
        uploaderParams: {
            basehref: $('base').attr('href'),
            runtimes: 'gears,html5,silverlight,flash,html4',
            max_file_size: '1000mb',
            multi_selection: true,
            chunk_size: '2mb',
            url: $('base').attr('href') + 'back/media/upload.html?'
                    + 'id_gab_page=' + $('[name=id_gab_page]').val() + '&'
                    + 'gabaritId=' + $('[name=id_gabarit]').val() + '&',
            flash_swf_url: $('base').attr('href') + 'public/default/back/js/plupload/plupload.flash.swf',
            silverlight_xap_url: $('base').attr('href') + 'public/default/back/js/plupload/plupload.silverlight.xap',
            drop_element: '#uploader_popup',
            filters: [
                {
                    title: 'Image files',
                    extensions: 'jpg,jpeg,gif,png'
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
            FilesAdded: function (base, up, files) {
                /* Pour chaque fichier ajouté, on ajoute une progress bar */
                $.each(files, function (i, file) {
                    var tr, td;
                    if (!file.error) {
                        tr = $('<tr>');
                        $('<td>', {
                            colspan: $('.table-media thead').find('th, td').length
                        }).html(file.name + '<div class="progress hidden">' + 
                                                '<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0" style="width: 0;">' +
                                                '0%' +
                                                '</div>' +
                                            '</div>').appendTo(tr);
                        file.tr = tr;
                    }
                    else
                        uploader.splice(i, 1);
                });

                $.each(files, function (i, file) {
                    if (!file.error) {
                        if (i == 0) {
                            file.tr.prependTo($('.table-media tbody'));
                        } else {
                            file.tr.insertAfter(files[i - 1].tr);
                        }
                    }
                });

                up.refresh();
                up.start();
            },
            UploadProgress: function (base, up, file) {
                $('.progress', file.tr).removeClass('hidden');
                $('.progress-bar', file.tr).css({
                    width: file.percent + '%'
                });
                $('.progress-bar', file.tr).html(file.percent + '%')
            },
            FileUploaded: function (base, up, file, info) {
                $('.progress', file.tr).remove();

                var response = $.parseJSON(info.response);

                if (response.status != 'error') {
                    if ('id_temp' in response) {
                        $('input[name=id_temp]:first').val(response.id_temp);
                        $.cookie('id_temp', response.id_temp, {
                            path: '/'
                        });
                    }

                    helperDatatable.reload($('.table-media'));
                } else {
                    file.tr.remove();
                }

                up.splice(0, 1);
                if (up.files.length == 0) {
                    helperDatatable.run($('.table-media'));
                }

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

            $('.uploader_popup').click(function (e) {
                e.preventDefault();
                $.get('back/media/popuplistefichiers.html?id_gab_page=' + $('[name=id_gab_page]').val(), function (response) {
                    $('<div>').html(response).attr('id', 'uploader_popup').appendTo('body');
                    var dialogParams = {
                        'html': $('#uploader_popup')
                    };
                    var $tableMedia = $('.table-media');
                    helperDialog.run(null, dialogParams);
                    helperDatatable.run($tableMedia, {urlConfig: 'back/mediadatatable/listconfig.html'});
                    helperZoom.run($tableMedia);
                    helperUploader.run($('#pickfiles'), currentModule.uploaderParams);
                });
            });
        }
    };
});
