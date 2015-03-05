<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Hardcoded implementation for the calendar filter for the content overview widget
 */
class CalendarContentOverviewFilter extends DateContentOverviewFilter {

    /**
     * Gets the options for the date field
     * @param \ride\library\orm\model\Model $model
     * @param string $fieldName Name of the field
     * @param string $locale Code of the current locale
     * @return array
     */
    protected function getMonthOptions(Model $model, $fieldName, $locale) {
        $query = $model->createQuery($locale);
        $query->setDistinct(true);
        $query->setFields('DATE_FORMAT(FROM_UNIXTIME({dateStart}), \'%Y-%m\') AS monthYear');
        $query->addOrderBy('monthYear DESC');

        $months = array_keys($query->query('monthYear'));

        return array_combine($months, $months);
    }

    /**
     * Applies the filter to the provided query
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\orm\query\ModelQuery $query
     * @param string $fieldName Name of the filter field
     * @param string|array $value Submitted value
     * @return string|array Value of the filter
     */
    public function applyQuery(Model $model, ModelQuery $query, $fieldName, $value = null) {
        if ($value === null || (is_array($value) && !$value)) {
            return null;
        }

        $from = null;
        $until = null;

        $this->getPeriodFromValue($value, $from, $until);

        if ($from && $until) {
            $query->addCondition('({dateStop} IS NULL AND %1% <= {dateStart} AND {dateStart} <= %2%) OR ({dateStop} IS NOT NULL AND ((%1% <= {dateStart} AND {dateStart} <= %2%) OR (%1% <= {dateStop} AND {dateStop} <= %2%) OR ({dateStart} <= %1% AND %2% <= {dateStop})))', $from, $until);
        }

        return $value;
    }

}
