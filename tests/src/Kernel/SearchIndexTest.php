<?php

namespace Drupal\Tests\finders\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\finders\Enum\FinderRole;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Tests indexing entries.
 *
 * WIP! Doesn't work!
 *
 * @group finders
 */
class SearchIndexTest extends KernelTestBase {

protected $strictConfigSchema = FALSE;

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'entity_test',
    'search_api',
    'finders',
    'finders_test',
    'search_api_db',
    'finders_db',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('search_api_task');
    $this->installSchema('search_api', ['search_api_item']);
    $this->installConfig('search_api');
    $this->installConfig(['finders_db']);

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    $this->installConfig('finders_test');

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
      'channel'
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

  /**
   * Tests that entries are indexed.
   */
  public function testEntriesIndexed(): void {
    $channel = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'name' => 'test channel',
      'type' => 'test_channel_bundle',
    ]);
    $channel->save();

    $entry = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'name' => 'test entry',
      'type' => 'test_entry_bundle_one',
      FinderTypeBase::CHANNEL_SELECTION_FIELD => $channel->id(),
    ]);
    $entry->save();

    $index = $this->entityTypeManager->getStorage('search_api_index')->load('finders_index_default');
    $indexed = $index->indexItems();
    $this->assertEquals(1, $indexed);
  }

}
