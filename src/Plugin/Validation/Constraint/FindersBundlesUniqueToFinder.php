<?php

namespace Drupal\finders\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates finder entries and channels as being unique to their finder.
 *
 * In other words, Finder A can't use the same entry bundles or the same channel
 * bundles as Finder B.
 */
#[Constraint(
  id: 'FindersBundlesUniqueToFinder',
  label: new TranslatableMarkup('Finders bundles'),
  type: FALSE,
)]
class FindersBundlesUniqueToFinder extends SymfonyConstraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public $message = "The @finder_label finder already uses the @used_bundles bundles as @role.";

  /**
   * The finder role for which bundles are being validated.
   *
   * @var string
   */
  public string $finderRole;

  /**
   * The ID of the finder being validated.
   *
   * This can contain variable values (e.g., `%parent`) that will be replaced.
   *
   * @see \Drupal\Core\Config\Schema\TypeResolver::replaceVariable()
   *
   * @var string
   */
  public string $finderId;

}
