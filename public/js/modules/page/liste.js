define(['jquery', 'jqueryCookie', 'sortable', 'modules/helper/noty'], function ($, jqueryCookie, Sortable, helperNoty) {
    return {
        run: function (wrap, response) {

            var pid,
                freezed,
                positions = [];

            /**
             * Gestion du tri des pages
             */
            var initTri = function (wrap) {
                $(wrap).each(function () {
                    $(this).children('fieldset').each(function (i) {
                        var domId = $(this).attr('id'),
                                tabId = domId.split('_'),
                                id = tabId.pop(),
                                id = parseInt(id);
                        positions[id] = i + 1;
                    });

                    var sortable = Sortable.create(this, {
                        animation: 150,
                        handle: '.sort-move',
                        draggable: '.sort-elmt',
                        onEnd: function (evt) {
                            positions = $(evt.target).data('sortable').toArray();
                            // Si positions différentes
                            if (typeof $(evt.target).data('lastSortableOrder') == 'undefined' || $(evt.target).data('lastSortableOrder').toString() != positions.toString()) {
                                $(evt.target).data('lastSortableOrder', positions);
                                orderProcess();
                            }
                        }
                    });
                    $(this).data('sortable', sortable);
                });
            }

            var orderProcess = function () {
                $.post('back/page/order.html', {
                    'positions': positions
                }, function (data) {
                    if (data.status == 'success') {
                        helperNoty.run(null, {text: 'Succès du déplacement', status: 'success'});
                    } else {
                        helperNoty.run(null, {text: 'Une erreur est survenue', status: 'error'});
                    }

                });

                return false;
            }

            initTri($('.sort-box'));


            $('select[name=id_sous_rubrique]').change(function () {
                var id_sous_rubrique = $(this).val();
                $.cookie('id_sous_rubrique', id_sous_rubrique, {
                    path: '/'
                });
                $(this).parents('form').submit();
            });

            /**
             * Ouverture / fermeture des pages parentes
             */
            $(document.body).on('click', 'legend.solire-js-toggle', function() {
                var $legend = $(this),
                        url = 'back/page/children.html';

                if ($legend.hasClass('noChild')) {
                    if ($legend.data('url') !== undefined) {
                        document.location.href = $legend.data('url');
                    }
                    return false;
                }
                if ($legend.data('ajax') !== undefined) {
                    url = $legend.data('ajax');
                }

                if ($legend.next('div').is(':hidden') && $legend.next('div').html() == '') {

                    $legend.find('i.fa-folder').addClass('fa-folder-open');
                    if (!$legend.next('div').hasClass('children-loaded')) {
                        var id = $legend.parent().attr('id').split('_').pop();

                        var $divToLoad = $legend.next('div')
                        $.ajax({
                            mode: 'queue',
                            port: 'ajaxWhois',
                            type: 'GET',
                            url: url,
                            data: {
                                id_parent: id
                            },
                            success: function (data) {
                                $divToLoad.html(data)
                                $divToLoad.addClass('children-loaded');
                                if (data != '') {
                                    //initTri($('.sort-box', $divToLoad));
                                    $divToLoad.slideToggle(500);
                                    $divToLoad.siblings('.cat-modif').slideToggle(500);
                                }
                            }
                        });
                        $.ajax({
                            mode: 'dequeue',
                            port: 'ajaxWhois'
                        });
                    }
                }
                else {
                    $legend.find('i.fa-folder').toggleClass('fa-folder-open');
                    $legend.next('div').slideToggle(500);
                    $legend.siblings('.cat-modif').slideToggle(500);
                }

                saveState()

                return false;
            });

            /**
             * Enregistre l'état de la liste des pages (Rubriques dépliées)
             * Celui-ci est stocké en cookie
             */
            function saveState() {
                var saveStateListPage = []
                $('legend i.icon-chevron-up').each(function () {
                    saveStateListPage.push($(this).parents('fieldset:first').attr('id'))
                })

                $.cookie('state_list', saveStateListPage, {
                    path: '/'
                });
            }
//
            var anchorFound = false;
            var currentState = 0

            /**
             * Recharge l'état de la liste des pages (Rubriques dépliées)
             * Récupéré dans le cookie correspondant
             */
            function reloadState() {
                var saveStateListPage;

                if ($.cookie('state_list')) {
                    saveStateListPage = $.cookie('state_list').split(',');
                }
                else {
                    saveStateListPage = [];
                }

                $.each(saveStateListPage, function (id, item) {
                    var id = item.split('_').pop();
                    if (typeof $('#' + item).html() === 'undefined') {
                        return;
                    }
                    if ($('#' + item).find('legend:first').data('ajax') !== undefined) {
                        var url = $('#' + item).find('legend:first').data('ajax');
                    } else {
                        var url = 'back/page/children.html';
                    }
                    $.ajax({
                        mode: 'queue',
                        port: 'ajaxWhois',
                        type: 'GET',
                        url: url,
                        data: {
                            id_parent: id
                        },
                        success: function (data) {
                            currentState++;
                            var $legend = $('#' + item).find('legend:first')
                            var $divToLoad = $('#' + item).find('.sort-box:first')
                            $legend.find('i.icon-chevron-down').toggleClass('icon-chevron-up').toggleClass('icon-chevron-down')
                            $divToLoad.html(data)
                            $divToLoad.addClass('children-loaded');
                            if (data != '') {
                                //initTri();
                                $divToLoad.slideToggle(500, function () {
                                    if (currentState == saveStateListPage.length
                                            && anchorFound !== false) {
                                        var heightFixed = 0
                                        //Si la navbar est en fixed (taille écran > 980px)
                                        if ($('.navbar-fixed-top').css('position') == 'fixed') {
                                            heightFixed = $('.navbar-fixed-top').height() + $('#breadcrumbs').height()
                                        }
                                        $.scrollTo($('#gab_page_' + getURLParameter('id_gab_page')), 1000, {
                                            queue: true,
                                            offset: -heightFixed
                                        });
                                    }
                                });
                                $divToLoad.siblings('.cat-modif').slideToggle(500);
                            }

                            if (anchorFound === false
                                    && $('#gab_page_' + getURLParameter('id_gab_page')).length > 0
                                    ) {
                                anchorFound = '#gab_page_' + getURLParameter('id_gab_page')
                            }

                        }
                    });
                })

                $.ajax({
                    mode: 'dequeue',
                    port: 'ajaxWhois'
                });

            }

            reloadState();

            $(document.body).on('click', '.sort-move', function(e) {
                e.preventDefault()
            })

            /**
             * Permet de récupérer des paramètres de l'url
             */
            function getURLParameter(name) {
                return decodeURI(
                        (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1]
                        );
            }
        }
    }
});