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
    'node',
    'search_api',
    'search_api_db',
    'finders',
    'finders_test',
  ];

  // Disable config checking -- the schema is in the test module and we don't
  // want what it installs.
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

    $this->installConfig('finders_test');
    $this->installEntitySchema('node');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests installing the finders_db module.
   */
  public function testInstall() {
    $channel_bundle = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'test_channel_bundle',
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
   * TODO: Replace this with EntityTrait when
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
