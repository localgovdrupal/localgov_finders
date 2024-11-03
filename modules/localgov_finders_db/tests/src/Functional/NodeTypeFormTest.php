<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_finders_db\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\localgov_finders\Functional\NodeTypeFormTest as OriginalNodeTypeFormTest;

/**
 * Test from intergation with node type.
 */
class NodeTypeFormTest extends OriginalNodeTypeFormTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_finders_db',
  ];

}

#class commentOutCode {
#  /**
#   * Use testing profile.
#   *
#   * @var string
#   */
#  protected $profile = 'testing';
#
#  /**
#   * Use stark theme.
#   *
#   * {@inheritdoc}
#   */
#  protected $defaultTheme = 'stark';
#
#  /**
#   * Modules to enable.
#   *
#   * @var array
#   */
#  protected static $modules = [
#    'localgov_finders_test',
#  ];
#
#  /**
#   * {inheritdoc}
#   */
#  protected function setUp(): void {
#    parent::setUp();
#
#    $admin_user = $this->drupalCreateUser([
#      'access administration pages',
#      'access content',
#      'access content overview',
#      'administer content types',
#      'administer nodes',
#      'bypass node access',
#    ]);
#    $this->drupalLogin($admin_user);
#  }
#
#  /**
#   * Create a channel and entry type.
#   */
#  public function testCreateFinder() {
#    $this->drupalGet('admin/structure/types/add');
#
#    $assert = $this->assertSession();
#    $page = $this->getSession()->getPage();
#
#    $page->fillField('edit-name', 'Finder channel');
#    $page->fillField('edit-type', 'finder_channel');
#    $finder_types = $page->findField('localgov_finders[finder_type]');
#    $finder_types->find('named', ['radio', 'None']);
#    $finder_types->selectOption('test');
#    // @todo Should entry show before a channel exists?
#    // If so test what happens when selected.
#    $entry_types = $page->findField('localgov_finders[finder_role]');
#    $entry_types->find('named', ['radio', 'None']);
#    $entry_types->selectOption('channel');
#
#    $page->pressButton('Save');
#    $this->drupalGet('admin/structure/types/manage/finder_channel');
#
#    $assert->pageTextContains('Finder channel');
#    $finder_types = $page->findField('localgov_finders[finder_type]');
#    $this->assertEquals($finder_types->getValue(), 'test');
#    $entry_types = $page->findField('localgov_finders[finder_role]');
#    $this->assertEquals($entry_types->getValue(), 'channel');
#
#    $channel_type = NodeType::load('finder_channel');
#    $this->assertEquals($channel_type->getThirdPartySetting('localgov_finders', 'finder_type'), 'test');
#    $this->assertEquals($channel_type->getThirdPartySetting('localgov_finders', 'finder_role'), 'channel');
#
#    // Config should be tested in Kernel tests for adding third party setting.
#    // But could also check fields here?
#  }
#
#}
