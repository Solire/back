define(['jquery', 'modules/helper/dialog', 'modules/helper/crop'], function ($, helperDialog, helperCrop) {
  return {
    wrap: null,
    modalCrop: null,
    cropTarget: null,
    select: null,
    src: null,
    minWidth: null,
    minHeight: null,
    crop: null,
    wShow: null,
    hShow: null,
    show: null,
    wRedim: null,
    hRedim: null,
    redim: null,
    /**
     *
     *
     * @param {jQuery} wrap
     * @param {object} options
     *
     * @returns {void}
     */
    run: function (wrap, options) {
      var self = this;

      self.wrap = wrap;

      // Event click to open crop modal
      $(self.wrap).click(function (e) {
        var src;

        self.select = $(this).parents('.form-group:first').find('select');

        e.preventDefault();

        if ($('#modalCrop').length == 0) {
          return false;
        }

        src = $(this)
          .parents('.form-group:first')
          .find('.field-file-link')
          .attr('href')
        ;

        $('<img>', {
          src: src
        }).load(function () {
          self.init(src);
        });
      });
    },
    /**
     *
     *
     * @param {type} src
     *
     * @returns {undefined}
     */
    init: function (src) {
      var self = this;

      self.modalCrop = $('#modalCrop').clone().removeClass('hidden');
      self.minWidth = parseInt(self.select.data('min-width'));
      self.minHeight = parseInt(self.select.data('min-height'));

      self.wShow = $('.wShow', self.modalCrop);
      self.hShow = $('.hShow', self.modalCrop);
      self.show = self.wShow.add(self.hShow);

      self.wRedim = $('.wRedim', self.modalCrop);
      self.hRedim = $('.hRedim', self.modalCrop);
      self.redim = self.wRedim.add(self.hRedim);

      self.cropTarget = $('.crop-target', self.modalCrop);

      self.cropTarget.attr('src', src);

      helperDialog.run(null, {
        html: self.modalCrop
      });

      self.crop = new helperCrop(
        self.cropTarget,
        {
          url: 'back/media/crop.html',
          minSize: [
            self.minWidth,
            self.minHeight
          ],
          params: {
            gabaritId: $('[name=id_gabarit]').val(),
            id_gab_page: $('[name=id_gab_page]').val(),
            src: src,
            dest: src,
            minwidth: self.minWidth,
            minheight: self.minHeight
          }
        }
      );

      if (self.minWidth) {
        self.wRedim.val(self.minWidth).attr('readonly', true);
      }

      if (self.minHeight) {
        self.hRedim.val(self.minHeight).attr('readonly', true);
      }

      if (self.minWidth && self.minHeight) {
        self.ratio = self.minWidth / self.minHeight;
        self.crop.getApi().setOptions({
          aspectRatio: self.ratio
        });
      }

      $('.form-crop-submit').click(function (e) {
        self.submit();
      });

      self.crop.getApi().container.on('cropmove cropend', function (e, s, c) {
        self.changeInput(c);
      });

      self.show.on('focusout', function (e) {
        self.changeSelection();
      });

      self.redim.on('focusout', function (e) {
        self.respectRatio($(this));
      });

      self.cropTarget.on('croped.helper.crop', function (e, response) {
        helperDialog.close();
        self.select.append('<option>' + response.filename + '</option>');
        self.select.val(response.filename).trigger('change');
        self.select.trigger('select2:select', {data: response});
      });
    },
    /**
     * Après chgt de la sélection, on maj les valeurs dans les inputs
     *
     * @param {object} size
     *
     * @returns {void}
     */
    changeInput: function (size) {
      var
        self = this,
        w = parseInt(Math.ceil(size.w)),
        h = parseInt(Math.ceil(size.h))
      ;

      if (w < self.minWidth) {
        w = self.minWidth;
      }
      if (h < self.minHeight) {
        h = self.minHeight;
      }

      self.w = w;
      self.h = h;

      self.wShow.val(w);
      self.hShow.val(h);

      if (!self.wRedim.attr('readonly')) {
        self.wRedim.val('');
      }
      if (!self.wRedim.attr('readonly')) {
        self.wRedim.val('');
      }
    },
//    /**
//     * Après chgt des inputs
//     *
//     * @returns {void}
//     */
//    changeSelection: function () {
//      var
//        self = this,
//        w = parseInt(self.wShow.val()),
//        h = parseInt(self.hShow.val()),
//        size = self.crop.getApi().ui.selection.core.unscale(self.crop.getApi().getSelection())
//      ;
//
//      self.crop.getApi().animateTo([
//        size.x,
//        size.y,
//        w,
//        h
//      ]);
//
//      if (!self.wRedim.attr('readonly')) {
//        self.wRedim.val('');
//      }
//      if (!self.wRedim.attr('readonly')) {
//        self.wRedim.val('');
//      }
//    },
    /**
     *
     *
     * @param {jQuery} elmt
     *
     * @returns {void}
     */
    respectRatio: function (elmt) {
      var
        self = this,
        w = parseInt(self.wShow.val()),
        h = parseInt(self.hShow.val()),
        ratio = w / h
      ;

      if (elmt.attr('readonly')) {
        return;
      }

      if (elmt.is(self.wRedim)) {
        self.hRedim.val(parseInt(elmt.val() / ratio));
      } else {
        self.wRedim.val(parseInt(elmt.val() * ratio));
      }
    },
    /**
     *
     *
     * @returns {void}
     */
    submit: function () {
      var
        self = this,
        size = $.extend(
          {},
          self.crop.getApi().ui.selection.core.unscale(self.crop.getApi().getSelection())
        ),
        minwidth = parseInt(self.wRedim.val()),
        minheight = parseInt(self.hRedim.val())
      ;

      if (minwidth && minheight) {
        size.minwidth = minwidth;
        size.minheight = minheight;
        size['force-width'] = 'width-height';
      }

      self.crop.sendRequest(size);
    }
  };
});
