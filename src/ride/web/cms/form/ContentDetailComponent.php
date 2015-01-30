<?php

namespace ride\web\cms\form;

use ride\library\form\FormBuilder;

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
        $result['primary'] = $data->isPrimaryMapper();
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
        $result->setIsPrimaryMapper($data['primary']);

        return $result;
    }

    /**
     * Prepares the form builder by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options Extra options from the controller
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $data = $options['data'];

        parent::prepareForm($builder, $options);

        $translator = $options['translator'];

        $modelName = $data->getModelName();
        if (!$modelName) {
            $modelOptions = $builder->getRow('model')->getOption('options');
            $modelName = reset($modelOptions);
        }

        $fieldIdOptions = $this->fieldService->getUniqueFields($modelName);

        $builder->addRow('field-id', 'select', array(
            'label' => $translator->translate('label.field.id'),
            'description' => $translator->translate('label.field.id.description'),
            'options' => $fieldIdOptions,
        ));
        $builder->addRow('primary', 'boolean', array(
            'label' => $translator->translate('label.content.mapper.primary'),
            'description' => $translator->translate('label.content.mapper.primary.description'),
        ));
        $builder->addRow('title', 'boolean', array(
            'label' => $translator->translate('label.title'),
            'description' => $translator->translate('label.title.content.description'),
        ));
    }

}
