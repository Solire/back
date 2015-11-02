define(['jquery', 'tinyMCE'], function ($, tinyMCE) {
  return {
    wysiwygs: {},
    save: function(){
      for (id in this.wysiwygs) {
        this.wysiwygs[id].data('wysiwyg').save();
      }
    },
    run: function (wrap, response) {
      var s = {
        mode: 'none',
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
      }

      tinymce.init({});
      var
        edId = $('textarea', wrap).attr('id'),
        ed = tinyMCE.createEditor(edId, s)
      ;

      wrap.addClass('wysiwyg-initialised')
      wrap.data('wysiwyg', ed);
      ed.render();

      this.wysiwygs[edId] = wrap;

      // Event click toggle wysiwyg
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
    },
    destroy: function (wrap) {
      if (typeof wrap != 'undefined') {
        var id = $('textarea', wrap).attr('id');
        tinymce.remove("#" + id);
        delete this.wysiwygs[id];
        wrap.data('wysiwyg', null);
        wrap.removeClass('wysiwyg-initialised')
      }
    }
  };
});
