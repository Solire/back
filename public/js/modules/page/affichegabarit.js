define(['jquery', 'jqueryCookie', 'modules/helper/dialog'], function ($, jqueryCookie, helperDialog) {
  return {
    run: function (wrap, response) {

      $.cookie('id_gab_page', $('input[name=id_gab_page]').val(), {
        path: '/'
      });

      $(document.body).on('click', '.expand-collapse .expand', function (e) {
        e.preventDefault();
        $(this).parent().next().children('fieldset').each(function () {
          if ($('div', this).first().is(':hidden')) {
            $('legend', this).first().click();
          }
        });
      });

      $(document.body).on('click', '.expand-collapse .collapse', function (e) {
        e.preventDefault();
        $(this).parent().next().children('fieldset').each(function () {
          if ($('div', this).first().is(':visible')) {
            $('legend', this).first().click();
          }
        });
      });

      var openingLegend = [];

      $(document.body).on('click', 'legend', function (e) {
        e.preventDefault();

        var indexLegend = $(this).index('legend'),
            $legend     = $(this);
        if (!openingLegend[indexLegend]) {
          openingLegend[indexLegend] = true;
          $(this).next().slideToggle(500, function () {
            $('.gmap-component', this).each(function () {
              google.maps.event.trigger($(this).gmap3('get'), 'resize');
              if ($('input.gmap_lat', $(this).parents('.line:first')).val() != '' && $('input.gmap_lng', $(this).parents('.line:first')).val() != '') {
                var lat = $('input.gmap_lat', $(this).parents('.line:first')).val();
                var lng = $('input.gmap_lng', $(this).parents('.line:first')).val();
                $(this).gmap3('get').setCenter(new google.maps.LatLng(lat, lng))
              }
            })
            $legend.find('i.fa-folder').toggleClass('fa-folder-open');
            openingLegend[indexLegend] = false;
            if ($(this).parent('.block-to-sort').parents('fieldset:first').find('.expand-collapse').length) {
              disabledExpandCollaspse($(this).parent('.block-to-sort').parents('fieldset:first'));
            }
          });
        }
      });

      /*
       * Message d'aide
       */
      $(function () {
        $('[data-toggle="popover"]').each(function () {
          console.log($($(this).data('popover-content')));
          if ($($(this).data('popover-content')).length > 0) {
            $(this).popover({
              container: 'body',
              html: true,
              content: function () {
                var clone = $($(this).data('popover-content')).clone(true).removeClass('hidden');
                return clone;
              }
            });
          }
        })
      })

      if ($('.langue').length > 1) {
        $('.openlang, .openlang-trad').click(function (e) {
          e.preventDefault();
          var $currentLang = $(this).parent().find('.openlang')

          var i = $('.openlang').index($currentLang);

          if ($('.langue').eq(i).is(':hidden')) {
            $('.openlang').addClass('translucide');
            $currentLang.removeClass('translucide')
            $('.langue:visible').slideUp(500);
            $('.langue').eq(i).slideDown(500);
          }
        });
      }

      function disabledExpandCollaspse($fieldset) {
        var expand   = false;
        var collapse = false;
        $fieldset.find('.block-to-sort:not(.block-to-sort .block-to-sort)').each(function () {
          if ($(' > div:first', this).is(':visible')) {
            collapse = true;
          } else {
            expand = true;
          }
        });

        if (expand) {
          $fieldset.find('.expand-collapse:first .expand').removeClass('disabled');
        } else {
          $fieldset.find('.expand-collapse:first .expand').addClass('disabled');
        }

        if (collapse) {
          $fieldset.find('.expand-collapse:first .collapse').removeClass('disabled');
        } else {
          $fieldset.find('.expand-collapse:first .collapse').addClass('disabled');
        }
      }
    }
  }
});