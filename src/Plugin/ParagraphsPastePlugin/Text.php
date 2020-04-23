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

    $document = Html::load($input);
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

    // Remove non-breaking spaces.
    $value = preg_replace('~\x{00a0}~siu', ' ', $value);

    if ($fieldDefinition->getType() == 'string') {
      return trim(preg_replace('/\s+/', ' ', strip_tags($value)));
    }
    if ($fieldDefinition->getType() == 'string_long') {
      $lines = array_map('trim', explode(PHP_EOL, strip_tags($value)));
      return implode(PHP_EOL, $lines);
    }

    // Remove trailing whitespace chars.
    $value = rtrim($value);

    // For 'text', 'text_long', 'text_with_summary' everything
    // is fine.
    return $value;
  }

}
