<?php

namespace Drupal\Tests\finders\Kernel;

use Drupal\finders\Plugin\FinderType\FinderTypeBase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests installing a finder from install config.
 *
 * @group finders
 */
class FindersConfigInstallTest extends KernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('search_api_task');

    $this->installEntitySchema('entity_test_with_bundle');
    $this->installConfig('finders');
    $this->installConfig('finders_test');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests installation of a finder from module install config.
   */
  public function testModuleConfig(): void {
    $channel = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'name' => 'test channel',
      'type' => 'finders_channel',
    ]);
    $channel->save();
    $this->assertTrue($channel->hasField(FinderTypeBase::CHANNEL_TYPES_FIELD));

    $entry = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'name' => 'test entry',
      'type' => 'finders_entry',
    ]);
    $entry->save();
    $this->assertTrue($entry->hasField(FinderTypeBase::CHANNEL_SELECTION_FIELD));
    $this->assertTrue($entry->hasField(FinderTypeBase::TITLE_SORT_FIELD));
  }

}
