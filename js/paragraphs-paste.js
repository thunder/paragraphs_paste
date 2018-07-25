/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Handle event when "Paste" button is clicked
   * @param event
   *   click event
   */
  var pasteHandler = function(event) {

    var clipboardData, pastedData;

    event.stopPropagation();
    event.preventDefault();

    // Get pasted data via clipboard API
    clipboardData = event.originalEvent.clipboardData || window.clipboardData;
    pastedData = clipboardData.getData('Text').split("\n");

    // var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;

    // https://twitter.com/ben_r/status/1020841333596884992
    var $pasteField = $('[data-drupal-selector="edit-paragraphs-paste"]');
    $pasteField.find('[data-drupal-selector="edit-content"]').val(pastedData);
    $pasteField.find('[data-drupal-selector="edit-button"]').trigger('mousedown');
  };

    /**
   * Process paragraph_AddAboveButton elements.
   */
  Drupal.behaviors.paragraphsPasteAction = {
    attach: function (context, settings) {
      var textarea = '<div class="paragraphs-paste-action" contenteditable="true"><div class="paragraphs-paste-message"><p>Paste here.</p></div></div>';
      // @todo test multiple fields.
      var $wrappers = $('[data-paragraphs-paste="enabled"]', context).closest('.paragraphs-container').once('paragraphsPaste');
      $wrappers.each(function() {
        $(this).find('> .fieldset-wrapper').prepend(textarea);
      });
      $wrappers.find('.paragraphs-paste-action')
        .on('paste', pasteHandler);
    }
  };

})(jQuery, Drupal);
