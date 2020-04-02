<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "text" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "text",
 *   label = @Translation("Text"),
 *   module = "paragraphs_paste",
 *   weight = -1,
 *   allowed_field_types = {"text", "text_long", "text_with_summary", "string",
 *   "string_long"}
 * )
 */
class Text extends ParagraphsPastePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input, array $definition) {
    // Catch all content.
    return !empty(trim($input));
  }

  /**
   * {@inheritdoc}
   */
  protected function formatInput($value, FieldDefinitionInterface $fieldDefinition) {

    if (in_array($fieldDefinition->getType(), [
      'text',
      'text_long',
      'text_with_summary',
    ])) {
      return '<p>' . implode('</p><p>', array_filter(explode("\n", $value))) . '</p>';
    }

    if ($fieldDefinition->getType() == 'string') {
      return trim(preg_replace('/\s+/', ' ', $value));
    }

    // For 'string_long' everything is fine.
    return $value;
  }

}
