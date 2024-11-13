<?php

namespace Drupal\Tests\finders\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\finders\Plugin\FinderType\FinderTypeBase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test case class TODO.
 *
 * @group finders
 */
class FindersTest extends KernelTestBase {

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
    // We use this for its test finder plugin, but we don't install its config.
    'finders_test',
    'entity_test',
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

  // TEMP!
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('search_api_task');

    $this->installEntitySchema('entity_test_with_bundle');

    $this->installConfig('finders');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
  }

  /**
   * Tests setting up a finder.
   */
  public function testFinderSetup() {
    // Create bundles that will be channels and entries.
    $channel_bundle = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_channel_bundle',
      'label' => 'Label',
      'status' => TRUE,
    ]);
    $channel_bundle->save();

    $entry_bundle_one = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_entry_bundle_one',
      'label' => 'Label',
      'status' => TRUE,
    ]);
    $entry_bundle_one->save();
    $entry_bundle_two = $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'id' => 'test_entry_bundle_two',
      'label' => 'Label',
      'status' => TRUE,
    ]);
    $entry_bundle_two->save();

    // Create a channel entity.
    $channel = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'name' => 'test channel',
      'type' => 'test_channel_bundle',
    ]);
    $channel->save();
    // The channel does not have the finder field on it yet.
    $this->assertFalse($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));

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
          'test_entry_bundle_two',
        ],
      ],
    ]);
    $finder->save();

    $channel_fields = $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'test_channel_bundle');
    $this->assertArrayHasKey(FinderTypeBase::CHANNEL_TYPES_FIELD, $channel_fields);

    $channel = $this->reloadEntity($channel);
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));

    $entry_fields = $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'test_entry_bundle_one');
    $this->assertArrayHasKey(FinderTypeBase::CHANNEL_SELECTION_FIELD, $entry_fields);

    // A search index has been created by the creation of the channel bundle.
    $search_index = $this->entityTypeManager->getStorage('search_api_index')->load('finders_index_default');
    $this->assertNotEmpty($search_index);
    $fields = $search_index->getFields();
    // The entity_test_with_bundle label field is 'name', unlike nodes.
    $this->assertArrayHasKey('name', $fields);
    $this->assertArrayHasKey('rendered_item', $fields);
    $this->assertArrayHasKey('finders_title_sort', $fields);
    $this->assertArrayHasKey('finders_channels', $fields);

    // A view has been created by the creation of the channel bundle.
    $view = $this->entityTypeManager->getStorage('view')->load('finders_channel_view');
    $this->assertNotEmpty($view);

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

    $channel = $this->reloadEntity($channel);
    $this->assertEquals($channels, $channel->get(FinderTypeBase::CHANNEL_TYPES_FIELD)->getValue());

    // Create an entry entity.
    $entry = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'name' => 'test entry',
      'type' => 'test_entry_bundle_one',
      FinderTypeBase::CHANNEL_SELECTION_FIELD => $channel->id(),
    ]);
    $entry->save();

    // The possible values for the channels types field on the channel entity
    // are the entry bundles for the finder.
    $this->assertEquals(
      [
        'test_entry_bundle_one',
        'test_entry_bundle_two',
      ],
      $channel->{FinderTypeBase::CHANNEL_TYPES_FIELD}->get(0)->getSettableValues()
    );

    // The possible values for the channels field on the entry entity
    // are the channels that reference the entry entity's bundle.
    $this->assertEquals(
      [
        $channel->id(),
      ],
      $entry->{FinderTypeBase::CHANNEL_SELECTION_FIELD}->get(0)->getSettableValues()
    );
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
