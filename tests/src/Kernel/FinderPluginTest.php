<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_finders\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_finders\Enum\FinderRole;
use Drupal\localgov_finders\Plugin\FinderType\FinderTypeBase;
use Drupal\search_api\Entity\Index;
use Hoa\File\Finder;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;

/**
 * Test description.
 *
 * @group localgov_finders
 */
final class FinderPluginTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;

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
    'localgov_finders',
    'localgov_finders_test',
    'search_api',
    'entity_test',
    'user',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('search_api_task');

    // $this->installConfig('localgov_finders_test');
    $this->installEntitySchema('entity_test_with_bundle');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }


    // This breaks because of the channel bundle that's in the config install! ARGH!
    // But that's ok -- the upgrade path won't have config imported.


  public function testUpgrade(): void {
    $channel_bundle = EntityTestBundle::create([
      'id' => 'test_channel_bundle',
      'status' => TRUE,
    ]);
    $channel_bundle->save();

    $entry_bundle_one = EntityTestBundle::create([
      'id' => 'test_entry_bundle_one',
      'status' => TRUE,
    ]);
    $entry_bundle_one->save();

    $this->createEntityReferenceField('entity_test_with_bundle', 'test_channel_bundle', FinderTypeBase::CHANNEL_TYPES_FIELD, 'label', 'entity_test_with_bundle', cardinality: -1);

    $entry = EntityTestWithBundle::create([
      'name' => 'test channel',
      'type' => 'test_entry_bundle_one',
    ]);
    $entry->save();

    $channel = EntityTestWithBundle::create([
      'name' => 'test channel',
      'type' => 'test_channel_bundle',
      FinderTypeBase::CHANNEL_TYPES_FIELD => [$entry->id()],
    ]);
    $channel->save();
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));
    $this->assertFalse($channel->get(FinderTypeBase::CHANNEL_TYPES_FIELD)->isEmpty());
    // dump($channel->toArray());
    //

    $entity_field_manager = \Drupal::service('entity_field.manager');
    $table_mapping = $this->entityTypeManager->getStorage('entity_test_with_bundle')->getTableMapping();
    $field_definition = $entity_field_manager->getFieldStorageDefinitions('entity_test_with_bundle')[FinderTypeBase::CHANNEL_TYPES_FIELD];
    $table_name = $table_mapping->getDedicatedDataTableName($field_definition);

    $database = \Drupal::service('database');
    $database->query('CREATE TABLE {backup} AS SELECT * FROM {' . $table_name . '}');
    // TODO! revision table backup!

    FieldStorageConfig::loadByName('entity_test_with_bundle', FinderTypeBase::CHANNEL_TYPES_FIELD)->delete();
    // TODO: need to purge them ALL?!
    // TODO -- the previous call renames the DB table, so do we even need this??
    // field_purge_batch(10);

    // Set both bundles as finders.
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

    // Set up the entry bundle as finder entries.
    $entry_bundle_one->setThirdPartySetting(
      'localgov_finders',
      'finder_type',
      'test'
    );
    $entry_bundle_one->setThirdPartySetting(
      'localgov_finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle_one->save();

    // Need to get the new field definition, as it won't have the same unique
    // ID which gets used to make the hash if the table name has one.
    $field_definition = $entity_field_manager->getFieldStorageDefinitions('entity_test_with_bundle')[FinderTypeBase::CHANNEL_TYPES_FIELD];
    $table_name = $table_mapping->getDedicatedDataTableName($field_definition);
    $database->query('INSERT INTO {' . $table_name . '} SELECT * FROM {backup}');
    // TODO delete the temp table for the next field to work!

    $channel = $this->reloadEntity($channel);
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));
    $this->assertFalse($channel->get(FinderTypeBase::CHANNEL_TYPES_FIELD)->isEmpty());

    // INSERT INTO new_table SELECT * FROM old_table;

    // dump($channel->toArray());
    // Nooooo this deleted our field :(
    // dump($channel->getFieldDefinition(FinderTypeBase::CHANNEL_TYPES_FIELD));

  }

  /**
   * Test callback.
   */
  public function testModuleConfig(): void {
    $channel = EntityTestWithBundle::create([
      'name' => 'test channel',
      'type' => 'localgov_finders_channel',
    ]);
    $channel->save();
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));
  }

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

    $channel = $this->reloadEntity($channel);
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));

    // A search index has been created by the creation of the channel bundle.
    $search_index = Index::load('localgov_finders_index_default');
    $this->assertNotEmpty($search_index);
    $this->assertEmpty($search_index->getFields());

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
      'localgov_finders',
      'finder_type',
      'test'
    );
    $entry_bundle_one->setThirdPartySetting(
      'localgov_finders',
      'finder_role',
      FinderRole::Entries->value,
    );
    $entry_bundle_one->save();

    $entry_bundle_two->setThirdPartySetting(
      'localgov_finders',
      'finder_type',
      'test'
    );
    $entry_bundle_two->setThirdPartySetting(
      'localgov_finders',
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
    $this->assertArrayHasKey('localgov_finders_title_sort', $fields);
    $this->assertArrayHasKey('localgov_finders_channels', $fields);

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
