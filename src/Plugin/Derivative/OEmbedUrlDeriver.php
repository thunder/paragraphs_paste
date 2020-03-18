<?php

namespace Drupal\paragraphs_paste\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Derives paragraph paste plugins handling OEmbed urls.
 */
class OEmbedUrlDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'video' => [
        'id' => 'oembed_url:video',
        'label' => $this->t('Remote video'),
        'description' => $this->t('Youtube or Vimeo URLs.'),
        'providers' => ['YouTube', 'Vimeo'],
        'property_path' => 'paragraph.video.field_video:remote_video.field_media_oembed_video',
      ],
      'twitter' => [
        'id' => 'oembed_url:twitter',
        'label' => $this->t('Twitter'),
        'description' => $this->t('Twitter URLs.'),
        'providers' => ['Twitter'],
        'property_path' => 'paragraph.twitter.field_media:twitter.field_url',
      ],
      'instagram' => [
        'id' => 'oembed_url:instagram',
        'label' => $this->t('Instagram'),
        'description' => $this->t('Instagram URLs.'),
        'providers' => ['Instagram'],
        'media_bundle' => 'instagram',
        'property_path' => 'paragraph.instagram.field_media:instagram.field_url',
      ],
    ];

    foreach ($this->derivatives as $name => $plugin_definition) {
      $this->derivatives[$name] = $plugin_definition + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
