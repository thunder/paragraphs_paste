<?php

namespace Drupal\Tests\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior plugins API.
 *
 * @group paragraphs_paste
 */
class TextTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs_paste',
    'entity_test',
    'field',
    'text',
  ];

  /**
   * Test the handle autocomplete method.
   */
  public function testFormatInput() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'entity_test',
      'type' => 'string',
      'cardinality' => '1',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field->save();

    /** @var \Drupal\paragraphs_paste\ParagraphsPastePluginManager $pluginManager */
    $pluginManager = \Drupal::service('plugin.manager.paragraphs_paste.plugin');

    /** @var \Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin\Text $text */
    $text = $pluginManager->createInstance('text');

    $class = new \ReflectionClass('Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin\Text');
    $method = $class->getMethod('formatInput');
    $method->setAccessible(TRUE);

    $this->assertSame('Test string.With br.', $method->invokeArgs($text, ['<p>Test string.</p><p>With <br/> br.</p>', $field]));
  }

}
