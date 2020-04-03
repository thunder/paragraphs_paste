<?php

namespace Drupal\Tests\paragraphs_paste\FunctionalJavascript;

/**
 * Tests the creation of paragraphs by pasting random data.
 *
 * @group paragraphs_paste
 */
class ParagraphsPasteTest extends ParagraphsPasteJavascriptTestBase {

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
    $this->assertTrue($driver->isVisible('//*[@data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]'), 'Paragraphs Paste area should be visible.');

    $this->simulatePasteEvent('[data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]', $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals("<p>{$text}</p>", $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getText(), 'Text should be pasted into paragraph subform.');
  }

  /**
   * Verify that the paste area stays after a first paste.
   */
  public function testPastingTwice() {
    $this->testPaste();

    $session = $this->getSession();
    $page = $session->getPage();

    $text = "Bacon ipsum dolor amet cow picanha andouille strip steak tongue..";
    $this->simulatePasteEvent('[data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]', $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-1-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals("<p>{$text}</p>", $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-1-subform-field-text-0-value"]')->getText(), 'Text should be pasted into paragraph subform.');
  }

}
