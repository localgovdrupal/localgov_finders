<?php

namespace Drupal\Tests\localgov_finders_db\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;

/**
 * Tests installing the module.
 *
 * @group localgov_finders_db
 */
class LocalGovFindersDbInstallTest extends KernelTestBase {

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
    'localgov_finders',
    'localgov_finders_test',
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

    $this->installConfig('localgov_finders_test');
    $this->installEntitySchema('node');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests installing the localgov_finders_db module.
   */
  public function testInstall() {
    $channel_bundle = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'test_channel_bundle',
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

    // A search index has been created by the creation of the channel bundle.
    $search_index = Index::load('localgov_finders_index_default');
    $this->assertNotEmpty($search_index);
    $this->assertEmpty($search_index->getServerId());

    $this->moduleInstaller->install(['localgov_finders_db']);

    // DOES NOT WORK!! AARGH!
    $search_index = $this->reloadEntity($search_index);
    $this->assertNotEmpty($search_index->getServerId());
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
    // AAAAAAARGGGGGH
    // https://www.drupal.org/project/drupal/issues/3485409
    \Drupal::configFactory()->clearStaticCache();

    $this->container->get('config.factory')->reset();
    $this->container->get('config.factory')->clearStaticCache();

    $config = \Drupal::configFactory();

    $controller = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $controller->resetCache([$entity->id()]);
    return $controller->load($entity->id());
  }

}
