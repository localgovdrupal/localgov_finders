<?php

namespace Drupal\Tests\localgov_finders\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_finders\Enum\FinderRole;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeBase;

/**
 * Tests indexing entries.
 *
 * WIP! Doesn't work!
 *
 * @group localgov_finders
 */
class SearchIndexTest extends KernelTestBase {

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
    'localgov_finders',
    'localgov_finders_test',
    'search_api_db',
    'localgov_finders_db',
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

    $this->installConfig('localgov_finders_test');

    // Create a channel bundle.
    $channel_bundle = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_channel_bundle',
      'status' => TRUE,
    ]);
    $channel_bundle->setThirdPartySetting(
      'localgov_finders',
      'finder_type',
      'test'
    );
    $channel_bundle->setThirdPartySetting(
      'localgov_finders',
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
      'localgov_finders',
      'finder_type',
      'test'
    );
    $entry_bundle->setThirdPartySetting(
      'localgov_finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle->save();
  }

  /**
   * Tests that entries are indexed.
   */
  public function testMyTest() {
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

    /** @var \Drupal\search_api\IndexInterface */
    $index = $this->entityTypeManager->getStorage('search_api_index')->load('localgov_finders_index_default');
    $index->reindex();
    $indexed = $index->indexItems();
    // TODO: doesn't work!
    dump($indexed);
  }

}
