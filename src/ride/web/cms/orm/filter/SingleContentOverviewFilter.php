<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\field\HasField;
use ride\library\orm\definition\field\HasManyField;
use ride\library\orm\definition\field\PropertyField;
use ride\library\orm\definition\ModelTable;
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
        $orm = $model->getOrmManager();
        $meta = $model->getMeta();

        $fieldName = $filters[$name]['field'];

        $fieldTokens = explode('.', $fieldName);
        $fieldTokenName = array_shift($fieldTokens);

        $field = $meta->getField($fieldTokenName);
        if ($field instanceof PropertyField) {
            return;
        }

        do {
            $relationModelName = $meta->getRelationModelName($fieldTokenName);

            if ($fieldTokens) {
                $fieldTokenName = array_shift($fieldTokens);
            } else {
                $fieldTokenName = null;
            }

            $relationModel = $orm->getModel($relationModelName);
            $meta = $relationModel->getMeta();

            if ($fieldTokenName) {
                $field = $meta->getField($fieldTokenName);
                if ($field instanceof PropertyField) {
                    return;
                }
            }
        } while ($fieldTokenName);

        $options = array();

        $condition = $field->getOption('scaffold.form.condition');
        if ($condition) {
            $options['condition'] = array($condition);
        }

        $entries = $relationModel->find($options, $locale);
        $options = $relationModel->getOptionsFromEntries($entries);

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

        $orm = $model->getOrmManager();
        $meta = $model->getMeta();

        if ($isArray) {
            $value = array_shift($value);
        }

        $fieldTokens = explode('.', $fieldName);
        $fieldTokenName = array_shift($fieldTokens);

        $field = $meta->getField($fieldTokenName);
        if ($field instanceof PropertyField) {
            return null;
        } elseif ($field instanceof BelongsToField) {
            $conditionField = $fieldTokenName;
        } else {
            $conditionField = $fieldTokenName . '.' . ModelTable::PRIMARY_KEY;
        }

        $numFields = count($fieldTokens);
        $numField = 0;
        $oldFieldTokenName = null;

        while ($numField <= $numFields) {
            if ($numField > 0) {
                $foreignKey = $meta->getRelationForeignKey($fieldTokenName);

                if ($field instanceof BelongsToField) {
                    // $query->addJoin('LEFT', $relationModelName, $relationModelName, '{self.' . $oldFieldTokenName . '} = {' . $oldFieldTokenName . '.id}');

                    $conditionField = $oldFieldTokenName . '.' . $fieldTokenName;
                } elseif ($field instanceof HasManyField) {
                    $relation = $meta->getRelationMeta($fieldTokenName);
                    $foreignKey = $relation->getForeignKey();
                    $linkModelName = $relation->getLinkModelName();
                    // $relation->isRelationWithSelf();
                    // $relation->isHasManyAndBelongsToMany();

                    if ($linkModelName) {
                        $foreignKeyToSelf = $relation->getForeignKeyToSelf($fieldTokenName);

                        $query->addJoin('LEFT', $linkModelName, $linkModelName, '{' . $linkModelName . '.' . $foreignKeyToSelf . '} = {' . $oldFieldTokenName . '.id}');

                        $conditionField = $linkModelName . '.' . $foreignKey;
                    } else {
                        $linkRelationModelName = $meta->getRelationModelName($fieldTokenName);
                        $foreignKeyToSelf = $meta->getRelationForeignKeyToSelf($fieldTokenName);
                        $linkModel = $orm->getModel($linkRelationModelName);
                        $linkMeta = $linkModel->getMeta();

                        $query->addJoin('LEFT', $linkRelationModelName, $linkRelationModelName, '{' . $linkRelationModelName . '.' . $foreignKey . '} = {' . $oldFieldTokenName . '.id}');

                        $conditionField = $linkRelationModelName . '.' . ModelTable::PRIMARY_KEY;
                    }
                }
            }

            $oldMeta = $meta;
            $oldFieldTokenName = $fieldTokenName;

            $relationModelName = $meta->getRelationModelName($fieldTokenName);
            $relationModel = $orm->getModel($relationModelName);
            $meta = $relationModel->getMeta();

            if ($fieldTokens) {
                $fieldTokenName = array_shift($fieldTokens);
                if ($fieldTokenName) {
                    $field = $meta->getField($fieldTokenName);
                }
            } else {
                $fieldTokenName = null;
            }

            $numField++;
        }

        $query->addCondition('{' . $conditionField . '} = %1%', $value);

        return $value;
    }

}
