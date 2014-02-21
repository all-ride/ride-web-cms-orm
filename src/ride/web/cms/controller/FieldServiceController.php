<?php

namespace ride\web\cms\controller;

use ride\web\base\controller\AbstractController;
use ride\web\cms\orm\FieldService;

/**
 * Controller to expose the field service
 */
class FieldServiceController extends AbstractController {

	/**
	 * Action to get the select field options of the provided model
	 * @param ride\web\cms\orm\FieldService $fieldService
	 * @param string $model Name of the model
	 * @return null
	 */
    public function selectFieldsAction(FieldService $fieldService, $model) {
        if ($model) {
        	$fields = $fieldService->getFields($model);
        } else {
            $fields = array();
        }

        $this->setJsonView(array('fields' => $fields));
    }

    /**
     * Action to get the order field option of the provided model
	 * @param ride\web\cms\orm\FieldService $fieldService
     * @param string $model Name of the model
     * @param integer $recursiveDepth Recursive depth
     * @return null
     */
    public function orderFieldsAction(FieldService $fieldService, $model, $recursiveDepth = 1) {
        if ($model) {
            $fields = $fieldService->getFields($model, true, false, $recursiveDepth);
        } else {
            $fields = array();
        }

        $this->setJsonView(array('fields' => $fields));
    }

	/**
	 * Action to get the unique field options of the provided model
	 * @param ride\web\cms\orm\FieldService $fieldService
	 * @param string $model Name of the model
	 * @return null
	 */
    public function uniqueFieldsAction(FieldService $fieldService, $model) {
        if ($model) {
        	$fields = $fieldService->getUniqueFields($model);
        } else {
            $fields = array();
        }

        $this->setJsonView(array('fields' => $fields));
    }

	/**
	 * Action to get the relation field options of the provided model
	 * @param ride\web\cms\orm\FieldService $fieldService
	 * @param string $model Name of the model
	 * @return null
	 */
    public function relationFieldsAction(FieldService $fieldService, $model) {
        if ($model) {
        	$fields = $fieldService->getRelationFields($model);
        } else {
            $fields = array();
        }

        $this->setJsonView(array('fields' => $fields));
    }

}