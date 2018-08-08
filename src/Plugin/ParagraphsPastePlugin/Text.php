<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\paragraphs_paste\ParagraphsPastePluginInterface;

/**
 * Defines the "text" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "text",
 *   label = @Translation("Text"),
 *   module = "paragraphs_paste",
 *   weight = -1
 * )
 */
class Text extends PluginBase implements ParagraphsPastePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build($input) {
    $target_type = 'paragraph';
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_type_manager->getDefinition($target_type);
    $bundle_key = $entity_type->getKey('bundle');

    $paragraph_entity = $entity_type_manager->getStorage($target_type)
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
