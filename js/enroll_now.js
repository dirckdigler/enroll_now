(function($, Drupal) {
  Drupal.behaviors.initializeMaterialize = {
    attach: function(context, settings) {
      $('.form-with-validations', context)
      .once('initialize-materialize')
      .each(function(){
        const $this = $(this);
        $('input[name="email_validate"]', this).on('copy paste', e => e.preventDefault());
        // Enable Materialize's selects
        $('select', $this).formSelect({
          dropdownOptions: {
            onCloseStart() {
              this.el.dispatchEvent(new Event('dropdownCloseStart', {bubbles: true}));
            }
          }
        });
        // check empty fields and clean select values
        if (!$('.input-field input', this).val().length) {
          $('.select-wrapper input,select', this).val('');
        } else {
          $('.select-wrapper', this).addClass('filled');
        }
        if( /Android|iPhone|iPad|iPod|Opera Mini/i.test(navigator.userAgent)) {
          $('li[id^="select-options"]').on('touchend', function (e) {
            e.stopPropagation();
          });
        }
        // Enable Bootstrap tooltip
        $('[data-toggle="tooltip"]', $this).tooltip();
        const $datepicker = $('.datepicker', $this);
        if( /Android|iPhone|iPad|iPod|Opera Mini/i.test(navigator.userAgent)) {
          const DATE_MASK = 'mm/dd/yyyy';
          $datepicker.on('click', function() {
            if(!$datepicker.val().length) {
              $datepicker.parent().get(0).dataset.mask = DATE_MASK;
              $datepicker[0].setSelectionRange(0,0);
            }
          });
          $datepicker.on('keyup', function(event){
            const { target } = event;
            const newValue = maskDate(target.value);
            target.value = newValue;
            $datepicker.parent().get(0).dataset.mask = DATE_MASK.replace(new RegExp(`^.{1,${newValue.length}}`), newValue);
          });
          function maskDate(value) {
            let v = value.replace(/\D/g,'').slice(0, 8);
            if (v.length >= 5) {
              return `${v.slice(0,2)}/${v.slice(2,4)}/${v.slice(4)}`;
            }
            else if (v.length >= 3) {
              return `${v.slice(0,2)}/${v.slice(2)}`;
            }
            return v
          }
        } else {
          var currentYear = new Date().getFullYear();
          var datepickerOptions = {
            maxDate: new Date(),
            setDefaultDate: $datepicker.val(),
            format: 'mm/dd/yyyy',
            yearRange: [1920, currentYear],
            onClose() {
              this.el.dispatchEvent(new Event('datepickerClose', {bubbles: true}));
            },
          }

          $datepicker.on('click', handleClick);
          function handleClick() {
            if (!instance()) {
              $datepicker.datepicker(datepickerOptions);
              instance().open();
              if(navigator.userAgent.toLowerCase().indexOf("firefox") > -1) {
                $('body').css('overflow', 'scroll');
              }
              return;
            }

            instance().close();
            instance().destroy();
            $datepicker.focus();
            $datepicker[0].setSelectionRange(0,0);
          }
        }
        function instance() {
          return M.Datepicker.getInstance($datepicker);
        }
      });
    }
  };
  Drupal.behaviors.checkSelection = {
    attach: function(context, settings) {
      $('.you-are-eligible', context)
      .once('check-selecion')
      .each(function(){
        const $this = this;
        const $radios = $('input[type="radio"]', $this);
        const $submit = $('.you-are-eligible__submit [type="submit"]', $this);
        $radios.each( (index, element) => {
          $(element).on('change', () => $submit.prop('disabled', ''));
        });

        window.onpageshow = function(event) {
          if (event.persisted) {
              window.location.reload();
              window.onbeforeunload = null;
          }
        };
      });
    }
  };
  Drupal.behaviors.formSubmit = {
    attach: function(context) {
      $('.form-with-validations', context)
      .once('form-submit')
      .each(function () {

        const NOT_PREVIOUS_DATE = 'notprevious';
        const NOT_A_DATE = 'notdate';

        const enrollForm = this;
        const formElements = Array(...enrollForm).filter( element => $(element).is('[name]'));
        const $select = $('.select-wrapper', this);
        const $check = $('#edit-check', this);
        const $datepicker = $('.datepicker', this);
        const selectElements = formElements.filter( element => $(element).is('select'));
        const $ModalPersonalDataButtons = $('#triggerPersonalData button');

        $(formElements).on('focusout', checkFormValidity);
        $(selectElements).on('change', checkFormValidity);

        $check.on('change', checkFormValidity);
        $datepicker.on('datepickerClose', checkFormValidity);

        $select.on('dropdownCloseStart', handleSelectClose);
        $select.on('focusout', handleSelectFocusOut);
        changeButtonState();

        $ModalPersonalDataButtons.on('click', checkSelectionAfterModal);

        function checkSelectionAfterModal() {
          const isModalButtonAccepted = $(this).hasClass('accept');
          const $checkElement = $('input[name="check"]', '.check-element');
          $checkElement.prop('checked', isModalButtonAccepted);
          setTimeout(() => {
            changeButtonState();
          }, 0);
        }
        
        function handleSelectClose() {
          checkFieldValidity($('select', this).get(0));
        }
        function handleSelectFocusOut(event) {
          const { relatedTarget } = event;
          if ($.contains(this, relatedTarget)) {
            return;
          }
          checkFieldValidity($('select', this).get(0));
        }
        function checkFormValidity(event) {
          const { target } = event;
          checkFieldValidity(target);
          changeButtonState();
          // Is not working properly when passing trim function 
          // to the phone-number value, based on the next ticket:
          // @see https://jira.corp.globant.com/browse/GLO163-3063.
          if (target.id !== 'edit-phone-number') {
            setTimeout(() => {
              target.value = target.value.trim();
            }, 0);
          }
        }

        function checkFieldValidity(element) {
          const $element = $(element);
          const isDatepicker = $element.is('.datepicker');
          const isEmpty = element.value.length === 0;
          const isSelect = $element.is('select');

          if (isDatepicker && !isEmpty) {
            const isValid = isValidDate(element.value);
            if (isValid) {
              const isPrevious = isTodayOrPrevious(element.value);
              element.setCustomValidity( isPrevious ? '' : NOT_PREVIOUS_DATE);
            } else {
              element.setCustomValidity(NOT_A_DATE);
            }
          }
          if (element.checkValidity()) {
            setElementAsValid($element)
            if (isSelect) {
              const $selectParent = $element.parent('.select-wrapper');
              $selectParent.addClass('filled');
              setElementAsValid($selectParent);
            }
            return;
          }
          const errorMessage = findErrorMessage(element);
          if (isSelect) {
            const $selectParent = $element.parent('.select-wrapper');
            setElementAsInvalid($selectParent, errorMessage);
          }
          setElementAsInvalid($element, errorMessage);
        }

        function changeButtonState() {
          const $submitButton = $('.button', enrollForm);
          if (enrollForm.checkValidity()) {
            $submitButton.prop('disabled', null);
            return;
          }
          $submitButton.prop('disabled', 'true');
        }

        function setElementAsInvalid($element, message) {
          $element.addClass('invalid');
          $element.nextAll('.helper-text').attr('data-error', message);
        }

        function setElementAsValid($element) {
          $element.removeClass('invalid');
          $element.nextAll('.helper-text').attr('data-error', '');
        }
        function isValidDate(allegedDate) {
          const { month, day, year } = matchDateString(allegedDate);
          const date = new Date(year, (+month-1), day);
          return (Boolean(+date) && date.getDate() === Number(day) && date.getMonth() + 1 === Number(month) && date.getFullYear() === Number(year));
        }
        function isTodayOrPrevious(dateAsString) {
          const inputDate = stringToDate(dateAsString);
          const tomorrowAtMidnight = new Date();
          tomorrowAtMidnight.setDate(tomorrowAtMidnight.getDate() + 1);
          tomorrowAtMidnight.setHours(0,0,0,0);
          return inputDate < tomorrowAtMidnight;
        }

        function stringToDate(dateAsString) {
          const { month, day, year } = matchDateString(dateAsString);
          return new Date(year, (+month-1), day);
        }

        function matchDateString(dateAsString) {
          let month, day, year;
          try {
            [ , month, day, year ] = String(dateAsString).match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/i);
          } catch(error) {
            return false;
          }
          return { month, day, year };
        }

        function findErrorMessage(element) {
          let error = 'valueMissing';
          if($(element).is('.datepicker')) {
            switch (element.validationMessage) {
              case NOT_PREVIOUS_DATE:
                error = 'customError';
                break;
              case NOT_A_DATE:
                error = 'patternMismatch';
                break;
              }
            return element.dataset[error];
          }
          for (const key in element.dataset) {
            if(element.validity[key]) {
              error = key;
              break;
            }
          }
          return element.dataset[error];
        }
      });
    }
  };
  Drupal.behaviors.modalLeave = {
    attach: function(context) {
      $('#page', context)
      .once('modal-leave')
      .each(function () {
        const $modal = $('#popup-modal');
        if (!$modal.length) return;
        const $leaveButton = $('.btn-leave', $modal);
        let linkThatOpenedTheModal;

        $('.navbar a:not(.language-link)', this).on('click', showModalLeave);
        $leaveButton.on('click', redirect);

        // Events listener to remove beforeUnload so it's not executed when redirecting
        $('a').on('click', removeOnBeforeUnload);
        $('form').on('submit', removeOnBeforeUnload);
        $('#navbar-main').on('click', handleClickOnNavbar);

        window.onbeforeunload = preventLeave;

        function showModalLeave(event) {
          event.returnValue = '';
          event.preventDefault();
          event.stopPropagation();
          linkThatOpenedTheModal = event.currentTarget;
          $('#popup-modal-trigger').trigger('click');
          return '';
        }
        function preventLeave(event) {
          event.preventDefault();
          event.returnValue = '';
          return '';
        }
        function redirect() {
          removeOnBeforeUnload();
          fetch('/delete-session-store')
          let { value : href } =  linkThatOpenedTheModal.attributes.href;
          if (isHash(href)) {
            const { currentLanguage } = drupalSettings.path;
            href = `/${currentLanguage}/${href}`;
          }
          window.location.replace(href);
        }
        function handleClickOnNavbar(event) {
          if (!$(event.originalEvent.path[0]).is('a')) {
            return;
          }
          removeOnBeforeUnload();
        }
        function removeOnBeforeUnload() {
          window.onbeforeunload = '';
          // Add the event after the call stack has been executed
          setTimeout(() => {
            window.onbeforeunload = preventLeave;
          }, 100);
        }
        function isHash(text) {
          return /^#/.test(text);
        }
      });
    }
  }
  Drupal.behaviors.fixFooterPosition = {
    attach: function(context, settings) {
      $('.content', context)
      .once('fix-footer-postion')
      .each(function(){
        const $formWithValidations = $('.form-with-validations');
        let windowsize = $(window).width();

        if ($formWithValidations.length === 0) {
          $(window).resize(updateFooterPosition);
          updateFooterPosition();
        }

        function updateFooterPosition() {
          windowsize = $(window).width();
          if (windowsize <= 1024 && windowsize >= 415 ) {
            $('.site-footer').css({
              'position' : 'absolute',
              'width' : '100%',
              'bottom' : '0'
            });
          } else {
            $('.site-footer').css({
              'position' : 'inherit'
            });
          }
        }
      });
    }
  }
}(jQuery, Drupal));
