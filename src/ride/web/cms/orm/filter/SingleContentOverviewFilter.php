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
     * @param ride\library\orm\model\Model $model
     * @param string $field Name of the filter field
     * @param string $locale Code of the current locale
     * @param string $baseUrl Base URL
     * @return null
     */
    public function setVariables(array &$filters, Model $model, $field, $locale, $baseUrl) {
        $relationModel = $model->getMeta()->getRelationModelName($field);
        $relationModel = $model->getOrmManager()->getModel($relationModel);

        $data = $relationModel->getDataList($locale);

        $filters[$field]['options'] = $data;
        $filters[$field]['urls'] = array();
        $filters[$field]['values'] = array();
        $filters[$field]['empty'] = $this->getUrl($baseUrl, $filters, $field, null);

        foreach ($data as $id => $label) {
            $filters[$field]['urls'][$label] = $this->getUrl($baseUrl, $filters, $field, $id);
            $filters[$field]['values'][$label] = $id;
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
     * @param ride\library\orm\model\Model $model
     * @param string $field Name of the filter field
     * @return array Label as key, URL as value
    */
    public function getUrls(Model $model, $field, $baseUrl) {
        $relationModel = $model->getMeta()->getRelationModelName($fieldName);
        $relationModel = $model->getOrmManager()->getModel($relationModel);

        $result = array();

        $data = $relationModel->getDataList($locale);
        foreach ($data as $id => $label) {
            $result[$label] = $id;
        }

        return $result;
    }

    /**
     * Applies the filter to the provided query
     * @param ride\library\orm\model\Model $model
     * @param ride\library\orm\query\ModelQuery $query
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