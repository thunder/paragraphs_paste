<?php

namespace Drupal\paragraphs_paste;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base Paragraphs paste plugin implementation.
 *
 * @see \Drupal\paragraphs_paste\Annotation\ParagraphsPastePlugin
 * @see \Drupal\paragraphs_paste\ParagraphsPastePluginBase
 * @see \Drupal\paragraphs_paste\ParagraphsPastePluginInterface
 * @see \Drupal\paragraphs_paste\ParagraphsPastePluginManager
 * @see plugin_api
 */
abstract class ParagraphsPastePluginBase extends PluginBase implements ContainerFactoryPluginInterface, ParagraphsPastePluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity selection manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $entityReferenceSelectionManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a ParagraphsPastePluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selectionPluginManager
   *   The entity selection manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, SelectionPluginManagerInterface $selectionPluginManager, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityReferenceSelectionManager = $selectionPluginManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build($input) {
    $property_path = explode('.', $this->configuration['property_path']);

    $target_entity_type = array_shift($property_path);
    $target_bundle = array_shift($property_path);

    $entity_type = $this->entityTypeManager->getDefinition($target_entity_type);

    $paragraph_entity = $this->entityTypeManager->getStorage($target_entity_type)
      ->create([
        $entity_type->getKey('bundle') => $target_bundle,
      ]);

    $this->setValue($property_path, $paragraph_entity, $input);

    return $paragraph_entity;
  }

  /**
   * Sets value to a property path.
   *
   * @param array $property_path
   *   Property path.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to set the value on.
   * @param mixed $value
   *   The value to set.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setValue(array $property_path, EntityInterface $entity, $value) {

    $fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

    $path = array_shift($property_path);

    list($fieldName, $bundle) = array_pad(explode(':', $path), 2, NULL);

    if (empty($fields[$fieldName])) {
      return;
    }

    $fieldConfig = $fields[$fieldName];

    // If bundle is defined, it's an ER field.
    if ($bundle) {

      $target_type = $fieldConfig->getFieldStorageDefinition()
        ->getSetting('target_type');

      /** @var \Drupal\Core\Entity\EntityInterface $newEntity */
      $newEntity = $this->entityReferenceSelectionManager->getSelectionHandler($fieldConfig)
        ->createNewEntity($target_type, $bundle, NULL, $this->currentUser->id());

      if (!empty($property_path)) {
        $this->setValue($property_path, $newEntity, $value);
      }
      $newEntity->save();
      $entity->{$fieldName}[] = $newEntity;
    }
    else {
      $entity->{$fieldName} = $value;
    }
  }

}
