/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function (Drupal, CKEDITOR) {

  'use strict';

  /**
   * Add paragraphsPasteAction behavior.
   */
  Drupal.behaviors.paragraphsPasteAction = {
    attach: function (context) {
      var buttons = context.querySelectorAll('[data-paragraphs-paste="enabled"]');

      buttons.forEach(button => {
        var wrapper = button.closest('.form-wrapper');
        var contentElem = wrapper.querySelector('.paragraphs-paste-area');

        if (!wrapper.getAttribute('paragraphsPasteActionProcessed')) {
          wrapper.setAttribute('paragraphsPasteActionProcessed', true);
          wrapper.prepend(contentElem);
          contentElem.classList.remove('visually-hidden');

          CKEDITOR.on('instanceReady', event => {
            var editor = event.editor;
            if (editor.element.$.dataset.drupalSelector === button.dataset.drupalSelector.replace(/action$/, 'content-value')) {
              editor.config.pasteFromWordPromptCleanup = false;
              editor.on('afterPaste', event => {
                document.querySelector('[data-drupal-selector="' + event.editor.element.$.dataset.drupalSelector.replace('content-value', 'action') + '"]')
                  .dispatchEvent(new Event('mousedown'));
              });
            }
          });
        }
      });
    },
    detach: function (context) {
      context.querySelectorAll('[data-paragraphs-paste="enabled"]').forEach(button => {
        var wrapper = button.closest('.form-wrapper');
        if (wrapper.getAttribute('paragraphsPasteActionProcessed')) {
          var contentElem = wrapper.querySelector('.paragraphs-paste-area');
          contentElem.classList.add('visually-hidden');
          button.parentNode.prepend(contentElem);
          wrapper.removeAttribute('paragraphsPasteActionProcessed');
        }
      });
    }
  };

})(Drupal, CKEDITOR);
