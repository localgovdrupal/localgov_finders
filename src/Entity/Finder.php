<?php

namespace Drupal\finders\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\finders\Enum\FinderRole;

/**
 * Provides the Finder entity.
 *
 * @ConfigEntityType(
 *   id = "finder",
 *   label = @Translation("Finder"),
 *   label_collection = @Translation("Finders"),
 *   label_singular = @Translation("finder"),
 *   label_plural = @Translation("finders"),
 *   label_count = @PluralTranslation(
 *     singular = "@count finder",
 *     plural = "@count finders",
 *   ),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\finders\Form\FinderForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "storage" = "Drupal\finders\Entity\Handler\FinderStorage",
 *     "list_builder" = "Drupal\finders\Entity\Handler\FinderListBuilder",
 *   },
 *   admin_permission = "administer finder entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "channels",
 *     "entries",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/finder/add",
 *     "canonical" = "/admin/structure/finder/{finder}",
 *     "collection" = "/admin/structure/finder",
 *     "edit-form" = "/admin/structure/finder/{finder}/edit",
 *     "delete-form" = "/admin/structure/finder/{finder}/delete",
 *   },
 * )
 */
class Finder extends ConfigEntityBase implements FinderInterface {

  /**
   * Machine name.
   *
   * @var string
   */
  protected $id = '';

  /**
   * Name.
   *
   * @var string
   */
  protected $label = '';

  /**
   * The finder type plugin ID.
   *
   * @var string
   */
  protected $type = '';

  /**
   * The entity bundles which are channels for this finder.
   *
   * @var array
   */
  protected $channels = [];

  /**
   * The entity bundles which are entries for this finder.
   *
   * @var array
   */
  protected $entries = [];

  /**
   * The plugin collection that holds the finder plugin for this entity.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->type);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      // Finder type plugins don't have any settings, so this key is immaterial.
      'settings' => $this->getPluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the finder's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The finder's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.finders_finder_type'), $this->type, []);
    }
    return $this->pluginCollection;
  }

  public function getFinderRoleForBundle(ConfigEntityInterface $bundle_entity): ?FinderRole {
    $entity_type_id = $bundle_entity->getEntityType()->getBundleOf();

    if (in_array($bundle_entity->id(), $this->get('channels')[$entity_type_id])) {
      return FinderRole::Channel;
    }
    if (in_array($bundle_entity->id(), $this->get('entries')[$entity_type_id])) {
      return FinderRole::Entries;
    }

    return NULL;
  }

  /**
   * Gets the channel bundle entities for this finder.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]
   *   A numeric array of bundle entities which are used as channels by this
   *   finder.
   */
  public function getChannelBundles(): array {
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $bundles = [];
    foreach ($this->channels as $entity_type_id => $bundle_names) {
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $bundle_entity_type_id = $entity_type->getBundleEntityType();

      $bundles = array_merge($bundles, $entity_type_manager->getStorage($bundle_entity_type_id)->loadMultiple($bundle_names));
    }

    return $bundles;
  }

  /**
   * Gets the entry bundle entities for this finder.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]
   *   A numeric array of bundle entities which are used as entries by this
   *   finder.
   */
  public function getEntryBundles(): array {
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $bundles = [];
    foreach ($this->entries as $entity_type_id => $bundle_names) {
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $bundle_entity_type_id = $entity_type->getBundleEntityType();

      $bundles = array_merge($bundles, $entity_type_manager->getStorage($bundle_entity_type_id)->loadMultiple($bundle_names));
    }

    return $bundles;
  }


  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // TODO validation! at the config schema level -- bundle can only be in one finder!

    $finder_type = $this->getPlugin();

    $finder_config_manager = \Drupal::service('finders.finder_config_manager');
    foreach ($this->getChannelBundles() as $channel_bundle) {
      $finder_config_manager->configureAsChannel($channel_bundle, $finder_type);
    }
    foreach ($this->getEntryBundles() as $entry_bundle) {
      $finder_config_manager->configureAsEntry($entry_bundle, $finder_type);
    }
  }

}
