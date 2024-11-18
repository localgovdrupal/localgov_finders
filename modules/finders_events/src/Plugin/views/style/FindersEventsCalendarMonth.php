<?php

namespace Drupal\finders_events\Plugin\views\style;

use Drupal\calendar_view\Plugin\views\style\CalendarViewMonth;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\finders_events\Plugin\FinderType\Events;
use Drupal\search_api\Plugin\views\field\SearchApiStandard;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Calendar view style for event finders.
 *
 * This is needed because calendar_view module's style plugin doesn't recognise
 * SearchAPI fields.
 *
 * This only works on a view on the events SearchAPI index, as it hardcode our
 * event occurrence field name.
 *
 * To use this in a view:
 *
 * 1. Create a view using the events SearchAPI index as its base.
 * 2. Set the format to show fields.
 * 3. Add a date field for the date occurrence.
 * 4. Select this style for the view, and select the date field in the calendar
 *    style options.
 * 5. Add other fields as required.
 *
 * @todo Figure out how to make isDateField() work with any SearchAPI field, and
 * then make that into a patch for calendar_view module, and remove the need for
 * this plugin.
 *
 * @see https://www.drupal.org/project/calendar_view/issues/3488137
 */
#[ViewsStyle(
  id: 'finders_events_calendar_month',
  title: new TranslatableMarkup('Finders Events Calendar Month'),
  theme: 'views_view_calendar',
  display_types: [
    'normal',
  ],
)]
class FindersEventsCalendarMonth extends CalendarViewMonth {

  /**
   * {@inheritdoc}
   */
  function isDateField($field) {
    // There does not seem to be an easy way to detect a SearchAPI field being
    // a date. A datetime field uses the default Views field plugin, and not
    // the 'search_api_date' which is only for timestamp fields.
    if ($field->field == Events::EVENT_DATE_OCCURRENCE_FIELD) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFieldValues(ResultRow $row, FieldPluginBase $field, int $delta = 0) {
    if ($field instanceof SearchApiStandard) {
      return ['value' => $field->getValue($row)[0]];
    }

    return [];
  }

}
