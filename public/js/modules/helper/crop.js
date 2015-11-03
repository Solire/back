define(['jquery', 'jcrop'], function ($) {
  function cropInstance(wrap, options)
  {
    this.defaults = {
      boxWidth: 800,
      boxHeight: 600,
      url: 'back/media/crop.html',
      params: {}
    };
    this.wrap = wrap;
    this.options = {};
    this.api = null;

    this.init = function (options) {
      var self = this, minSize, newMS;

      $.extend(self.options, self.defaults, options, $(self.wrap).data());

      minSize = self.options.minSize;
      delete self.options.minSize;

      $(this.wrap).Jcrop(self.options);
      self.api = $(this.wrap).Jcrop('api');

      newMS = self.api.ui.manager.core.scale({
        w: minSize[0],
        h: minSize[1]
      });
      minSize = [
        newMS.w,
        newMS.h
      ];
      self.api.setOptions({
        minSize: minSize
      });
    }

    this.init(options);
  }

  cropInstance.prototype.sendRequest = function (data) {
    var
      self = this,
      data = data || {}
    ;

    $.extend(self.options.params, data);

    $.post(self.options.url, self.options.params, function (response) {
      $(self.wrap).trigger('croped.helper.crop', response);
    }, 'json');
  };

  cropInstance.prototype.getApi = function () {
    return this.api;
  }

  return cropInstance;
});
