<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\Component\Utility\Html;
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

    if (empty(trim($input))) {
      return FALSE;
    }

    $document = Html::load($html);
    $xpath = new \DOMXPath($document);
    // Remove empty html tags recursively.
    while (($node_list = $xpath->query('//*[not(node())]')) && $node_list->length) {
      foreach ($node_list as $node) {
        $node->parentNode->removeChild($node);
      }
    }
    $input = Html::serialize($document);
    return !empty(trim($input));
  }

  /**
   * {@inheritdoc}
   */
  protected function formatInput($value, FieldDefinitionInterface $fieldDefinition) {

    if ($fieldDefinition->getType() == 'string') {
      return trim(preg_replace('/\s+/', ' ', $value));
    }

    // Remove trailing whitespace chars.
    $value = rtrim($value);

    // For 'string_long', 'text', 'text_long', 'text_with_summary' everything
    // is fine.
    return $value;
  }

}
