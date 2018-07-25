<?php

namespace Drupal\paragraphs_paste\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter the entity form to add access unpublished elements.
 */
class ParagraphsPasteForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->config = $configFactory->get('paragraphs_paste.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Alter the entity form to add access unpublished elements.
   */
  public function formAlter(&$form, FormStateInterface $form_state) {

    if (!$form_state->getFormObject() instanceof EntityForm) {
      return;
    }

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $field_name = 'field_paragraphs';

    if ($entity->bundle() === 'article' && isset($form[$field_name])) {
      // Enable for field_paragraphs.
      $form[$field_name]['#attributes']['data-paragraphs-paste'] = 'enabled';
      $form['paragraphs_paste'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'visually-hidden',
          'id' => 'edit-paragraphs-paste',
        ],
        '#attached' => [
          'library' => [
            'paragraphs_paste/init',
          ],
        ],
      ];

      $form['paragraphs_paste']['content'] = [
        '#type' => 'hidden',
      ];

      $form['paragraphs_paste']['button'] = [
        '#type' => 'submit',
        '#value' => t('Paste'),
        '#submit' => [[get_class($this), 'pasteSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'pasteAjax'],
          'wrapper' => $form[$field_name]['widget']['add_more']['add_more_button_text']['#ajax']['wrapper'],
        ],
        '#limit_validation_errors' => [['paragraphs_paste']],
        // '#limit_validation_errors' => [array_merge($this->fieldParents, [$this->fieldDefinition->getName(), 'add_more'])],.
      ];

    }
  }

  /**
   * Submit allback.
   */
  public static function pasteSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Fake submit.
    $button['#array_parents'] = [
      'field_paragraphs', 'widget', 'add_more', 'add_more_button_text',
    ];
    $form_state->setTriggeringElement($button);

    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state);

    // $submit['widget_state']['selected_bundle'] = 'text';.
    $submit['widget_state']['items_count']++;
    $submit['widget_state']['real_items_count']++;

    $host = $form_state->getFormObject()->getEntity();
    $field_name = 'field_paragraphs';
    $target_type = 'paragraph';

    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_type_manager->getDefinition($target_type);
    $bundle_key = $entity_type->getKey('bundle');

    $paragraphs_entity = $entity_type_manager->getStorage($target_type)->create([
      $bundle_key => 'text',
    ]);
    $paragraphs_entity->setParentEntity($host, $field_name);
    $submit['widget_state']['paragraphs'][] = [
      'entity' => $paragraphs_entity,
    // $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));.
      'display' => 'default',
      'mode' => 'edit',
    ];

    $field_state['items_count'];
    ParagraphsWidget::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);

    $form_state->setRebuild();

  }

  /**
   * Ajax callback..
   */
  public static function pasteAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Fake submit.
    $button['#array_parents'] = [
      'field_paragraphs', 'widget', 'add_more', 'add_more_button_text',
    ];
    $form_state->setTriggeringElement($button);

    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state);
    $element = $submit['element'];

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $submit['element']['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

}
