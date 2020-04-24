<?php

namespace Drupal\Tests\paragraphs_paste\Kernel\Plugin\ParagraphsPastePlugin;

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
   * {@inheritdoc}
   */
  protected static $modules = [
    'paragraphs_paste',
    'entity_test',
    'field',
    'text',
  ];

  /**
   * Data provider for formatInput.
   */
  public function formatInputProvider() {
    return [
      [
        'string',
        '<p>Test string.</p><p>With <br/>
         br.</p>',
        'Test string.With br.',
      ],
      [
        'string_long',
        '<p>Test string.</p><p>With <br/>
         br.</p>',
        "Test string.With\nbr.",
      ],
      [
        'string_long',
        "A text with non&nbsp;breaking&nbsp;spaces.",
        'A text with non breaking spaces.',
      ],
      [
        'text_long',
        "<p></p><p> </p></p><p><span><span>Text with empty tags, paragraphs containing non breaking space, </span></span></p>\r\n\r\n<p>&nbsp;</p>\r\n<p><span><span>and newlines </span></span></p>\r\n<p>&nbsp;</p>",
        '<p><span><span>Text with empty tags, paragraphs containing non breaking space, </span></span></p><p><span><span>and newlines </span></span></p>',
      ],
    ];
  }

  /**
   * Test format input an a string field.
   *
   * @dataProvider formatInputProvider
   */
  public function testFormatInput($fieldType, $input, $output) {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'entity_test',
      'type' => $fieldType,
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

    $this->assertSame($output, $method->invokeArgs($text, [$input, $field]));
  }

}
