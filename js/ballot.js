/**
 * @file
 * Condition UI behaviors.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.ballotOptionsControl = {
    attach: function () {
      var selects = $('.election-candidate-preference-select');
      console.log('selects', selects);

      var disableOptions = function (triggering_elem, others) {
        var val = triggering_elem.val(), prev = triggering_elem.data('previous_value');
        if (prev !== undefined) {
          others.find('option[value="' + prev + '"][disabled]').removeAttr('disabled');
        }
        if (val !== '' && val !== 'NONE') {
          others.find('option[value="' + val + '"]').not(':selected').attr('disabled', 'disabled');
        }
        triggering_elem.data('previous_value', val);
      };

      selects.each(function () {
        var thisSelect = $(this);
        var others = selects.not(thisSelect);
        var allow_equal = thisSelect.closest('form').hasClass('allow-equal');

        if (!allow_equal) {
          disableOptions(thisSelect, others);
          thisSelect.change(function () {
            disableOptions(thisSelect, others);
          });
        }
      });
    }
  };

}(jQuery, Drupal));
