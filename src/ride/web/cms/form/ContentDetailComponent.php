<?php

namespace ride\web\cms\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\OrmManager;

use ride\web\cms\orm\ContentProperties;

/**
 * Form to edit the properties of a content detail widget
 */
class ContentDetailComponent extends AbstractContentComponent {

    /**
     * Parse the data to form values for the component rows
     * @param mixed $data
     * @return array $data
     */
    public function parseSetData($data) {
        if (!$data) {
            return null;
        }

        $result = parent::parseSetData($data);
        $result['field-id'] = $data->getIdField();
        $result['title'] = $data->getTitle();

        return $result;
    }

    /**
     * Parse the form values to data of the component
     * @param array $data
     * @return mixed $data
     */
    public function parseGetData(array $data) {
        $result = parent::parseGetData($data);
        $result->setIdField($data['field-id']);
        $result->setTitle($data['title']);

        return $result;
    }

    /**
     * Prepares the form builder by adding row definitions
     * @param ride\library\html\form\builder\Builder $builder
     * @param array $options Extra options from the controller
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        parent::prepareForm($builder, $options);

        $translator = $options['translator'];

        if ($this->data) {
            $modelName = $this->data->getModelName();
        } else {
            $modelName = null;
        }

        $builder->addRow('field-id', 'select', array(
            'label' => $translator->translate('label.field.id'),
            'description' => $translator->translate('label.field.id.description'),
            'options' => $this->fieldService->getUniqueFields($modelName),
        ));
        $builder->addRow('title', 'boolean', array(
            'label' => $translator->translate('label.title'),
            'description' => $translator->translate('label.title.update.description'),
        ));
    }

}