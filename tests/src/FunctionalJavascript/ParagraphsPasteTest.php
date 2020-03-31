<?php

namespace Drupal\Tests\paragraphs_paste\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;

/**
 * Tests the creation of paragraphs by pasting random data.
 *
 * TODO: Move widget settings form tests in separate class.
 *
 * @group paragraphs_features
 */
class ParagraphsPasteTest extends WebDriverTestBase {

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
   * Test paste functionality.
   */
  public function testPaste() {
    $content_type = 'article';

    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $this->loginAsAdmin();

    // Check that paste functionality is working with default config.
    $text = "Lorem ipsum dolor sit amet.";
    $this->drupalGet("node/add/$content_type");
    $this->assertEquals(TRUE, $driver->isVisible('//*[@data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]'), 'Paragraphs Paste area should be visible.');

    $jsScript = "var pasteData = new DataTransfer(); pasteData.setData('text/plain', '{$text}'); document.querySelector('.paragraphs-paste-action').dispatchEvent(new ClipboardEvent('paste', {clipboardData: pasteData}));";
    $session->executeScript($jsScript);
    $this->assertJsCondition("document.querySelector('[data-drupal-selector=\"edit-field-paragraphs-0-subform-field-text-0-value\"]')", 10000, 'Text field in paragraph form should be visible.');
    $this->assertEquals($text, $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getText(), 'Text should be pasted into paragraph subform.');

#    $this->assertTrue(FALSE);
  }

}
