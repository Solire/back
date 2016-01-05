define(['jquery', 'bootstrap'], function ($) {
  return {
    run: function (wrap, response) {
      var dataAttributeOptions = {
        offset: {
          top: $(wrap).data('offsetTop')
        }
      };
      $(wrap).affix(dataAttributeOptions);
    }
  };
});
