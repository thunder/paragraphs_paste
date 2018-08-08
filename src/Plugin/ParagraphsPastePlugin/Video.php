<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "video" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "video",
 *   label = @Translation("Video"),
 *   module = "paragraphs_paste",
 *   weight = 0
 * )
 */
class Video extends ParagraphsPastePluginBase {

  /**
   * {@inheritdoc}
   */
  public function build($input) {
    $target_type = 'paragraph';

    $entity_type = $this->entityTypeManager->getDefinition($target_type);
    $bundle_key = $entity_type->getKey('bundle');

    $paragraph_entity = $this->entityTypeManager->getStorage($target_type)
      ->create([
        $bundle_key => 'text',
      ]);
    $paragraph_entity->set('field_text', $input);

    return $paragraph_entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    return TRUE;
  }

}
