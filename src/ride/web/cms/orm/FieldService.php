<?php

namespace ride\web\cms\orm;

use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\field\HasField;
use ride\library\orm\definition\field\PropertyField;
use ride\library\orm\definition\field\RelationField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\OrmManager;

/**
 * Service to query a model for fields
 */
class FieldService {

    /**
     * Constructs a new field service
     * @param ride\library\orm\OrmManager $orm
     */
    public function __construct(OrmManager $orm) {
        $this->orm = $orm;
    }

    /**
     * Gets the ORM
     * @return ride\library\orm\OrmManager
     */
    public function getOrm() {
        return $this->orm;
    }

    /**
     * Gets the fields of a model as options for a form field
     * @param string $model Name of the selected model
     * @param boolean $includeRelationFields
     * @param boolean $includeHasFields
     * @return array Array with tne name of the field as key and as value
     */
    public function getFields($model, $includeRelationFields = false, $includeHasFields = false, $recursiveDepth = 1) {
        if ($includeRelationFields) {
            $options = array('' => '---');
        } else {
            $options = array();
        }

        if (!$model) {
            return $options;
        }

        $model = $this->orm->getModel($model);
        $meta = $model->getMeta();
        $fields = $meta->getFields();

        foreach ($fields as $fieldName => $field) {
            if (!$includeRelationFields || $field instanceof PropertyField) {
                $options[$fieldName] = $fieldName;

                continue;
            }

            if (!($includeHasFields || $field instanceof BelongsToField)) {
                continue;
            }

            if ($recursiveDepth != '1') {
                $options[$fieldName] = $fieldName;

                continue;
            }

            $relationModel = $model->getRelationModel($fieldName);
            $relationMeta = $relationModel->getMeta();
            $relationFields = $relationMeta->getFields();

            foreach ($relationFields as $relationFieldName => $relationField) {
                if (!$includeHasFields && $relationField instanceof HasField) {
                    continue;
                }

                $name = $fieldName . '.' . $relationFieldName;
                $options[$name] = $name;
            }
        }

        return $options;
    }

    /**
     * Gets the unique properties of a model as options for a form field
     * @param string $model Name of the selected model
     * @return array
     */
    public function getUniqueFields($model) {
        $options = array();

        if (!$model) {
            return $options;
        }

        $model = $this->orm->getModel($model);
        $meta = $model->getMeta();

        $options[ModelTable::PRIMARY_KEY] = ModelTable::PRIMARY_KEY;

        $fields = $meta->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field instanceof PropertyField || !$field->isUnique()) {
                continue;
            }

            $options[$fieldName] = $fieldName;
        }

        return $options;
    }

    /**
     * Gets the relation fields of a model as options for a form field
     * @param string $model Name of the selected model
     * @return array
     */
    public function getRelationFields($model) {
        $options = array();

        if (!$model) {
            return $options;
        }

        $model = $this->orm->getModel($model);
        $meta = $model->getMeta();


        $fields = $meta->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field instanceof RelationField) {
                continue;
            }

            $options[$fieldName] = $fieldName;
        }

        return $options;
    }

}