<?php

namespace Drupal\Tests\finders_events\Kernel;

use Drupal\finders_events\Plugin\FinderType\Events;
use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_finders\Enum\FinderRole;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeBase;
use Drupal\search_api\Entity\Index;

/**
 * Tests the events finder type plugin.
 *
 * @group finders_events
 */
class EventFinderPluginTest extends KernelTestBase {

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
    'localgov_finders',
    'search_api',
    'viewsreference',
    'date_recur',
    'computed_field',
    'date_recur_search_api',
    'finders_events',
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

    // We have to use the node entity type in this test rather than
    // entity_test_with_bundle because that makes the table name too long. See
    // https://www.drupal.org/project/date_recur/issues/3485912
    $this->installEntitySchema('node');

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
      'localgov_finders',
      'finder_type',
      'events'
    );
    $channel_bundle->setThirdPartySetting(
      'localgov_finders',
      'finder_role',
      FinderRole::Channel->value,
    );
    $channel_bundle->save();

    // Create an entry bundle.
    $entry_bundle = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'test_entry_bundle_one',
      'status' => TRUE,
    ]);
    $entry_bundle->save();
    $entry_bundle->setThirdPartySetting(
      'localgov_finders',
      'finder_type',
      'events'
    );
    $entry_bundle->setThirdPartySetting(
      'localgov_finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle->save();

    // The channel and entry bundles have finder fields defined on them.
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $channel_fields = $entity_field_manager->getFieldDefinitions('node', 'test_channel_bundle');
    $this->assertArrayHasKey(FinderTypeBase::CHANNEL_TYPES_FIELD, $channel_fields);
    $this->assertArrayHasKey(Events::LIST_VIEW_FIELD, $channel_fields);
    $this->assertArrayHasKey(Events::CALENDAR_VIEW_FIELD, $channel_fields);

    $event_fields = $entity_field_manager->getFieldDefinitions('node', 'test_entry_bundle_one');
    $this->assertArrayHasKey(FinderTypeBase::CHANNEL_SELECTION_FIELD, $event_fields);
    $this->assertArrayHasKey(Events::EVENT_DATE_FIELD, $event_fields);

    // A search index has been created by the creation of the channel bundle.
    $search_index = Index::load('localgov_finders_index_events');
    $this->assertNotEmpty($search_index);

    $datasources = $search_index->getDatasources();
    $this->assertCount(1, $datasources);
    $datasource = reset($datasources);
    $this->assertEquals('date_recur', $datasource->getBaseId());
    $this->assertEquals('date_recur:node__' . Events::EVENT_DATE_FIELD, $datasource->getPluginId());
    $this->assertContains('test_entry_bundle_one', $datasource->getBundles());

    $fields = $search_index->getFields();
    $this->assertArrayHasKey('title', $fields);
    $this->assertArrayHasKey('rendered_item', $fields);
    $this->assertArrayHasKey('localgov_finders_title_sort', $fields);
    $this->assertArrayHasKey('localgov_finders_channels', $fields);
    $this->assertArrayHasKey('finders_events_date_occurrence', $fields);
  }

}
