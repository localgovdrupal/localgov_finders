<?php

namespace Drupal\Tests\finders_facets\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests FindersFacetsConfigSubscriber.
 *
 * @group finders
 */
class IgnoreTypeConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'search_api',
    'finders',
    'finders_facets',
    'finders_facets_ignore_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'finders_facets_ignore_test']);
  }

  /**
   * Test excluding modules from the config export.
   */
  public function testExcludedModules() {
    // Assert that our facet config is in the active config.
    $active = $this->container->get('config.storage');
    $this->assertNotEmpty($active->listAll('finders_facets.finders_facet_type.'));
    $this->assertNotEmpty($active->listAll('system.'));
    // Add collections.
    $collection = $this->randomMachineName();
    foreach ($active->listAll() as $config) {
      $active->createCollection($collection)->write($config, $active->read($config));
    }

    // Assert that facet config is not in the export storage.
    $export = $this->container->get('config.storage.export');
    $this->assertEmpty($export->listAll('finders_facets.finders_facet_type.'));
    $this->assertNotEmpty($export->listAll('system.'));
    // And assert excluded from collections too.
    $this->assertEmpty($export->createCollection($collection)->listAll('finders_facets.finders_facet_type.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('system.'));

    // Assert that existing facet config is again in the import storage.
    $import = $this->container->get('config.import_transformer')->transform($export);
    $this->assertNotEmpty($import->listAll('finders_facets.finders_facet_type.'));
    $this->assertNotEmpty($import->listAll('system.'));

    // Enable export.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['finders_facets_stage_site'] = TRUE;
    new Settings($settings);
    drupal_flush_all_caches();
    // Assert that facet config is in the export storage.
    $export = $this->container->get('config.storage.export');
    $this->assertNotEmpty($export->listAll('finders_facets.finders_facet_type.'));
    $this->assertNotEmpty($export->listAll('system.'));
    // And assert excluded from collections too.
    $this->assertNotEmpty($export->createCollection($collection)->listAll('finders_facets.finders_facet_type.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('system.'));

    // Assert config not removed if it exists in exported storage.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['finders_facets_stage_site'] = FALSE;
    new Settings($settings);
    drupal_flush_all_caches();
    $sync = $this->container->get('config.storage.sync');
    $active = $this->container->get('config.storage');
    // Store sync storage, and make changes to active storage.
    $this->copyConfig($active, $sync);
    $active_facet_type_config = $active->read('finders_facets.finders_facet_type.test');
    $active_facet_type_config['label'] = 'Updated';
    $active->write('finders_facets.finders_facet_type.test', $active_facet_type_config);
    $active_system_config = $active->read('system.site');
    $active_system_config['name'] = 'Updated';
    $active->write('system.site', $active_system_config);
    // Assert that facet config remains as is,
    // but update to sytem is exported.
    $export = $this->container->get('config.storage.export');
    $export_facet_type_config = $export->read('finders_facets.finders_facet_type.test');
    $this->assertEquals($export_facet_type_config['label'], 'Test Facet Type');
    $export_system_config = $export->read('system.site');
    $this->assertEquals($export_system_config['name'], 'Updated');

    // Assert config is imported if present.
    $import = $this->container->get('config.import_transformer')->transform($export);
    $import_facet_type_config = $import->read('finders_facets.finders_facet_type.test');
    $this->assertEquals($import_facet_type_config['label'], 'Test Facet Type');
  }

}
