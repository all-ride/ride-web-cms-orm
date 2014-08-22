<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Implementation for a date filter for the content overview widget
 */
class DateContentOverviewFilter extends AbstractContentOverviewFilter {

    /**
     * Gets the available options for the filter
     * @param array $filters Filters to update
     * @param \ride\library\orm\model\Model $model
     * @param string $name Name of the filter
     * @param string $fieldName Name of the filter field
     * @param string $locale Code of the current locale
     * @param string $baseUrl Base URL
     * @return null
     */
    public function setVariables(array &$filters, Model $model, $name, $locale, $baseUrl) {
        $filters[$name]['options'] = array();
        $filters[$name]['urls'] = array();
        $filters[$name]['values'] = array();
        $filters[$name]['empty'] = $this->getUrl($baseUrl, $filters, $name, null);
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

        if ($from) {
            $query->addCondition('{' . $fieldName . '} >= %1%', $from);
        }

        if ($until) {
            $query->addCondition('{' . $fieldName . '} <= %1%', $until);
        }

        return $value;
    }

    /**
     * Gets the filter period from the filter value
     * @param string|array $value
     * @param integer $from
     * @param integer $until
     * @return null
     */
    protected function getPeriodFromValue($value, &$from, &$until) {
        $isArray = is_array($value);

        if (!$isArray || count($value) === 1) {
            if ($isArray) {
                $value = array_shift($value);
            }

            $tokens = explode('-', $value);

            if (isset($tokens[2])) { // day
                $from = mktime(0, 0, 0, $tokens[1], $tokens[2], $tokens[0]);
                $until = mktime(23, 59, 59, $tokens[1], $tokens[2], $tokens[0]);
            } elseif (isset($tokens[1])) { // month
                $from = mktime(0, 0, 0, $tokens[1], 1, $tokens[0]);
                $until = mktime(23, 59, 59, $tokens[1], date('t', $from), $tokens[0]);
            } elseif (isset($tokens[0])) { // year
                $from = mktime(0, 0, 0, 1, 1, $tokens[0]);
                $until = mktime(23, 59, 59, 12, 31, $tokens[0]);
            }
        } else {
            $fromValue = array_shift($value);
            $tokens = explode('-', $fromValue);

            if (isset($tokens[2])) { // day
                $from = mktime(0, 0, 0, $tokens[1], $tokens[2], $tokens[0]);
            } elseif (isset($tokens[1])) { // month
                $from = mktime(0, 0, 0, $tokens[1], 1, $tokens[0]);
            } elseif (isset($tokens[0])) { // year
                $from = mktime(0, 0, 0, 1, 1, $tokens[0]);
            }

            $untilValue = array_shift($value);
            $tokens = explode('-', $fromValue);

            if (isset($tokens[2])) { // day
                $until = mktime(23, 59, 59, $tokens[1], $tokens[2], $tokens[0]);
            } elseif (isset($tokens[1])) { // month
                $until = mktime(23, 59, 59, $tokens[1], date('t', date(0, 0, 0, $tokens[1], 1, $tokens[0])), $tokens[0]);
            } elseif (isset($tokens[0])) { // year
                $until = mktime(23, 59, 59, 12, 31, $tokens[0]);
            }
        }
    }

}
