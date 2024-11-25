<?php

namespace Drupal\Tests\finders_facets\Kernel;

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
    'block',
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

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    parent::setUp();

    $this->installEntitySchema('search_api_task');

    $this->installConfig('finders');
    $this->installConfig('finders_test');
    $this->installEntitySchema('entity_test_with_bundle');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
  }

  /**
   * Tests a finder with facets.
   */
  public function testFinderWithFacets() {
    // Create bundles that will be channels and entries.
    $channel_bundle = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_channel_bundle',
      'status' => TRUE,
    ]);
    $channel_bundle->save();

    $entry_bundle_one = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_entry_bundle_one',
      'status' => TRUE,
    ]);
    $entry_bundle_one->save();
    $entry_bundle_two = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_entry_bundle_two',
      'status' => TRUE,
    ]);
    $entry_bundle_two->save();

    // Create a channel entity.
    $channel = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'name' => 'test channel',
      'type' => 'test_channel_bundle',
    ]);
    $channel->save();

    $finder = $this->entityTypeManager->getStorage('finder')->create([
      'id' => 'test',
      'label' => 'Test',
      'type' => 'test',
      'channels' => [
        'entity_test_with_bundle' => [
          'test_channel_bundle',
        ],
      ],
      'entries' => [
        'entity_test_with_bundle' => [
          'test_entry_bundle_one',
        ],
      ],
    ]);
    // Save the finder now, so we test that updating an existing finder to have
    // facets works properly.
    $finder->save();

    $finder->setThirdPartySetting(
      'finders_facets',
      'facets',
      TRUE
    );
    $finder->save();

    // The channel bundle has the facet types field on it.
    $channel_fields = $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'test_channel_bundle');
    $this->assertArrayHasKey('finders_facets_enable', $channel_fields);

    // The entry bundle has the facet selection field on it.
    $channel_fields = $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'test_entry_bundle_one');
    $this->assertArrayHasKey('finders_facets_select', $channel_fields);

    $search_index = $this->entityTypeManager->getStorage('search_api_index')->load('finders_index_default');
    $this->assertNotEmpty($search_index);
    $fields = $search_index->getFields();

    // The facets field has been added to the search index.
    $this->assertArrayHasKey('finders_facets_filter', $fields);

    // The facet has been created.
    $facet = $this->entityTypeManager->getStorage('facets_facet')->load('finders_index_default_finders_channel_view');
    $this->assertNotEmpty($facet);

    $block = $this->entityTypeManager->getStorage('block')->load('finders_facets_finders_index_default_finders_channel_view');
    $this->assertNotEmpty($block);
  }

}
