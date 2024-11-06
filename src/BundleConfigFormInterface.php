<?php

declare(strict_types=1);

namespace Drupal\localgov_finders;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form section added to configure Finder on bundle.
 */
interface BundleConfigFormInterface {

  /**
   * Call from hook_form_alter to add plugin form.
   */
  public function alterBundleForm(&$form, FormStateInterface $form_state): void;

  /**
   * Basic form elements to add.
   */
  public function getFormElements(ConfigEntityInterface $bundle_type): array;

  /**
   * Validation handler.
   */
  public static function validate(array $form, FormStateInterface $form_state): void;

  /**
   * Submit handler.
   */
  public static function submit(array $form, FormStateInterface $form_state): void;

}
