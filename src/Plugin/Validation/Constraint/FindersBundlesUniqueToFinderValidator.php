<?php

namespace Drupal\finders\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\finders\Enum\FinderRole;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates the FindersBundlesUniqueToFinder constraint.
 */
class FindersBundlesUniqueToFinderValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * Creates a FindersBundlesValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityBundleInfo,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $validating_finder_id = TypeResolver::resolveDynamicTypeName("[$constraint->finderId]", $this->context->getObject());
    $validating_finder_role = FinderRole::from($constraint->finderRole);
    // Either the channel or entry entity type ID.
    $validating_finder_entity_type_id = array_key_first($items);
    $validating_finder_bundle_ids = $items[$validating_finder_entity_type_id];

    /** @var \Drupal\finders\Entity\FinderInterface $finder */
    foreach ($this->entityTypeManager->getStorage('finder')->loadMultiple() as $finder) {
      // Skip the finder being validated.
      if ($finder->id() == $validating_finder_id) {
        continue;
      }

      $finder_entity_type_id = match($validating_finder_role) {
        FinderRole::Channel => $finder->getChannelEntityTypeId(),
        FinderRole::Entries => $finder->getEntryEntityTypeId(),
      };

      // Skip if finder uses a different entity type.
      if ($finder_entity_type_id != $validating_finder_entity_type_id) {
        continue;
      }

      $finder_bundle_ids = match($validating_finder_role) {
        FinderRole::Channel => $finder->getChannelBundleIds(),
        FinderRole::Entries => $finder->getEntryBundleIds(),
      };

      if ($duplicated_bundle_ids = array_intersect($finder_bundle_ids, $validating_finder_bundle_ids)) {
        // Get the bundle info for the duplicated bundles.
        $bundle_info = $this->entityBundleInfo->getBundleInfo($finder_entity_type_id);
        $duplicated_bundle_info = array_intersect_key($bundle_info, array_fill_keys($duplicated_bundle_ids, TRUE));
        // Finally, get the bundle labels of the duplicated bundles.
        $duplicated_bundle_labels = array_column($duplicated_bundle_info, 'label');

        $this->context->addViolation($constraint->message, [
          '@finder_label' => $finder->label(),
          '@used_bundles' => implode(', ', $duplicated_bundle_labels),
          '@role' => match($validating_finder_role) {
            FinderRole::Channel => t('channels'),
            FinderRole::Entries => t('entries'),
          },
        ]);

        // If we have a violation, we can leave. There should not be common
        // bundles between existing finders.
        return;
      }
    }
  }

}
