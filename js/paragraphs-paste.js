/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function (Drupal, CKEDITOR) {

  'use strict';

  /**
   * Forward 'paste' event ckeditor.
   *
   * @param {event} event The event.
   */
  var pasteHandler = function (event, data) {
    event.currentTarget.classList.add('paragraphs-paste-action-focus');

    var targetElement = document.querySelector('[data-drupal-selector="' + event.currentTarget.dataset.paragraphsPasteTarget.replace(/action$/, 'content-value') + '"]');
    var editor = CKEDITOR.instances[targetElement.id];

    editor.focus();
    var editableElem = editor.editable().$;
    // Reset editor contents.
    editableElem.innerHTML = '';

    event.stopPropagation();
    event.preventDefault();
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
          area.addEventListener('mousedown', pasteHandler);
          area.addEventListener('mouseleave', event => {
            event.currentTarget.classList.remove('paragraphs-paste-action-focus');
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
