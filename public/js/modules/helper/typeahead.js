define(['jquery', 'typeahead'], function ($) {
  return {
    defaults: {
      url: null,
      name: null,
      suggestionData: 'value',
      displayData: 'value'
    },
    run: function (wrap, options) {
      options = $.extend(true, {}, this.defaults, options);

      // Surcharge via les attributs data
      var optionsFromData = $(wrap).data();
      options             = $.extend(true, {}, options, optionsFromData);

      $(wrap).typeahead({
            minLength: 1,
            highlight: false,
            hint: false
          },
          {
            name: options.name,
            display: options.displayData,
            source: function (query, process) {
              var url = options.url;

              $.getJSON(
                  url,
                  {
                    term: query
                  },
                  function (response) {
                    process(response);
                  }
              );
            },
            templates: {
              suggestion: function (data) {
                var suggestion = data[options.suggestionData];
                return suggestion;
              }
            }
          })
    }
  };
});
