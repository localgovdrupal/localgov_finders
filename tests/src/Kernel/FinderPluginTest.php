<?php

declare(strict_types=1);

namespace Drupal\Tests\finders\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\finders\Enum\FinderRole;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;
use Drupal\search_api\Entity\Index;

/**
 * Test description.
 *
 * @group finders
 */
final class FinderPluginTest extends KernelTestBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'views',
    'finders',
    'finders_test',
    'search_api',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('search_api_task');

    $this->installConfig('finders_test');
    $this->installEntitySchema('entity_test_with_bundle');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests installation of a finder from module install config.
   */
  public function testModuleConfig(): void {
    $channel = EntityTestWithBundle::create([
      'name' => 'test channel',
      'type' => 'finders_channel',
    ]);
    $channel->save();
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));
  }

  /**
   * Tests setting up a finder type by configuring bundles.
   */
  public function testUpdateBundle(): void {
    $channel_bundle = EntityTestBundle::create([
      'id' => 'test_channel_bundle',
      'status' => TRUE,
    ]);
    $channel_bundle->save();
    $channel = EntityTestWithBundle::create([
      'name' => 'test channel',
      'type' => 'test_channel_bundle',
    ]);
    $channel->save();
    $this->assertFalse($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));

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

    $channel = $this->reloadEntity($channel);
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));

    // A search index has been created by the creation of the channel bundle.
    $search_index = Index::load('finders_index_default');
    $this->assertNotEmpty($search_index);
    $this->assertEmpty($search_index->getFields());

    // A view has been created by the creation of the channel bundle.
    $view = $this->entityTypeManager->getStorage('view')->load('finders_channel_view');
    $this->assertNotEmpty($view);

    $entry_bundle_one = EntityTestBundle::create([
      'id' => 'test_entry_bundle_one',
      'status' => TRUE,
    ]);
    $entry_bundle_one->save();
    $entry_bundle_two = EntityTestBundle::create([
      'id' => 'test_entry_bundle_two',
      'status' => TRUE,
    ]);
    $entry_bundle_two->save();

    // Set up the entry bundles as finder entries.
    $entry_bundle_one->setThirdPartySetting(
      'finders',
      'finder_type',
      'test'
    );
    $entry_bundle_one->setThirdPartySetting(
      'finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle_one->save();

    $entry_bundle_two->setThirdPartySetting(
      'finders',
      'finder_type',
      'test'
    );
    $entry_bundle_two->setThirdPartySetting(
      'finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle_two->save();

    // The search index has been updated by the creation of the entry bundles.
    $search_index = $this->reloadEntity($search_index);
    $fields = $search_index->getFields();
    // The entity_test_with_bundle label field is 'name', unlike nodes.
    $this->assertArrayHasKey('name', $fields);
    $this->assertArrayHasKey('rendered_item', $fields);
    $this->assertArrayHasKey('finders_title_sort', $fields);
    $this->assertArrayHasKey('finders_channels', $fields);

    // The view has been updated by the creation of the entry bundles.
    $view = $this->reloadEntity($view);
    $this->assertArrayHasKey('finders_title_sort', $view->getDisplay('default')['display_options']['sorts']);
    $this->assertArrayHasKey('finders_channels', $view->getDisplay('default')['display_options']['arguments']);

    // At present unrestricted which test entity type bundles.
    $channel->{FinderTypeBase::CHANNEL_TYPES_FIELD} = $channels = [
      ['target_id' => 'test_entry_bundle_one'],
      ['target_id' => 'test_entry_bundle_two'],
    ];
    $channel->save();

    $channel = EntityTestWithBundle::load($channel->id());
    $this->assertEquals($channels, $channel->get(FinderTypeBase::CHANNEL_TYPES_FIELD)->getValue());

    $entry = EntityTestWithBundle::create([
      'name' => 'test entry',
      'type' => 'test_entry_bundle_one',
      FinderTypeBase::CHANNEL_SELECTION_FIELD => $channel->id(),
    ]);
    $entry->save();
  }

  /**
   * Reloads the given entity from the storage and returns it.
   *
   * TODO: Replace this with EntityTrait when 10.4.0 is minimum supported
   * version.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be reloaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    $controller = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $controller->resetCache([$entity->id()]);
    return $controller->load($entity->id());
  }

}
