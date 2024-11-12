<?php

namespace Drupal\Tests\finders_facets\Kernel;

use Drupal\finders\Enum\FinderRole;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests setting up a finder with facets.
 *
 * @group finders_facets
 */
class FindersFacetTest extends KernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'views',
    'viewsreference',
    'search_api',
    'finders',
    'entity_test',
    'facets',
    'finders_facets',
    'finders_test',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  // Disable config checking -- the schema for Finders third-party settings on
  // the entity_test_with_bundle entity type is in the test module and we don't
  // want what it installs.
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    parent::setUp();

    $this->installEntitySchema('search_api_task');

    $this->installConfig('finders_test');
    $this->installEntitySchema('entity_test_with_bundle');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests a finder with facets.
   */
  public function testFinderWithFacets() {
    // Create a channel bundle.
    $channel_bundle = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_channel_bundle',
      'status' => TRUE,
    ]);
    $channel_bundle->setThirdPartySetting(
      'finders',
      'finder_type',
      'test'
    );
    $channel_bundle->setThirdPartySetting(
      'finders',
      'finder_role',
      FinderRole::Channel->value,
    );
    $channel_bundle->setThirdPartySetting(
      'finders_facets',
      'facets',
      TRUE
    );
    $channel_bundle->save();

    // Create an entry bundle.
    $entry_bundle = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_entry_bundle_one',
      'status' => TRUE,
    ]);
    $entry_bundle->save();
    $entry_bundle->setThirdPartySetting(
      'finders',
      'finder_type',
      'test'
    );
    $entry_bundle->setThirdPartySetting(
      'finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle->save();
  }

}
