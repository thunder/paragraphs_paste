<?php

namespace Drupal\paragraphs_paste\Form;

use Drupal\Component\Utility\Html;
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
  public function formAlter(&$elements, FormStateInterface $form_state, array $context) {

    if (!$form_state->getFormObject() instanceof EntityForm) {
      return;
    }

    /** @var \Drupal\Core\Entity\Entity $entity */
    // $entity = $form_state->getFormObject()->getEntity();
    $fieldWrapperId = Html::getId(implode('-', array_merge($context['form']['#parents'], [$elements['#field_name']])) . '-add-more-wrapper');
    // 0 = "field_paragraphs", 1 = "widget",
    // 2 = "add_more", 3 = "add_more_button_text".
    // $triggering_parents = $elements['#array_parents'];.
    // Check config #field_name.
    $elements['paragraphs_paste']['#attributes']['data-paragraphs-paste'] = 'enabled';
    $elements['paragraphs_paste']['#attached']['library'][] = 'paragraphs_paste/init';

    // Move children to table header and remove $elements['paragraphs_paste'],
    // see paragraphs_preprocess_field_multiple_value_form().
    $elements['paragraphs_paste']['#paragraphs_header'] = TRUE;

    $elements['paragraphs_paste']['paste_content'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    $elements['paragraphs_paste']['paste_action'] = [
      '#type' => 'submit',
      '#value' => t('Paste'),
      '#submit' => [[get_class($this), 'pasteSubmit']],
      '#attributes' => [
        'class' => ['visually-hidden'],
        'data-paragraphs-paste' => 'enabled',
      ],
      '#ajax' => [
        'callback' => [get_class($this), 'pasteAjax'],
        'wrapper' => $fieldWrapperId,
      ],
      '#limit_validation_errors' => [['paragraphs_paste']],
    ];
  }

  /**
   * Submit allback.
   */
  public static function pasteSubmit(array $form, FormStateInterface $form_state) {
    $submit['button'] = $form_state->getTriggeringElement();

    // 'field_paragraphs', 'widget', 'add_more', 'add_more_button_text',
    // $form_state->setTriggeringElement($button);
    // $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state);.
    // Mimic ParagraphsWidget::getSubmitElementInfo().
    $submit['element'] = NestedArray::getValue($form, array_slice($submit['button']['#array_parents'], 2, 0));
    $submit['field_name'] = $submit['element']['#field_name'];
    $submit['parents'] = $submit['element']['#field_parents'];

    $submit['widget_state'] = ParagraphsWidget::getWidgetState($submit['parents'], $submit['field_name'], $form_state);
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

    ParagraphsWidget::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);

    $form_state->setRebuild();

  }

  /**
   * Ajax callback..
   */
  public static function pasteAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Fake submit.
    //    $button['#array_parents'] = [
    //      'field_paragraphs', 'widget', 'add_more', 'add_more_button_text',
    //    ];.
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
