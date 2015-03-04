<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Implementation for a single value filter for the content overview widget
 */
class MultiContentOverviewFilter extends SingleContentOverviewFilter {

    /**
     * Operator between the different values (AND or OR)
     * @var string
     */
    protected $operator;

    /**
     * Constructs a new multiple content filter
     * @param string $operator Operator between the different values (AND or OR)
     * @return null
     */
    public function __construct($operator) {
        $this->operator = $operator;
    }

    /**
     * Gets the operator of this filter
     * @return string
     */
    public function getOperator() {
        return $this->operator;
    }

    /**
     * Gets the available options for the filter
     * @param array $filters Filters to update
     * @param \ride\library\orm\model\Model $model
     * @param string $name Name of the filter
     * @param string $locale Code of the current locale
     * @param string $baseUrl Base URL
     * @return null
     */
    public function setVariables(array &$filters, Model $model, $name, $locale, $baseUrl) {
        $field = null;
        $relationModel = null;
        if (!$this->parseRelationField($model, $filters[$name]['field'], $field, $relationModel)) {
            return null;
        }

        $entries = $this->getEntries($field, $relationModel, $locale);
        $options = $relationModel->getOptionsFromEntries($entries);

        $filters[$name]['entries'] = $entries;
        $filters[$name]['options'] = $options;
        $filters[$name]['urls'] = array();
        $filters[$name]['values'] = array();
        $filters[$name]['empty'] = $this->getUrl($baseUrl, $filters, $name, null);

        if (isset($filters[$name]['value'])) {
            $baseValue = $filters[$name]['value'];
        } else {
            $baseValue = array();
        }

        if ($baseValue && !is_array($baseValue)) {
            $baseValue = array($baseValue);
        }

        foreach ($options as $id => $label) {
            if (in_array($id, $baseValue)) {
                $value = $baseValue;
                foreach ($value as $i => $v) {
                    if ($v == $id) {
                        unset($value[$i]);
                    }
                }
            } else {
                $value = $baseValue;
                $value[] = $id;
            }

            $filters[$name]['urls'][$label] = $this->getUrl($baseUrl, $filters, $name, $value);
            $filters[$name]['values'][$label] = $id;
        }
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
        $isArray = is_array($value);
        if ($value === null || ($isArray && !$value)) {
            return null;
        }

        $conditionField = '';
        if (!$this->prepareQuery($model, $query, $fieldName, $conditionField)) {
            return null;
        }

        if (!$isArray) {
            $value = array($value);
        }

        if ($this->operator == 'AND') {
            $conditions = array();
            foreach ($value as $index => $v) {
                $conditions[] = '{' . $conditionField . '} = %' . $index . '%';
            }

            $query->addConditionWithVariables(implode(' ' . $this->operator . ' ', $conditions), $value);
        } else {
            $query->addCondition('{' . $conditionField . '} IN %1%', $value);
        }

        return $value;
    }

}
