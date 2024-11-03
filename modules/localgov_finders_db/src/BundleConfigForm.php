<?php

declare(strict_types=1);

namespace Drupal\localgov_finders_db;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\localgov_finders\BundleConfigForm as DecoratedBundleConfigForm;
use Drupal\localgov_finders\BundleConfigFormInterface;

/**
 * Decorates to extend configure Finder on bundle form.
 *
 * Add the option to automatically add a db backend to new configurations.
 */
class BundleConfigForm implements BundleConfigFormInterface {

  /**
   * Creates a BundleConfigForm decorator instance.
   *
   * @param BundleConfigFormInterface $decorated
   *   The decorated inner class.
   */
  public function __construct(protected DecoratedBundleConfigForm $decorated) {
  }

  /**
   * Call from hook_form_alter to add plugin form.
   */
  public function alterBundleForm(&$form, FormStateInterface $form_state): void {
    $bundle_type = $form_state->getFormObject()->getEntity();
    assert($bundle_type instanceof ConfigEntityInterface);
    $form += $this->getFormElements($bundle_type);
    foreach (Element::children($form['actions']) as $action) {
      $form['actions'][$action]['#validate'][] = $this->validate(...);
      $form['actions'][$action]['#submit'][] = $this->submit(...);
    }
  }

  /**
   * Basic form elements to add.
   */
  public function getFormElements(ConfigEntityInterface $bundle_type): array {
    $form = $this->decorated->getFormElements($bundle_type);
    // This only works if all the backends play ball and want to add the same
    // way. That said, there will only be a few. And writing a whole other plugin
    // system to then identify activate etc. feels like overkill.
    // At the same time. Probably want a permission for accessing this as well.
    // So the plugin option explicitly on the form makes more sense.
    //
    // What about add the facets section? Another plugin, or a decorator?

    if (!isset($form['localgov_finders']['index_backend'])) {
      $form['localgov_finders']['index_backend'] = [
        '#type' => 'radios',
        '#title' => t('Backend'),
        '#options' => [
          '' => t('None'),
          'db' => t('Database'),
        ],
        '#empty_value' => '',
        '#default_value' => $backend,
      ];
    }
  }

  /**
   * Validation handler.
   */
  public function validate(array $form, FormStateInterface $form_state): void {
    $this->decorated->validate($form, $form_state);
  }

  /**
   * Submit handler.
   */
  public function submit(array $form, FormStateInterface $form_state): void {
    $this->decorated->submit($form, $form_state);
  }

}
