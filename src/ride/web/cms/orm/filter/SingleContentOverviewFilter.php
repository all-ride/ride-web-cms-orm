<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Implementation for a single value filter for the content overview widget
 */
class SingleContentOverviewFilter extends AbstractContentOverviewFilter {

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

        foreach ($options as $id => $label) {
            $filters[$name]['urls'][$label] = $this->getUrl($baseUrl, $filters, $name, $id);
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
        if ($value === null || $value === '' || ($isArray && !$value)) {
            return null;
        }

        $conditionField = '';
        if (!$this->prepareQuery($model, $query, $fieldName, $conditionField)) {
            return null;
        }

        if ($isArray) {
            $value = array_shift($value);
        }

        $query->addCondition('{' . $conditionField . '} = %1%', $value);

        return $value;
    }

}
