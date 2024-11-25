<?php

namespace Drupal\Tests\finders_db\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;

/**
 * Tests installing the module.
 *
 * @group finders_db
 */
class FindersDbInstallTest extends KernelTestBase {

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
    'entity_test',
    'search_api',
    'search_api_db',
    'finders',
    'finders_test',
  ];

  /**
   * Disable config checking -- schema for our third-party settings is missing.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The module installer service.
   */
  protected ModuleInstallerInterface $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    $this->installConfig('finders_test');
    $this->installEntitySchema('entity_test_with_bundle');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests installing the finders_db module.
   */
  public function testInstall() {
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

    // A search index has been created by the creation of the channel bundle.
    $search_index = Index::load('finders_index_default');
    $this->assertNotEmpty($search_index);
    $this->assertEmpty($search_index->getServerId());
    $this->assertFalse($search_index->status());

    $this->moduleInstaller->install(['finders_db']);

    /** @var \Drupal\search_api\Entity\IndexInterface $search_index */
    $search_index = $this->reloadEntity($search_index);

    // The search index now has the server set and is enabled.
    $this->assertNotEmpty($search_index->getServerId());
    $this->assertTrue($search_index->status());
  }

  /**
   * Reloads the given entity from the storage and returns it.
   *
   * @todo Replace this with EntityTrait when
   * https://www.drupal.org/project/drupal/issues/3485409 is fixed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be reloaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity): EntityInterface {
    // Need to very forcibly clear lots of caches, AND get the relevant services
    // from the Drupal object and NOT $this->container.
    // https://www.drupal.org/project/drupal/issues/3485409
    \Drupal::service('entity_type.manager')->clearCachedDefinitions();
    \Drupal::service('config.factory')->clearStaticCache();
    \Drupal::service('config.factory')->reset();

    $storage = \Drupal::service('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    return $storage->load($entity->id());
  }

}
