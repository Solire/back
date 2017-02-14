define(['jquery'], function ($) {
  return {
    init: function (wrap) {
      var currentModule = this,
        tourWrapper = $('.tour-wrapper'),
        tourSteps = tourWrapper.children('li'),
        coverLayer = $('.tour-cover-layer'),
        tourStepInfo = $('.tour-more-info'),
        tourTrigger = wrap;

      if (tourWrapper.length != 0) {
        wrap.removeClass('hidden');
      }

      var visibleTourSteps = [];

      tourTrigger.on('click', function (e) {
        e.preventDefault();
        //start tour
        if (!tourWrapper.hasClass('active')) {
          //in that case, the tour has not been started yet
          visibleTourSteps = currentModule.buildTour(tourSteps);
          currentModule.openTour(visibleTourSteps, tourWrapper, coverLayer);
        }
      });

      //change visible step
      tourStepInfo.on('click', '.tour-prev', function (event) {
        event.preventDefault();
        //go to prev step - if available
        ( !$(event.target).hasClass('inactive') ) && currentModule.changeStep(visibleTourSteps, coverLayer, 'prev');
      });
      tourStepInfo.on('click', '.tour-next', function (event) {
        event.preventDefault();
        //go to next step - if available
        ( !$(event.target).hasClass('inactive') ) && currentModule.changeStep(visibleTourSteps, coverLayer, 'next');
      });

      //close tour
      tourStepInfo.on('click', '.tour-close', function (event) {
        event.preventDefault();
        currentModule.closeTour(visibleTourSteps, tourWrapper, coverLayer);
      });

      tourWrapper.add(coverLayer).on('click', function (event) {
        // Close on tourWrapper's click only but not on children's click event
        if (event.target != this) {
          return;
        }
        event.preventDefault();
        currentModule.closeTour(visibleTourSteps, tourWrapper, coverLayer);
      });

      //detect swipe event on mobile - change visible step
      tourStepInfo.on('swiperight', function (event) {
        //go to prev step - if available
        if (!$(this).find('.tour-prev').hasClass('inactive') && currentModule.viewportSize() == 'mobile') {
          currentModule.changeStep(visibleTourSteps, coverLayer, 'prev');
        }
      });
      tourStepInfo.on('swipeleft', function (event) {
        //go to next step - if available
        if (!$(this).find('.tour-next').hasClass('inactive') && currentModule.viewportSize() == 'mobile') {
          currentModule.changeStep(visibleTourSteps, coverLayer, 'next');
        }
      });

      //keyboard navigation
      $(document).keyup(function (event) {
        if (event.which == '37' && !visibleTourSteps.filter('.is-selected').find('.tour-prev').hasClass('inactive')) {
          currentModule.changeStep(visibleTourSteps, coverLayer, 'prev');
        } else if (event.which == '39' && !visibleTourSteps.filter('.is-selected').find('.tour-next').hasClass('inactive')) {
          currentModule.changeStep(visibleTourSteps, coverLayer, 'next');
        } else if (event.which == '27') {
          currentModule.closeTour(visibleTourSteps, tourWrapper, coverLayer);
        }
      });
    },
    buildTour: function (tourSteps) {
      var currentModule = this;
      // Show only step with target
      var visibleTourSteps = $();
      tourSteps.each(function () {
        var stepConfig = $(this).data();
        if ($(stepConfig.tourTarget).length > 0
          && ($(stepConfig.tourTarget).is(':visible') || $(stepConfig.tourTarget).parents('fieldset:visible:first').length > 0)
        ) {
          $('.tour-dot-indicator', this).remove();
          $(this).prepend('<span class="tour-dot-indicator"></span>')
          visibleTourSteps = visibleTourSteps.add(this);
        }
      });

      var stepsNumber = visibleTourSteps.length;

      //create the navigation for each step of the tour
      currentModule.createNavigation(visibleTourSteps, stepsNumber);

      return visibleTourSteps;
    },
    createNavigation: function (steps, n) {
      var tourNavigationHtml = '<div class="tour-nav"><span><b class="tour-actual-step">1</b> sur ' + n + '</span><ul class="tour-tour-nav"><li><a href="#0" class="tour-prev">&#171; Précédent</a></li><li><a href="#0" class="tour-next">Suivant &#187;</a></li></ul></div><a href="#0" class="tour-close">Fermer</a>';

      steps.each(function (index) {
        var step = $(this),
          stepNumber = index + 1,
          nextClass = ( stepNumber < n ) ? '' : 'inactive',
          prevClass = ( stepNumber == 1 ) ? 'inactive' : '';
        step.children('.tour-more-info').find('.tour-nav').remove();
        var nav = $(tourNavigationHtml).find('.tour-next').addClass(nextClass).end().find('.tour-prev').addClass(prevClass).end().find('.tour-actual-step').html(stepNumber).end().appendTo(step.children('.tour-more-info'));
      });
    },
    showStep: function (step, layer) {
      var currentModule = this,
        stepConfig = step.data();

      // if target is not visible
      if ($(stepConfig.tourTarget).is(':visible') == false) {
        $($(stepConfig.tourTarget).parents('fieldset').get().reverse()).each(function () {
          if ($(this).find('div:first').is(':hidden')) {
            $(this).find('legend:first').click();
            setTimeout(function () {
              currentModule.position(step);
              step.addClass('is-selected').removeClass('move-left');
              currentModule.smoothScroll(step.children('.tour-more-info'));
              currentModule.showLayer(layer);
            }, 500);
          }
        });
      } else {
        currentModule.position(step);
        step.addClass('is-selected').removeClass('move-left');
        currentModule.smoothScroll(step.children('.tour-more-info'));
        currentModule.showLayer(layer);
      }
    },
    position: function (step) {
      var currentModule = this,
        stepConfig = step.data(),
        tourTarget = $(stepConfig.tourTarget).filter(':visible:first'),
        position = tourTarget.offset();

      if ($('.tour-more-info', step).hasClass('bottom')) {
        var top = position.top + tourTarget.outerHeight() - 5,
          left = position.left + (tourTarget.outerWidth() / 2) - 5;
      } else if ($('.tour-more-info', step).hasClass('top')) {
        var top = position.top - 5,
          left = position.left + (tourTarget.outerWidth() / 2) - 5;
      } else if ($('.tour-more-info', step).hasClass('left')) {
        var top = position.top + (tourTarget.outerHeight() / 2) - 5,
          left = position.left - 5;
      } else if ($('.tour-more-info', step).hasClass('right')) {
        var top = position.top + (tourTarget.outerHeight() / 2) - 5,
          left = position.left + tourTarget.outerWidth() - 5;
      }

      step.css({top: top, left: left});
    },
    smoothScroll: function (element) {
        /* Fixed top toolbar */
      var offsetY = 200;
      (element.offset().top - offsetY < $(window).scrollTop()) && $('body,html').animate({'scrollTop': element.offset().top - offsetY}, {
        duration: 400,
        queue: false
      });
      (element.offset().top + offsetY + element.height() > $(window).scrollTop() + $(window).height()) && $('body,html').animate({'scrollTop': element.offset().top + offsetY + element.height() - $(window).height()}, {
        duration: 400,
        queue: false
      });
      //(element.offset().left + element.width() > $(window).scrollLeft() + $(window).width()) && $('body,html').animate({'scrollLeft': element.offset().left + offsetY + element.width() - $(window).width()}, { duration: 400, queue: false });
    },
    showLayer: function (layer) {
      layer.addClass('is-visible');
    },
    changeStep: function (steps, layer, bool) {
      var currentModule = this;
      var visibleStep = steps.filter('.is-selected'),
        delay = (currentModule.viewportSize() == 'desktop') ? 300 : 0;
      visibleStep.removeClass('is-selected');

      var visibleStepIndex = steps.index(visibleStep),
        nextStep = steps[visibleStepIndex + 1],
        prevStep = steps[visibleStepIndex - 1];

      (bool == 'next') && visibleStep.addClass('move-left');

      setTimeout(function () {
        ( bool == 'next' )
          ? currentModule.showStep($(nextStep), layer)
          : currentModule.showStep($(prevStep), layer);
      }, delay);
    },
    openTour: function (steps, wrapper, layer) {
      var currentModule = this;
      wrapper.addClass('active');
      currentModule.showStep(steps.eq(0), layer);

      // Resize event
      $(window).resize(function () {
        var step = $(steps).filter('.is-selected');
        currentModule.position(step);
      });
    },
    closeTour: function (steps, wrapper, layer) {
      steps.removeClass('is-selected move-left');
      wrapper.removeClass('active');
      layer.removeClass('is-visible');

      // Remove Resize event
      $(window).off('resize');
    },
    viewportSize: function () {
      return window.getComputedStyle(document.querySelector('.tour-wrapper'), '::before').getPropertyValue('content').replace(/"/g, "").replace(/'/g, "");
    },
    run: function (wrap) {
      var currentModule = this;
      currentModule.init(wrap);
    }
  };
});
