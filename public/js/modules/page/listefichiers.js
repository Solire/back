define(['jquery', 'jsTree'], function ($) {
    return {
        run: function (wrap, response) {
            var tree;
//            var tree, uploader, oTable = null, nomdesobjets = 'fichier';
//            var basehref = ''
//            var extensionsImage = ['jpg', 'jpeg', 'gif', 'png'];
//            var image,
//                    contenu,
//                    resid = null,
//                    restype,
//                    currentData,
//                    oTable = null
//            orderby = {
//                champ: "date_crea",
//                sens: "desc"
//            },
//            orderstates = ["", "asc", "desc", ""],
//                    orderclasses = ["ui-icon-carat-2-n-s", "ui-icon-carat-1-n", "ui-icon-carat-1-s", "ui-icon-carat-2-n-s"];

            /**
             * Suppression des fichiers
             */
//            $(".delete-file").live("click", function (e) {
//                e.preventDefault()
//                var tr = $(this).parents('tr').first();
//                var heading = 'Confirmation de suppression de fichier';
//                var question = 'Etes-vous sur de vouloir supprimer ce fichier ? ';
//                var cancelButtonTxt = 'Annuler';
//                var okButtonTxt = 'Confirmer';
//
//                var callback = function () {
//                    $.post('back/media/delete.html', {
//                        id_media_fichier: tr.attr('id').split('_').pop()
//                    }, function (data) {
//                        if (data.status == 'success') {
//                            oTable.fnReloadAjax()
//                        }
//                    }, 'json');
//                    var heading = 'Confirmation de suppression de fichier';
//                    var message = 'Le fichier'
//                            + ' a été supprimé avec succès'
//                    var closeButtonTxt = 'Fermer';
//                    myModal.message(heading, message, closeButtonTxt, 2500);
//
//                }
//
//                myModal.confirm(heading, question, cancelButtonTxt, okButtonTxt, callback);
//            })


//            function reloadDatatable(idGabPage) {
//                if (idGabPage != 0)
//                    $("#colright").show()
//                else {
//                    $("#colright").hide()
//                }
//                var oTable = eval("oTable_" + $("table.display").attr("id").substr(8));
//
//                var oSettings = oTable.fnSettings();
//                if (oSettings.sAjaxSource.indexOf("&filter[]") != -1)
//                    oSettings.sAjaxSource = oSettings.sAjaxSource.substr(0, oSettings.sAjaxSource.indexOf("&filter[]"))
//                oTable.fnReloadAjax(oSettings.sAjaxSource + '&filter[]=media_fichier.id_gab_page|' + idGabPage);
//                $('.dataTables_filter input').attr("placeholder", "Recherche...");
//            }


            /**
             * Titre trop long (scroll)
             */
//                jQuery.fn.scroller = function () {
//                    $(this).SetScroller({
//                        velocity: 60,
//                        direction: 'horizontal',
//                        startfrom: 'right',
//                        loop: 'infinite',
//                        movetype: 'linear',
//                        onmouseover: 'pause',
//                        onmouseout: 'pause',
//                        onstartup: 'pause',
//                        cursor: 'pointer'
//                    });
//
//                    $(this).unbind("mouseover");
//                    $(this).unbind("mouseout");
//                    //how to play or stop scrolling animation outside the scroller...
//                    $(this).mouseenter(function () {
//                        if ($('.scrollingtext', this).width() > $(this).width())
//                            $(this).PlayScroller();
//                    });
//                    $(this).mouseleave(function () {
//                        $(this).PauseScroller();
//                        $('.scrollingtext', this).css("left", "0px");
//                    });
//
//                    $(' .scrollingtext', this).css("left", "0px");
//                    return this;
//                }
//
//
//
//                $(".horizontal_scroller").livequery(function () {
//                    var newHeight = 0, $this = $(this);
//                    $.each($this.children(), function () {
//                        newHeight += $(this).height();
//                    });
//                    $this.height(newHeight);
//                    $this.scroller();
//
//                });






//                basehref = $('base').attr('href');
            //reloadDatatable(0)

            tree = $("#folders", wrap).jstree({
                'core': {
                    'themes': {
                        'name': 'proton',
                        'responsive': true
                    },
                    'animation' : 350,
                    'data': {
                        "url": "back/media/folderlist.html",
                        "dataType": "json",
                        "data": function (n) {
                            return {
                                "id": n.id != '#' ? n.id.replace("node_", "") : ""
                            };
                        }
                    }
                },
                "plugins": [
                    "types"
                ]
            })
            
            $('#folders').on('open_node.jstree', function (e, data) { $('#' + data.node.id + ' .jstree-anchor:first i').addClass('fa-folder-open')});
            $('#folders').on('close_node.jstree', function (e, data) { $('#' + data.node.id + ' .jstree-anchor:first i').removeClass('fa-folder-open') });


            //////////////////// PLUPLOAD ////////////////////
//                $('#pickfiles').pluploader({
//                    basehref: basehref,
//                    drop_element: '#colright',
//                    runtimes: 'gears,html5,silverlight,flash,html4',
//                    multi_selection: true,
//                    max_file_size: '1000mb',
//                    chunk_size: '2mb',
//                    url: basehref + 'back/media/upload.html',
//                    flash_swf_url: 'public/default/back/js/plupload/plupload.flash.swf',
//                    silverlight_xap_url: 'public/default/back/js/plupload/plupload.silverlight.xap',
//                    filters: [
//                        {
//                            title: "Image files",
//                            extensions: "jpg,jpeg,gif,png"
//                        },
//                        {
//                            title: "Zip files",
//                            extensions: "zip,rar,bz2"
//                        },
//                        {
//                            title: "Adobe",
//                            extensions: "pdf,eps,psd,ai,indd"
//                        },
//                        {
//                            title: "Fichiers vidéos",
//                            extensions: "mp4"
//                        }
//                    ],
//                    unique_names: false,
//                    multiple_queues: true,
//                    FilesAdded: function (base, up, files) {
//                        if (restype == 'page') {
//                            $.each(files, function (i, file) {
//                                var tr, td;
//                                if (!file.error) {
//                                    tr = $('<tr>').addClass("filenotused");
//                                    $('<td>', {
//                                        colspan: 4
//                                    }).html(file.name + '<div class="progressbar"></div>').appendTo(tr);
//                                    file.tr = tr;
//                                }
//                            });
//
//                            $.each(files, function (i, file) {
//                                if (i == 0)
//                                    file.tr.prependTo($('#foldercontent'));
//                                else
//                                    file.tr.insertAfter(files[i - 1].tr);
//                            });
//
//                            $('.progressbar').progressbar({
//                                value: 0
//                            });
//
//                            up.refresh();
//                            up.start();
//                        }
//                        else {
//                            up.splice(0, uploader.files.length);
//                        }
//                    },
//                    UploadProgress: function (base, up, file) {
//                        $(file.tr, '.progressbar').progressbar("value", file.percent);
//                    },
//                    Error: function (up, err) {
//                        err.file.error = true;
//                        up.refresh();
//                    },
//                    FileUploaded: function (base, up, file, info) {
//                        $(file.tr, '.progressbar').progressbar("destroy");
//
//                        var response = $.parseJSON(info.response);
//
//                        if (response.status != "error") {
//                            oTable.fnReloadAjax();
//                        }
//                    }
//                });

//                image = $(null);

//                $('.previsu').live('click', function (e) {
//                    e.preventDefault();
//                    image = $(this);
//
//                    var link = $(this).attr('href');
//                    var ext = link.split('.').pop().toLowerCase();
//                    if ($.inArray(ext, extensionsImage) != -1) {
//                        $('<img>', {
//                            'src': link
//                        }).load(function () {
//                            myModal.message("Prévisualisation", $(this), "Fermer", false, true)
//                        });
//                    } else {
//
//                    }
//                });

//                $('#search').keyup(function () {
//                    $('#node_' + resid + ' > a').click()
//                });

        }
    }
});