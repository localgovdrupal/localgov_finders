<?php

namespace Drupal\Tests\finders_geo\Kernel;

use Drupal\finders_geo\Plugin\FinderType\Geo;
use Drupal\KernelTests\KernelTestBase;
use Drupal\finders\Enum\FinderRole;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;
use Drupal\search_api\Entity\Index;

/**
 * Tests the Geo finder type plugin.
 *
 * @group finders
 */
class GeoFinderPluginTest extends KernelTestBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'views',
    'node',
    'finders',
    'finders_geo',
    'search_api',
    'viewsreference',
    'geofield',
    'geo_entity',
    // @todo make a geo_entity_test
    // and then move location into a basefield!
    'geo_entity_area',
    // Oof and geo_entity has a set of config dependencies.
    'entity_browser',
    'entity_browser_entity_form'
  ];

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
    $this->installEntitySchema('node');
    $this->installEntitySchema('geo_entity');
    $this->installConfig('geo_entity');
    $this->installConfig('geo_entity_area');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests event finder type.
   */
  public function testEventFinderType() {
    // Create a channel bundle.
    $channel_bundle = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'test_channel_bundle',
      'status' => TRUE,
    ]);
    $channel_bundle->setThirdPartySetting(
      'finders',
      'finder_type',
      'geo'
    );
    $channel_bundle->setThirdPartySetting(
      'finders',
      'finder_role',
      FinderRole::Channels->value,
    );
    $channel_bundle->save();

    // Update the geo type to be an entry.
    $entry_bundle = $this->entityTypeManager->getStorage('geo_entity_type')->load('area');
    $entry_bundle->setThirdPartySetting(
      'finders',
      'finder_type',
      'geo'
    );
    $entry_bundle->setThirdPartySetting(
      'finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle->save();

    // The channel and entry bundles have finder fields defined on them.
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $channel_fields = $entity_field_manager->getFieldDefinitions('node', 'test_channel_bundle');
    $this->assertArrayHasKey(FinderTypeBase::CHANNEL_TYPES_FIELD, $channel_fields);
    $this->assertArrayHasKey(Geo::LIST_VIEW_FIELD, $channel_fields);
    $this->assertArrayHasKey(Geo::MAP_VIEW_FIELD, $channel_fields);

    $geo_fields = $entity_field_manager->getFieldDefinitions('geo_entity', 'area');
    $this->assertArrayHasKey(FinderTypeBase::CHANNEL_SELECTION_FIELD, $geo_fields);

    // A search index has been created by the creation of the channel bundle.
    $search_index = Index::load('finders_index_geo');
    $this->assertNotEmpty($search_index);

    $datasources = $search_index->getDatasources();
    $this->assertCount(1, $datasources);
    $datasource = reset($datasources);
    $this->assertEquals('geo_entity', $datasource->getDerivativeId());
    $this->assertContains('Area', $datasource->getBundles());

    $fields = $search_index->getFields();
    #$this->assertArrayHasKey('title', $fields);
    #$this->assertArrayHasKey('rendered_item', $fields);
    #$this->assertArrayHasKey('finders_title_sort', $fields);
    #$this->assertArrayHasKey('finders_channels', $fields);
    // @todo then the location field(s).
  }

}
