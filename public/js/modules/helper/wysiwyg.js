define(['jquery', 'tinyMCE'], function ($, tinyMCE) {
    return {
        run: function (wrap, response) {
            var s = {
                mode                : 'none',
                height              :'290px',
                entity_encoding     : 'raw',
                plugins             : [
                    'autolink link'
                ],
                menubar             : false,
                statusbar           : false,
                toolbar             : 'insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image',
                document_base_url   : '../../../../',
                image_list          : 'back/media/autocomplete.html?tinyMCE',
                link_list           : 'sitemap.xml?visible=0&json=1&onlylink=1&tinymce=1',
            }

            tinymce.init({});
            var edId = $('textarea', wrap).attr('id')
            var ed = tinyMCE.createEditor(edId, s);
            ed.render();

            $('.switch-editor a', wrap).click(function (e) {
                    e.preventDefault();
                    var textarea = $('textarea', wrap);
                    if ($(this).hasClass('btn-default') && textarea.length > 0) {
                        $(this).removeClass('btn-default').addClass('btn-info');
                        $(this).siblings().removeClass('btn-info').addClass('btn-default');
                        if (ed.isHidden()) {
                            ed.show();
                        } else {
                            ed.hide();
                        }
                    }
                });
        }
    };
});
