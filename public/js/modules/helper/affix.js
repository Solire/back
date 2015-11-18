define(['jquery', 'bootstrap'], function ($) {
  return {
    run: function (wrap, response) {
      var dataAttributeOptions = {
        offset: {
          top: $(wrap).data('offsetTop')
        }
      };
      console.log(dataAttributeOptions);
      $(wrap).affix(dataAttributeOptions);
    }
  };
});
