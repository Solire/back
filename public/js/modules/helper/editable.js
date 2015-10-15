define(['jquery', 'xEditable'], function($){
  return {
    run : function(wrap, response){
      $.fn.editable.defaults.mode = 'popup';
      $.fn.editable.defaults.inputclass = '';
      $(wrap).editable();
    }
  }
});