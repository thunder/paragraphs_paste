<?php

namespace Drupal\Tests\paragraphs_paste\Kernel\Controller;

use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs_paste\Controller\PropertyPathAutocompleteController;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the behavior plugins API.
 *
 * @group paragraphs_paste
 */
class PropertyPathAutocompleteControllerTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs_paste_test',
    'paragraphs_paste',
    'paragraphs',
    'entity_reference_revisions',
    'file',
    'field',
    'media',
    'node',
    'system',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['paragraphs_paste_test']);

  }

  /**
   * Data provider for handleAutocomplete.
   */
  public function handleAutocompleteProvider() {
    return [
      [
        'para',
        '[{"value":"paragraph.","label":"paragraph (Paragraph)","keyword":"paragraph Paragraph"}]',
      ],
      [
        'paragraph.',
        '[{"value":"paragraph.text.","label":"paragraph.text (Text)","keyword":"text Text"},{"value":"paragraph.video.","label":"paragraph.video (Video)","keyword":"video Video"}]',
      ],
      [
        'paragraph.te',
        '[{"value":"paragraph.text.","label":"paragraph.text (Text)","keyword":"text Text"}]',
      ],
      [
        'paragraph.text.',
        '[{"value":"paragraph.text.behavior_settings","label":"paragraph.text.behavior_settings (Behavior settings)","keyword":"behavior_settings Behavior settings"},{"value":"paragraph.text.field_text","label":"paragraph.text.field_text (Text)","keyword":"field_text Text"},{"value":"paragraph.text.parent_field_name","label":"paragraph.text.parent_field_name (Parent field name)","keyword":"parent_field_name Parent field name"},{"value":"paragraph.text.parent_id","label":"paragraph.text.parent_id (Parent ID)","keyword":"parent_id Parent ID"},{"value":"paragraph.text.parent_type","label":"paragraph.text.parent_type (Parent type)","keyword":"parent_type Parent type"}]',
      ],
      [
        'paragraph.video.field_video:',
        '[{"value":"paragraph.video.field_video:remote_video.","label":"paragraph.video.field_video:remote_video (Remote video)","keyword":"remote_video Remote video"}]',
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
