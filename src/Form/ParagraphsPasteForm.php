<?php

namespace Drupal\paragraphs_paste\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\paragraphs_paste\ParagraphsPastePluginBase;
use Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin\OEmbedUrl;
use Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin\Text;
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
   * @param \Drupal\paragraphs_paste\ParagraphsPastePluginManager $pluginManager
   *   The ParagraphsPaste plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ParagraphsPastePluginManager $pluginManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->pluginManager = $pluginManager;
    $this->config = $configFactory->get('paragraphs_paste.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.paragraphs_paste.plugin'),
      $container->get('config.factory')
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
    $fieldWrapperId = Html::getId(implode('-', array_merge($context['form']['#parents'], [$elements['#field_name']])) . '-add-more-wrapper');

    $elements['paragraphs_paste']['#attributes']['data-paragraphs-paste'] = 'enabled';
    $elements['paragraphs_paste']['#attached']['library'][] = 'paragraphs_paste/init';

    // Move children to table header and remove $elements['paragraphs_paste'],
    // see paragraphs_preprocess_field_multiple_value_form().
    $elements['paragraphs_paste']['#paragraphs_paste'] = TRUE;

    $elements['paragraphs_paste']['paste_content'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    $elements['paragraphs_paste']['paste_action'] = [
      '#type' => 'submit',
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

    $pasted_data = json_decode(
      NestedArray::getValue(
        $form_state->getUserInput(),
        array_merge(array_slice($submit['button']['#parents'], 0, -1), ['paste_content'])
      )
    );

    $settings = $form_object->getFormDisplay($form_state)
      ->getComponent($submit['field_name'])['third_party_settings']['paragraphs_paste'];

    $reg_ex = [];
    if ($settings['split_method']['oEmbed']) {
      $reg_ex[] = "https?://[^\s/$.?#].[^\s]*";
    }
    if ($settings['split_method']['regex'] && !empty($settings['split_method_regex'])) {
      $reg_ex[] = $settings['split_method_regex'];
    }
    if ($settings['split_method']['double_new_line'] || empty($reg_ex)) {
      $reg_ex[] = "[\r\n]+\s?[\r\n]+";
    }

    // Split by RegEx.
    $data = preg_split('~(' . implode('|', $reg_ex) . ')~', $pasted_data, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    $items = self::traverseData($data, $settings['mapping']);

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

    foreach ($results as $key => $result) {
      // Merge text items following a split on an oembed url.
      if ($result->plugin instanceof OEmbedUrl) {

        $iterator = $key + 1;
        while ($iterator < count($results)) {
          $current = $iterator;
          $next = $iterator + 1;

          if ($results[$current]->plugin instanceof Text && $results[$next]->plugin instanceof Text) {
            $results[$next]->value = $results[$current]->value . $results[$next]->value;
            $results[$current]->plugin = FALSE;
          }
          $iterator++;
        }
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

    $elements['mapping'] = [
      '#type' => 'fieldset',
      '#title' => t('Copy & Paste mapping'),
      '#states' => ['visible' => [":input[name=\"fields[$field_name][settings_edit_form][third_party_settings][paragraphs_paste][enabled]\"]" => ['checked' => TRUE]]],
      '#description' => t('Specify a property path in the pattern of {entity_type}.{bundle}.{field_name} or {entity_type}.{bundle}.{entity_reference_field_name}:{referenced_entity_bundle}.{field_name} (Use arrow keys to navigate available options)'),
    ];

    /** @var \Drupal\paragraphs_paste\ParagraphsPastePluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.paragraphs_paste.plugin');
    foreach ($plugin_manager->getDefinitions() as $definition) {
      $elements['mapping'][$definition['id']] = [
        '#type' => 'textfield',
        '#title' => $definition['label'],
        '#autocomplete_route_name' => 'paragraphs_paste.autocomplete.property_path',
        '#default_value' => $plugin->getThirdPartySetting('paragraphs_paste', 'mapping')[$definition['id']],
      ];
    }

    $elements['split_method'] = [
      '#type' => 'checkboxes',
      '#title' => t('Split methods'),
      '#description' => t('Define when new paragraphs should be created.'),
      '#required' => TRUE,
      '#options' => [
        'double_new_line' => t('By text double newline'),
        'oEmbed' => t('By oEmbed URL'),
        'regex' => t('By RegEx'),
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

    ];

    return $elements;
  }

}
