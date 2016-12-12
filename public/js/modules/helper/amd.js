define(['jquery'], function ($) {
  function Loader($)
  {
    this.stackSize = 1;

    this.div;

    this.init = function () {
      this.div = $('div#loading')
              .text('Loading&#8230;')
              ;
    }

    this.show = function () {
      this.stackSize++;
      this.div.show();
    }

    this.hide = function () {
      this.stackSize--;
      if (this.stackSize === 0) {
        this.div.hide();
      }
    }

    this.init();
  }

  var loader = new Loader($);

  return {
    loader: loader,
    run: function (wrap, response, showLoader) {
      var self = this;

      $('[data-amd]', wrap).each(function () {
        var
          elem = $(this),
          modules = elem.data('amd').split(',')
        ;

        if (showLoader) {
          self.loader.show();
        }

        require(modules, function () {
          $.each(arguments, function (ii, module) {
            module.run(elem);
          });

          if (showLoader) {
            self.loader.hide();
          }
        });
      });

      if (showLoader) {
        self.loader.hide();
      }
    }
  };
});
