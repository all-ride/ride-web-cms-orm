<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Implementation for a single value filter for the content overview widget
 */
class SingleContentOverviewFilter implements ContentOverviewFilter {

    /**
     * Gets the available options for the filter
     * @param array $filters Filters to update
     * @param \ride\library\orm\model\Model $model
     * @param string $fieldName Name of the filter field
     * @param string $locale Code of the current locale
     * @param string $baseUrl Base URL
     * @return null
     */
    public function setVariables(array &$filters, Model $model, $fieldName, $locale, $baseUrl) {
        $meta = $model->getMeta();

        $relationModelName = $meta->getRelationModelName($fieldName);
        $relationModel = $model->getOrmManager()->getModel($relationModelName);

        $options = array();

        $field = $meta->getField($fieldName);
        $condition = $field->getOption('scaffold.form.condition');
        if ($condition) {
            $options['condition'] = array($condition);
        }

        $entries = $relationModel->find($options, $locale);
        $options = $relationModel->getOptionsFromEntries($entries);

        $filters[$fieldName]['options'] = $options;
        $filters[$fieldName]['urls'] = array();
        $filters[$fieldName]['values'] = array();
        $filters[$fieldName]['empty'] = $this->getUrl($baseUrl, $filters, $fieldName, null);

        foreach ($options as $id => $label) {
            $filters[$fieldName]['urls'][$label] = $this->getUrl($baseUrl, $filters, $fieldName, $id);
            $filters[$fieldName]['values'][$label] = $id;
        }
    }

    /**
     * Gets the URL for the provided filter
     * @param string $baseUrl
     * @param array $filters
     * @param string $field
     * @param string $value
     * @return string
     */
    protected function getUrl($baseUrl, $filters, $field, $value) {
        $query = array();

        foreach ($filters as $filterName => $filter) {
            if ($filterName == $field) {
                if ($value && $filter['value'] != $value) {
                    $query[$filterName] = $filterName . '=' . $value;
                }
            } elseif (isset($filter['value'])) {
                if (is_array($filter['value'])) {
                    foreach ($filter['value'] as $filterValue) {
                        $query[$filterName] = $filterName . '[]=' . $filterValue;
                    }
                } else {
                    $query[$filterName] = $filterName . '=' . $filter['value'];
                }
            }
        }

        if (!$query) {
            return $baseUrl;
        } else {
            return $baseUrl . '?' . implode('&', $query);
        }
    }

    /**
     * Gets the available options for the filter
     * @param \ride\library\orm\model\Model $model
     * @param string $field Name of the filter field
     * @return array Label as key, id as value
    */
    public function getUrls(Model $model, $field, $baseUrl) {
        $relationModel = $model->getMeta()->getRelationModelName($fieldName);
        $relationModel = $model->getOrmManager()->getModel($relationModel);

        $entries = $relationModel->find(null, $locale);
        $options = $relationModel->getOptionsFromEntries($entries);

        return array_flip($options);
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

        if ($isArray) {
            $value = array_shift($value);
        }

        $field = $model->getMeta()->getField($fieldName);
        if ($field instanceof BelongsToField) {
            $query->addCondition('{' . $fieldName . '} = %1%', $value);
        } else {
            $query->addCondition('{' . $fieldName . '.' . ModelTable::PRIMARY_KEY . '} = %1%', $value);
        }

        return $value;
    }

}
