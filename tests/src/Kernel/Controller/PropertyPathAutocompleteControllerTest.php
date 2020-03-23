<?php

namespace Drupal\Tests\paragraphs_paste\Kernel\Controller;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs_paste\Controller\PropertyPathAutocompleteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the behavior plugins API.
 *
 * @group paragraphs_paste
 */
class PropertyPathAutocompleteControllerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs_paste',
    'paragraphs',
    'file',
    'field',

  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ParagraphsType::create([
      'label' => 'Text',
      'id' => 'text',
    ])->save();

    // Add a test to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '1',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'text',
    ]);
    $field->save();

    ParagraphsType::create([
      'label' => 'Image',
      'id' => 'image',
    ])->save();

    ParagraphsType::create([
      'label' => 'Video',
      'id' => 'video',
    ])->save();

  }

  /**
   * Data provider for handleAutocomplete.
   */
  public function handleAutocompleteProvider() {
    return [
      [
        'para',
        '[{"value":"paragraph","label":"paragraph... (Paragraph)","keyword":"paragraph Paragraph"}]',
      ],
      [
        'paragraph.',
        '[{"value":"paragraph.image","label":"paragraph.image... (Image)","keyword":"image Image"},{"value":"paragraph.text","label":"paragraph.text... (Text)","keyword":"text Text"},{"value":"paragraph.video","label":"paragraph.video... (Video)","keyword":"video Video"}]',
      ],
      [
        'paragraph.te',
        '[{"value":"paragraph.text","label":"paragraph.text... (Text)","keyword":"text Text"}]',
      ],
      [
        'paragraph.text.',
        '[{"value":"paragraph.text.field_text","label":"paragraph.text.field_text (field_text)","keyword":"field_text field_text"},{"value":"paragraph.text.parent_field_name","label":"paragraph.text.parent_field_name (Parent field name)","keyword":"parent_field_name Parent field name"},{"value":"paragraph.text.parent_id","label":"paragraph.text.parent_id (Parent ID)","keyword":"parent_id Parent ID"},{"value":"paragraph.text.parent_type","label":"paragraph.text.parent_type (Parent type)","keyword":"parent_type Parent type"}]',
      ],

    ];
  }

  /**
   * Test the handle autocomplete method.
   *
   * @dataProvider handleAutocompleteProvider
   */
  public function testHandleAutocomplete($input, $result) {

    $controller = PropertyPathAutocompleteController::create($this->container);
    $response = $controller->handleAutocomplete(new Request(['q' => $input]));
    $this->assertSame($result, $response->getContent());
  }

}
