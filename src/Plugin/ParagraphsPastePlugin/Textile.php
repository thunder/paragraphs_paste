<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\paragraphs_paste\ParagraphsPastePluginBase;
use Netcarver\Textile\Parser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "custom_text" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "textile",
 *   label = @Translation("Textile text"),
 *   module = "paragraphs_paste",
 *   weight = 0,
 *   allowed_field_types = {"text", "text_long", "text_with_summary", "string",
 *   "string_long"}
 * )
 */
class Textile extends ParagraphsPastePluginBase {

  /**
   * The textile Parser.
   *
   * @var \Netcarver\Textile\Parser
   */
  protected $textileParser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setTextileParser(new Parser());

    return $instance;
  }

  /**
   * Sets the parser for this plugin.
   *
   * @param \Netcarver\Textile\Parser $parser
   *   The textile parser.
   */
  protected function setTextileParser(Parser $parser) {
    $this->textileParser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatInput($value, FieldDefinitionInterface $fieldDefinition) {
    return $this->parseTextileInput($value);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input, array $definition) {
    return !empty(trim($input)) && class_exists('\Netcarver\Textile\Parser');
  }

  /**
   * Use textile to parse input.
   */
  public function parseTextileInput($input) {
    $input = preg_replace('~\r?\n~', "\n", $input);
    return $this->textileParser->setBlockTags(TRUE)->setRestricted(TRUE)->parse($input);
  }

}
