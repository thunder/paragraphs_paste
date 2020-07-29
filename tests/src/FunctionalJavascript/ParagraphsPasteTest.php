<?php

namespace Drupal\Tests\paragraphs_paste\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the creation of paragraphs by pasting random data.
 *
 * @group paragraphs_paste
 */
class ParagraphsPasteTest extends ParagraphsPasteJavascriptTestBase {

  /**
   * Test paste functionality.
   *
   * @dataProvider providerTestPaste
   */
  public function testPaste($experimental_mode, $expected) {
    $content_type = 'article';
    $this->setPasteMethod($experimental_mode);

    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $this->loginAsAdmin();

    // Check that paste functionality is working with default config.
    $text = "Lorem ipsum dolor sit amet.";
    $this->drupalGet("node/add/$content_type");
    sleep(1);
    $this->assertTrue($driver->isVisible('//*[@data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]'), 'Paragraphs Paste area should be visible.');

    $this->simulatePasteEvent('[data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]', $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals(sprintf($expected, $text), $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
  }

  /**
   * Test multiline text with video functionality.
   *
   * @dataProvider providerTestPaste
   */
  public function testMultilineTextPaste($experimental_mode, $expected) {
    $content_type = 'article';
    $this->setPasteMethod($experimental_mode);

    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $this->loginAsAdmin();

    // Check that paste functionality is working with default config.
    $text = 'Spicy jalapeno bacon ipsum dolor amet short ribs ribeye chislic, turkey shank chuck cupim bacon bresaola.\n\nhttps://www.youtube.com/watch?v=3pX4iPEPA9A\n\nPicanha porchetta cupim, salami jerky alcatra doner strip steak pork loin short loin pork belly tail ham hock cow shoulder.';
    $this->drupalGet("node/add/$content_type");
    $this->assertTrue($driver->isVisible('//*[@data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]'), 'Paragraphs Paste area should be visible.');

    $this->simulatePasteEvent('[data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]', $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');

    $this->assertEquals(sprintf($expected, "Spicy jalapeno bacon ipsum dolor amet short ribs ribeye chislic, turkey shank chuck cupim bacon bresaola."), $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
    $this->assertEquals("Drupal Rap - Monster (remix) feat. A.Hughes and D.Stagg (1)", $page->find('xpath', '//input[@data-drupal-selector="edit-field-paragraphs-1-subform-field-video-0-target-id"]')->getValue(), 'Video should be connected to the paragraph subform.');
    $this->assertEquals(sprintf($expected, "Picanha porchetta cupim, salami jerky alcatra doner strip steak pork loin short loin pork belly tail ham hock cow shoulder."), $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-2-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
  }

  /**
   * Verify that the paste area stays after a first paste.
   *
   * @dataProvider providerTestPaste
   */
  public function testPastingTwice($experimental_mode, $expected) {
    $this->testPaste($experimental_mode, $expected);

    $session = $this->getSession();
    $page = $session->getPage();

    $text = "Bacon ipsum dolor amet cow picanha andouille strip steak tongue..";
    $this->simulatePasteEvent('[data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]', $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-1-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals(sprintf($expected, $text), $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-1-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
  }

  /**
   * Test paste functionality with two paste areas in the form.
   *
   * @dataProvider providerTestPaste
   */
  public function testPastingInTwoAreas($experimental_mode, $expected) {
    $content_type = 'article';
    $this->setPasteMethod($experimental_mode);

    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $field_name = 'field_second_paragraphs';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => ['target_type' => 'paragraph'],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $content_type,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = EntityFormDisplay::load("node.$content_type.default");
    $display->setComponent($field_name, $display->getComponent('field_paragraphs'));
    $display->save();

    $this->loginAsAdmin();

    // Check that paste functionality is working with default config.
    $this->drupalGet("node/add/$content_type");
    $this->assertTrue($driver->isVisible("//*[@data-paragraphs-paste-target='edit-field-paragraphs-paragraphs-paste-paste-action']"), 'Paragraphs Paste area should be visible.');
    $this->assertTrue($driver->isVisible("//*[@data-paragraphs-paste-target='edit-field-second-paragraphs-paragraphs-paste-paste-action']"), 'Second Paragraphs Paste area should be visible.');

    $text = "Lorem ipsum dolor sit amet.";
    $this->simulatePasteEvent('[data-paragraphs-paste-target="edit-field-paragraphs-paragraphs-paste-paste-action"]', $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals(sprintf($expected, $text), $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');

    $text = "Bacon ipsum dolor amet cow picanha andouille strip steak tongue..";
    $this->simulatePasteEvent('[data-paragraphs-paste-target="edit-field-second-paragraphs-paragraphs-paste-paste-action"]', $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-second-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in second paragraph form should be present.');
    $this->assertEquals(sprintf($expected, $text), $page->find('xpath', '//textarea[@data-drupal-selector="edit-field-second-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into the second paragraph subform.');
  }

  /**
   * Data provider for testPaste().
   *
   * @return array
   *   The data.
   */
  public function providerTestPaste() {
    return [
      'HTML' => [TRUE, '<p>%s</p>'],
      'Plain' => [FALSE, '%s'],
    ];
  }

}
