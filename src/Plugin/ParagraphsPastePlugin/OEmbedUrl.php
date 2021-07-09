<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "oembed_url" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "oembed_url",
 *   label = @Translation("OEmbed Urls"),
 *   module = "paragraphs_paste",
 *   weight = 0,
 *   providers = {},
 *   deriver = "\Drupal\paragraphs_paste\Plugin\Derivative\OEmbedUrlDeriver",
 *   allowed_field_types = {"string"}
 * )
 */
class OEmbedUrl extends ParagraphsPastePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input, array $definition) {
    $input = strip_tags($input);
    /** @var \Drupal\media\OEmbed\UrlResolverInterface $resolver */
    $resolver = \Drupal::service('media.oembed.url_resolver');

    foreach ($definition['providers'] as $provider_name) {
      try {
        $provider = $resolver->getProviderByUrl($input);
        if ($provider_name == $provider->getName()) {
          return TRUE;
        }
      }
      catch (\Exception $e) {
        continue;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatInput($value, FieldDefinitionInterface $fieldDefinition) {
    return strip_tags($value);
  }

}
