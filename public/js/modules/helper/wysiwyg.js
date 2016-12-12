define(['jquery', 'tinyMCE'], function ($, tinyMCE) {
  return {
    wysiwygs: {},
    editors: {},
    save: function(){
      for (edId in this.editors) {
        this.editors[edId].save();
      }
    },
    run: function (wrap, response) {
      var s = {
        mode: 'none',
        content_style: "body p{font-size: 14px;}",
        height: '290px',
        language_url : 'public/default/back/bower_components/solire.tinymce-i18n/langs/fr_FR.js',
        entity_encoding: 'raw',
        plugins: [
          'autolink link'
        ],
        menubar: false,
        statusbar: false,
        toolbar: 'insertfile undo redo | bold italic | bullist numlist | link image',
        document_base_url: '../../../../',
        image_list: 'back/media/autocomplete.html?tinyMCE',
        link_list: 'sitemap.xml?visible=0&json=1&onlylink=1&tinymce=1',
      }, edId, ed;

      tinyMCE.init({});
      edId = $('textarea', wrap).attr('id');
      ed = tinyMCE.createEditor(edId, s);

      wrap.addClass('wysiwyg-initialised')
      ed.render();

      this.editors[edId] = ed;

      // Event click toggle wysiwyg
      $('.switch-editor a', wrap).click(function (e) {
        var textarea;

        e.preventDefault();

        textarea = $('textarea', wrap);

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
    },
    destroy: function (wrap) {
      if (typeof wrap != 'undefined') {
        var id = $('textarea', wrap).attr('id');

        tinyMCE.remove('#' + id);

        delete this.editors[id];

        wrap.removeClass('wysiwyg-initialised')
      }
    }
  };
});
