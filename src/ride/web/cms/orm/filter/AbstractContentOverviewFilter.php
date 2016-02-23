<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\field\HasManyField;
use ride\library\orm\definition\field\ModelField;
use ride\library\orm\definition\field\PropertyField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Abstract implementation for a filter of the content overview widget
 */
abstract class AbstractContentOverviewFilter implements ContentOverviewFilter {

    /**
     * Gets the URL for the provided filter value
     * @param string $baseUrl Base URL
     * @param array $filters All filters with the name as key and properties as
     * value
     * @param string $name Name of the filter for which a value is provided
     * @param string $value Value for the filter of the provided name
     * @return string Url for the provided filter value
     */
    protected function getUrl($baseUrl, $filters, $name, $value) {
        $query = array();

        foreach ($filters as $filterName => $filter) {
            if ($filterName == $name) {
                if ($filter['value'] != $value) {
                    $queryValue = $this->getQueryValue($filter, $filterName, $value);
                    if ($queryValue) {
                        $query[$filterName] = $queryValue;
                    }
                }
            } elseif (isset($filter['value'])) {
                $queryValue = $this->getQueryValue($filter, $filterName, $filter['value']);
                if ($queryValue) {
                    $query[$filterName] = $queryValue;
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
     * Gets the query string for the provided filter
     * @param array $filter Filter properties
     * @param string $name Name of the filter
     * @param mixed $value Value for the filter
     * @return string Query string for the provided filter
     */
    protected function getQueryValue($filter, $name, $value) {
        $query = null;

        if ($value === '' || $value === null) {
            return $query;
        } elseif (is_array($value)) {
            $query = '';
            foreach ($value as $filterValue) {
                $query .= ($query ? '&' : '') . $name . '[]=' . $filterValue;
            }
        } else {
            $query = $name . '=' . $value;
        }

        return $query;
    }

    /**
     * Gets the available fiter entries for the provided field
     * @param \ride\library\orm\definition\field\ModelField $field
     * @param \ride\library\orm\model\Model $relationModel
     * @param string $locale Code of the locale
     * @return array Array with the id of the entry as key and the entry
     * instance as value
     */
    protected function getEntries($field, $relationModel, $locale) {
        $options = array();

        $condition = $field->getOption('scaffold.form.condition');
        if ($condition) {
            $options['condition'] = array($condition);
        }

        $vocabulary = $field->getOption('taxonomy.vocabulary');
        if ($vocabulary && $relationModel->getName() == 'TaxonomyTerm') {
            if (is_numeric($vocabulary)) {
                $options['condition'] = array('{vocabulary.id} = "' . $vocabulary . '"');
            } else {
                $options['condition'] = array('{vocabulary.slug} = "' . $vocabulary . '"');
            }
        }

        return $relationModel->find($options, $locale);
    }


    /**
     * Parses the relation field of the filter
     * @param \ride\library\orm\model\Model $model
     * @param string $fieldName
     * @param \ride\library\orm\definition\field\ModelField $field
     * @param \ride\library\orm\model\Model $relationModel
     * @return boolean
     */
    protected function parseRelationField(Model $model, $fieldName, ModelField &$field = null, Model &$relationModel = null) {
        $orm = $model->getOrmManager();
        $meta = $model->getMeta();

        $fieldTokens = explode('.', $fieldName);
        $fieldTokenName = array_shift($fieldTokens);

        $field = $meta->getField($fieldTokenName);
        if ($field instanceof PropertyField) {
            return false;
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
                    return false;
                }
            }
        } while ($fieldTokenName);

        return true;
    }

    /**
     * Prepares the query for the condition
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\orm\query\ModelQuery $query
     * @param string $fieldName Name of the filter field
     * @param string $conditionField Name of the model field to query on
     * @return boolean True when conditionField is set, false otherwise
     */
    protected function prepareQuery(Model $model, ModelQuery $query, $fieldName, &$conditionField) {
        $orm = $model->getOrmManager();
        $meta = $model->getMeta();

        $fieldTokens = explode('.', $fieldName);
        $fieldTokenName = array_shift($fieldTokens);

        $field = $meta->getField($fieldTokenName);
        if ($field instanceof PropertyField) {
            return false;
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

        return true;
    }

}
