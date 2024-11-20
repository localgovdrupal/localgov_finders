<?php

namespace Drupal\finders\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders\FinderTypeManager;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field formatter for channel views.
 *
 * @todo Implement isApplicable().
 */
#[FieldFormatter(
  id: 'finders_channel_view',
  label: new TranslatableMarkup("Finder channel"),
  field_types: [
    'viewsreference',
  ],
)]
class FindersChannelView extends FormatterBase {

  /**
   * The finder type manager.
   *
   * @var \Drupal\finders\FinderTypeManager
   */
  protected $finderTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.finders_finder_type'),
    );
  }

  /**
   * Creates a FindersChannelView instance.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The settings.
   * @param string $label
   *   The label.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   The third party settings.
   * @param \Drupal\finders\FinderTypeManager $finder_type_manager
   *   The finder type manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    FinderTypeManager $finder_type_manager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->finderTypeManager = $finder_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['show_filters'] = FALSE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['show_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show view exposed filters'),
      '#default_value' => $this->getSetting('show_filters'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary[] = $settings['show_filters']
      ? $this->t('Exposed filters: shown')
      : $this->t('Exposed filters: not shown');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $show_filters = $settings['show_filters'];

    $host_entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      $view_id = $item->getValue()['target_id'];
      $display_id = $item->getValue()['display_id'];
      $view = Views::getView($view_id);

      if (!$view || !$view->access($display_id)) {
        return [];
      }

      // Our fields have cardinality 1. We don't support multi-valued fields
      // because YAGNI.
      // @todo: Use a lazy builder. See the viewsreference module's formatters
      // for an example.
      $render = [
        '#type' => 'view',
        '#name' => $view_id,
        '#display_id' => $display_id,
        '#arguments' => [$host_entity->id()],
      ];

      if (!$show_filters) {
        $render['#post_render'] = [
          [static::class, 'removeExposedFilter'],
        ];
      }

      return $render;
    }

    return [];
  }

  /**
   * Post render callback.
   *
   * @see ::getViewEmbed()
   */
  #[TrustedCallback]
  public static function removeExposedFilter(Markup $markup, array $render) {
    // Sure there must be a better way in the pre_render to stop it adding the
    // form, while accepting the parameters. But this does the same later.
    return $markup::create(preg_replace('|<form.*?class="[^"]*views-exposed-form.*?>.*?</form>|s', '', $markup, 1));
  }

}
