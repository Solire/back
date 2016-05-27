define(['jquery', 'modules/helper/wysiwyg'], function ($, wysiwyg) {
  return {
    run: function (wrap, response) {
      var $form = $(wrap).parents('form').first();

      $(wrap).click(function(){
        var
          $clone,
          action = $('base').attr('href') + $(wrap).data('url');
        ;

        wysiwyg.save();
        $clone = $form.clone();
        $clone.attr('action', action);
        $clone.attr('target', '_blank');
        $clone.css('display', 'none');
        $clone.prependTo('body');
        $clone.submit();
        $clone.remove();
      });
    }
  };
});
