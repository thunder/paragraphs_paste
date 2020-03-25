<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "text" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "text",
 *   label = @Translation("Text"),
 *   module = "paragraphs_paste",
 *   weight = -1,
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
  protected function formatInput($value) {
    return '<p>' . implode('</p><p>', array_filter(explode("\n", $value))) . '</p>';
  }

}
