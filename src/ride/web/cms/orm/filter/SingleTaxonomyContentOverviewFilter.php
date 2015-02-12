<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\field\HasField;
use ride\library\orm\definition\field\HasManyField;
use ride\library\orm\definition\field\ModelField;
use ride\library\orm\definition\field\PropertyField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Implementation for a single taxonomy term filter for the content overview widget
 */
class SingleTaxonomyContentOverviewFilter extends SingleContentOverviewFilter {
    /**
     * Applies the filter to the provided query
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\orm\query\ModelQuery $query
     * @param string $fieldName Name of the filter field
     * @param string|array $value Submitted value
     * @return string|array Value of the filter
     */
    public function applyQuery(Model $model, ModelQuery $query, $fieldName, $value = null) {
        $isArray = is_array($value);
        if ($value === null || ($isArray && !$value)) {
            return null;
        }

        $conditionField = '';
        if (!$this->prepareQuery($model, $query, $fieldName, $conditionField)) {
            return null;
        }

        if ($isArray) {
            $value = array_shift($value);
        }

        $tokens = explode('.', $conditionField);
        if (count($tokens) == 1) {
            $parentConditionField = $conditionField . '.parent';
        } else {
            array_pop($tokens);
            array_push($tokens, 'parent');

            $parentConditionField = implode('.', $tokens);
        }

        $query->addCondition('{' . $conditionField . '} = %1% OR {' . $parentConditionField . '} = %1%', $value);

        return $value;
    }

}
