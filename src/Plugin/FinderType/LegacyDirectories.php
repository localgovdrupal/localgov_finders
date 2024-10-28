<?php

namespace Drupal\localgov_finders\Plugin\FinderType;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\localgov_directories\Constants;
use Drupal\localgov_finders\Attribute\FinderType;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field as SearchIndexField;

/**
 * Directories finder type for sites built on Directories 3.x.
 *
 * This will use config fields to maintain compatibility.
 *
 * POC -- will move to the LGD Directories module.
 */
#[FinderType(
  id: "directories_legacy",
  label: new TranslatableMarkup("Directories (legacy support)"),
  description: new TranslatableMarkup("Provides directories which have entries (pages, venues, etc.) which can be filtered and searched"),
)]
class LegacyDirectories extends FinderTypeBase {

  /**
   * {@inheritdoc}
   */
  const string CHANNEL_TYPES_FIELD = 'localgov_directory_channel_types';

  /**
   * {@inheritdoc}
   */
  const string CHANNEL_SELECTION_FIELD = Constants::CHANNEL_SELECTION_FIELD;

  /**
   * {@inheritdoc}
   */
  public function getChannelFieldDefinitions(ConfigEntityInterface $bundle): array {
    // Legacy directories finder doesn't add bundle fields.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntryFieldDefinitions(ConfigEntityInterface $bundle): array {
    // Legacy directories finder doesn't add bundle fields.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds(): array {
    return [Constants::DEFAULT_INDEX];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexFields(ConfigEntityInterface $bundle, IndexInterface $index): array {
    $fields = [];

    // This is one of the fields that can't be included in the default config,
    // and needs to be added with the first content type.
    $field = new SearchIndexField($index, Constants::CHANNEL_SELECTION_FIELD);
    $field->setLabel('Directory channels');
    $field->setDataSourceId('entity:node');
    $field->setPropertyPath(Constants::CHANNEL_SELECTION_FIELD);
    $field->setType('string');
    $field->setDependencies([
      'config' => [
        'field.storage.node.' . Constants::CHANNEL_SELECTION_FIELD,
      ],
    ]);
    $fields[Constants::CHANNEL_SELECTION_FIELD];

    // This one should already exist, as it can be included in the default cofig.
    // But we need to update it with the correct bundles.
    // It's also likely, but not guaranteed, to be on all the indexes.
    $field = new SearchIndexField($index, 'rendered_item');
    $field->setLabel('Rendered HTML output');
    $field->setDataSourceId('entity:node');
    $field->setPropertyPath('rendered_item');
    $field->setType('text');
    // It's this that needs to change, and as we've got the index we can
    // look up the present configuration of the field here, and add to it.
    //
    // if ($index->getField('rendered_item')) {
    //   ...
    // }
    //
    // It really feels like this becomes a single alter method, but it feels
    // like it would be nicer if wasn't.
    $field->setConfiguration([
      'roles' => [
        'anonymous' => 'anonymous',
      ],
      'view_mode' => [
        'entity:node' => [
          'localgov_directories_page' => 'directory_index',
          'node' => 'directory_index',
        ],
      ],
    ]);
    $fields['rendered_item'] = $field;

    return $fields;
  }

}
