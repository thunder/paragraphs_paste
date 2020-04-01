<?php

namespace Drupal\Tests\paragraphs_paste\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;

/**
 * Base class for Javascript tests for paragraphs_paste module.
 *
 * @package Drupal\Tests\paragraphs_paste\FunctionalJavascript
 */
abstract class ParagraphsPasteJavascriptTestBase extends WebDriverTestBase {

  use LoginAdminTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs_paste_test',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Simulate paste event.
   *
   * @param string $selector
   *   The CSS selector.
   * @param string $text
   *   Text to copy.
   */
  public function simulatePasteEvent($selector, $text) {
    $this->getSession()->executeScript("var pasteData = new DataTransfer(); pasteData.setData('text/plain', '{$text}'); document.querySelector('{$selector}').dispatchEvent(new ClipboardEvent('paste', {clipboardData: pasteData}));");
  }

  /**
   * Wait for element to be present.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  public function waitForElementPresent($selector, $timeout = 1000, $message = '') {
    $this->assertJsCondition("document.querySelector('{$selector}')", $timeout, $message);
  }

}
