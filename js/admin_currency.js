/**
 * @file
 * Attaches the behaviors for the mcapi module.
 */

(function($) {

"use strict";

Drupal.behaviors.mcapiCurrency = {
  attach: function (context, settings) {
    $('.field-plugin-type', context).change(function () {
      var $throbber = $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');

      $(this)
        .addClass('progress-disabled')
        .after($throbber);

        $('input#edit-refresh').mousedown();

        // Disabled elements do not appear in POST ajax data, so we mark the
        // elements disabled only after firing the request.
        $(ajaxElements).prop('disabled', true);
    });
  }
};

})(jQuery);
