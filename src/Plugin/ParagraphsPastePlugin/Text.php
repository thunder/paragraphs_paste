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

    $document = new \DOMDocument();
    $document->loadHTML($input);
    $xpath = new \DOMXPath($document);
    // Remove empty html tags recursively.
    while (($node_list = $xpath->query('//*[not(node())]')) && $node_list->length) {
      foreach ($node_list as $node) {
        $node->parentNode->removeChild($node);
      }
    }
    $document->formatOutput = TRUE;
    $input = $document->saveHTML();
    // Strip doctype, html and body tags..
    $input = substr($input, 119, strlen($input) - 119 - 15);

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
