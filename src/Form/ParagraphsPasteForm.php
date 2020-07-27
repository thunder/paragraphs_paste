<?php

namespace Drupal\paragraphs_paste\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\paragraphs_paste\ParagraphsPastePluginBase;
use Drupal\paragraphs_paste\ParagraphsPastePluginManager;
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
   * The ParagraphsPaste plugin manager.
   *
   * @var \Drupal\paragraphs_paste\ParagraphsPastePluginManager
   */
  protected $pluginManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\paragraphs_paste\ParagraphsPastePluginManager $pluginManager
   *   The ParagraphsPaste plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ParagraphsPastePluginManager $pluginManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.paragraphs_paste.plugin'),
    );
  }

  /**
   * Alter the entity form to add access unpublished elements.
   */
  public function formAlter(&$elements, FormStateInterface $form_state, array $context) {

    $settings = $context['widget']->getThirdPartySettings('paragraphs_paste');
    if (empty($settings['enabled']) || $form_state->isProgrammed()) {
      return;
    }
    // Construct wrapper id.
    $fieldIdPrefix = implode('-', array_merge($context['form']['#parents'], [$elements['#field_name']]));
    $fieldWrapperId = Html::getId($fieldIdPrefix . '-add-more-wrapper');

    $elements['paragraphs_paste']['#attributes']['data-paragraphs-paste'] = 'enabled';

    if ($settings['experimental']) {
      $elements['paragraphs_paste']['#attached']['library'][] = 'paragraphs_paste/html';
    }
    else {
      $elements['paragraphs_paste']['#attached']['library'][] = 'paragraphs_paste/plain';
    }

    // Move children to table header and remove $elements['paragraphs_paste'],
    // see paragraphs_preprocess_field_multiple_value_form().
    $elements['paragraphs_paste']['#paragraphs_paste'] = TRUE;

    $elements['paragraphs_paste']['paste'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    $elements['paragraphs_paste']['paste']['content'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    if ($settings['experimental']) {
      $elements['paragraphs_paste']['paste']['content']['#type'] = 'text_format';
    }

    $elements['paragraphs_paste']['paste_action'] = [
      '#type' => 'submit',
      '#name' => $fieldIdPrefix . '_paste_action',
      '#value' => $this->t('Paste'),
      '#submit' => [[get_class($this), 'pasteSubmit']],
      '#attributes' => [
        'class' => ['visually-hidden'],
        'data-paragraphs-paste' => 'enabled',
      ],
      '#ajax' => [
        'callback' => [ParagraphsWidget::class, 'addMoreAjax'],
        'wrapper' => $fieldWrapperId,
      ],
      '#limit_validation_errors' => [['paragraphs_paste']],
    ];
  }

  /**
   * Submit callback.
   */
  public static function pasteSubmit(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state);
    /** @var \Drupal\Core\Entity\ContentEntityForm $form_object */
    $form_object = $form_state->getFormObject();
    $host = $form_object->getEntity();

    $settings = $form_object->getFormDisplay($form_state)
      ->getComponent($submit['field_name'])['third_party_settings']['paragraphs_paste'];

    $pasted_data = NestedArray::getValue(
      $form_state->getUserInput(),
      array_merge(array_slice($submit['button']['#parents'], 0, -1), ['paste', 'content'])
    );

    if ($settings['experimental']) {
      // Get value from textarea.
      $pasted_data = $pasted_data['value'];
    }

    // Reset value.
    NestedArray::setValue(
      $form_state->getUserInput(),
      array_merge(array_slice($submit['button']['#parents'], 0, -1), ['paste', 'content']),
      ''
    );

    // Split by RegEx pattern.
    $pattern = self::buildRegExPattern($settings);
    $data = preg_split($pattern, $pasted_data, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $items = self::traverseData($data, $settings['property_path_mapping']);

    $submit['widget_state']['real_items_count'] = $submit['widget_state']['real_items_count'] ?? 0;
    $submit['widget_state']['items_count'] = $submit['widget_state']['items_count'] ?? 0;
    foreach ($items as $item) {
      if ($item->plugin instanceof ParagraphsPastePluginBase) {
        $paragraph_entity = $item->plugin->createParagraphEntity($item->value);
        /* @var \Drupal\paragraphs\Entity\Paragraph $paragraph_entity */
        $paragraph_entity->setParentEntity($host, $submit['field_name']);
        $submit['widget_state']['paragraphs'][] = [
          'entity' => $paragraph_entity,
          'display' => 'default',
          'mode' => 'edit',
        ];
        $submit['widget_state']['real_items_count']++;
        $submit['widget_state']['items_count']++;
      }
    }

    ParagraphsWidget::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);
    $form_state->setRebuild();
  }

  /**
   * Traverse pasted data.
   *
   * @param array $data
   *   Pasted data.
   * @param array $property_path_mapping
   *   Property path mapping.
   *
   * @return array
   *   Enriched data.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public static function traverseData(array $data, array $property_path_mapping) {
    /** @var \Drupal\paragraphs_paste\ParagraphsPastePluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.paragraphs_paste.plugin');
    $results = [];

    // Enrich pasted data with plugins.
    foreach ($data as $value) {
      if ($plugins = $plugin_manager->getPluginsFromInput($value)) {

        // Filter out plugins without property path.
        $plugins = array_filter($plugins, function ($plugin) use ($property_path_mapping) {
          return !empty($property_path_mapping[$plugin['id']]);
        });

        // Sort definitions / candidates by weight.
        uasort($plugins, [SortArray::class, 'sortByWeightElement']);

        $plugin_id = end($plugins)['id'];
        $plugin = $plugin_manager->createInstance($plugin_id, ['property_path' => $property_path_mapping[$plugin_id]]);
        $results[] = (object) ['plugin' => $plugin, 'value' => $value];
      }
    }

    return $results;
  }

  /**
   * Get 3rd party setting form for paragraphs paste.
   *
   * @param \Drupal\Core\Field\WidgetInterface $plugin
   *   Widget plugin.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   Returns 3rd party form elements.
   */
  public static function getThirdPartyForm(WidgetInterface $plugin, $field_name) {
    $elements = [];

    $elements['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Copy & Paste area'),
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_paste', 'enabled'),
    ];

    $elements['property_path_mapping'] = [
      '#type' => 'fieldset',
      '#title' => t('Copy & Paste mapping'),
      '#states' => ['visible' => [":input[name=\"fields[$field_name][settings_edit_form][third_party_settings][paragraphs_paste][enabled]\"]" => ['checked' => TRUE]]],
      '#description' => t('Specify a property path in the pattern of {entity_type}.{bundle}.{field_name} or {entity_type}.{bundle}.{entity_reference_field_name}:{referenced_entity_bundle}.{field_name} (Use arrow keys to navigate available options)'),
    ];

    /** @var \Drupal\paragraphs_paste\ParagraphsPastePluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.paragraphs_paste.plugin');
    foreach ($plugin_manager->getDefinitions() as $definition) {
      $elements['property_path_mapping'][$definition['id']] = [
        '#type' => 'textfield',
        '#title' => $definition['label'],
        '#autocomplete_route_name' => 'paragraphs_paste.autocomplete.property_path',
        '#autocomplete_route_parameters' => ['allowed_field_types' => $definition['allowed_field_types']],
        '#default_value' => $plugin->getThirdPartySetting('paragraphs_paste', 'property_path_mapping')[$definition['id']],
      ];
    }

    $elements['experimental'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable HTML processing (experimental).'),
      '#states' => ['visible' => [":input[name=\"fields[$field_name][settings_edit_form][third_party_settings][paragraphs_paste][enabled]\"]" => ['checked' => TRUE]]],
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_paste', 'experimental'),
    ];

    $elements['split_method'] = [
      '#type' => 'checkboxes',
      '#title' => t('Split methods'),
      '#description' => t('Define when new paragraphs should be created.'),
      '#required' => TRUE,
      '#options' => [
        'double_new_line' => t('By text double newline'),
        'regex' => t('By RegEx'),
        'url' => t('By URL'),
      ],
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_paste', 'split_method', ['double_new_line']),
      '#states' => ['visible' => [":input[name=\"fields[$field_name][settings_edit_form][third_party_settings][paragraphs_paste][enabled]\"]" => ['checked' => TRUE]]],
    ];

    $elements['split_method_regex'] = [
      '#type' => 'textfield',
      '#title' => t('By RegEx'),
      '#default_value' => $plugin->getThirdPartySetting('paragraphs_paste', 'split_method_regex'),
      '#states' => [
        'visible' => [":input[name=\"fields[$field_name][settings_edit_form][third_party_settings][paragraphs_paste][split_method][regex]\"]" => ['checked' => TRUE]],
        'required' => [":input[name=\"fields[$field_name][settings_edit_form][third_party_settings][paragraphs_paste][split_method][regex]\"]" => ['checked' => TRUE]],
      ],
      '#element_validate' => [[__CLASS__, 'validateRegEx']],
    ];

    return $elements;
  }

  /**
   * Validates the regex field element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateRegEx(array &$element, FormStateInterface $form_state, array &$complete_form) {

    $split_method_parents = $element['#parents'];
    array_pop($split_method_parents);
    $split_method_parents[] = 'split_method';

    $split_methods = $form_state->getValue($split_method_parents);
    $regex = $form_state->getValue($element['#parents']);
    if (!empty($split_methods['regex']) && (empty($regex) || preg_match("/$regex/", NULL) === FALSE)) {
      $form_state->setError($element, t('A RegEx needs to be defined or is invalid.'));
    }
  }

  /**
   * Build regex pattern based on config settings.
   *
   * @param array $settings
   *   The form settings.
   *
   * @return string
   *   The regex pattern.
   */
  public static function buildRegExPattern(array $settings) {
    $parts = [];

    if ($settings['split_method']['url']) {
      $parts[] = "https?://[^\s/$.?#].[^\s]*";
    }
    if ($settings['split_method']['regex'] && !empty($settings['split_method_regex'])) {
      $parts[] = $settings['split_method_regex'];
    }
    if ($settings['split_method']['double_new_line'] || empty($parts)) {
      $parts[] = "(?:\r\n *|\n *){2,}";
    }
    return '~(' . implode('|', $parts) . ')~';
  }

}
