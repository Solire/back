
require.config({
  baseUrl: './',
  urlArgs: 'bust=' + (new Date()).getTime(),
  waitSeconds: 15,
  paths: requireJsConfig.paths,
  shim: requireJsConfig.shim
});

require(
  ['jquery', 'modules/helper/amd', 'bootstrap', 'material', 'ripples'],
  function ($, helperAmd) {
    $(function () {
      $.material.init();
      $('[data-toggle="tooltip"]').tooltip();
      helperAmd.run(document, null, true);
    });
  }
);


