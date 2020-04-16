/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function (Drupal, CKEDITOR) {

  'use strict';

  /**
   * Handle event when "Paste" button is clicked.
   *
   * @param {event} event The event.
   */
  var pasteHandler = function (event) {
    var clipboardData;

    event.stopPropagation();
    event.preventDefault();

    // Get pasted data via clipboard API.
    clipboardData = event.clipboardData || window.clipboardData;
//    var targetSelector = event.currentTarget.dataset.paragraphsPasteTarget.replace(/action$/, 'content-value');
    var targetElement = document.querySelector('[data-drupal-selector="' + event.currentTarget.dataset.paragraphsPasteTarget.replace(/action$/, 'content-value') + '"]');

    var editable = CKEDITOR.instances[targetElement.id].editable();
    editable.$.dispatchEvent(new ClipboardEvent('paste', {clipboardData: clipboardData}));
  };

  /**
   * Theme function for paste area.
   *
   * @param {object} options
   *   Options for delete confirmation button.
   *
   * @return {HTMLElement}
   *   Returns paste area as DOM Node .
   */
  Drupal.theme.paragraphsPasteActionArea = function (options) {
    var message = document.createElement('p');
    message.textContent = Drupal.t('Paste here.');

    var messageWrapper = document.createElement('div');
    messageWrapper.setAttribute('class', 'paragraphs-paste-message');
    messageWrapper.appendChild(message);

    var areaWrapper = document.createElement('div');
    areaWrapper.setAttribute('class', 'paragraphs-paste-action');
    areaWrapper.setAttribute('data-paragraphs-paste-target', options.target);
    areaWrapper.appendChild(messageWrapper);

    return areaWrapper;
  };

  /**
   * Add paragraphsPasteAction behavior.
   */
  Drupal.behaviors.paragraphsPasteAction = {
    attach: function (context) {
      var buttons = context.querySelectorAll('[data-paragraphs-paste="enabled"]');

      buttons.forEach(button => {
        var wrapper = button.closest('.form-wrapper');

        if (!wrapper.getAttribute('paragraphsPasteActionProcessed')) {
          var area = Drupal.theme('paragraphsPasteActionArea', {target: button.dataset.drupalSelector});
          area.addEventListener('paste', pasteHandler);
          area.addEventListener('mousedown', function () {
            this.setAttribute('contenteditable', true);
          });

          wrapper.prepend(area);
          wrapper.setAttribute('paragraphsPasteActionProcessed', true);

          CKEDITOR.on("instanceReady", event => {
            var editor = event.editor;
            if (editor.element.$.dataset.drupalSelector === button.dataset.drupalSelector.replace(/action$/, 'content-value')) {
              editor.config.pasteFromWordPromptCleanup = false;
              editor.on('afterPaste', event => {
                document.querySelector('[data-drupal-selector="' + event.editor.element.$.dataset.drupalSelector.replace('content-value', 'action') + '"]')
                  .dispatchEvent(new Event('mousedown'));
              });
              editor.on('paste', event => {
                console.log(event);
                var x = 1;
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
          wrapper.querySelector('.paragraphs-paste-action').remove();
          wrapper.removeAttribute('paragraphsPasteActionProcessed');
        }
      });
    }
  };

})(Drupal, CKEDITOR);
