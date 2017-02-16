<?php

namespace ride\web\cms\form;

use ride\library\form\FormBuilder;

/**
 * Form to edit the properties of a content entry widget
 */
class ContentEntryComponent extends AbstractContentComponent {

    /**
     * Code of the locale of the entries
     * @var string
     */
    protected $locale;

    /**
     * Sets the locale for the entries
     * @param string $locale Code of the locale
     * @return null
     */
    public function setLocale($locale) {
        $this->locale = $locale;
    }

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
        $result['entry'] = $data->getEntryId();
        $result['title'] = $data->getTitle();
        $result['breadcrumb'] = $data->getBreadcrumb();
        if ($this->isPermissionGranted) {
            $result['content-mapper'] = $data->getContentMapper();
        }

        return $result;
    }

    /**
     * Parse the form values to data of the component
     * @param array $data
     * @return mixed $data
     */
    public function parseGetData(array $data) {
        $result = parent::parseGetData($data);
        $result->setEntryId($data['entry']);
        $result->setTitle($data['title']);
        $result->setBreadcrumb($data['breadcrumb']);
        if ($this->isPermissionGranted) {
            $result->setContentMapper($data['content-mapper']);
        }

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
        $orm = $this->fieldService->getOrm();

        $modelName = $data->getModelName();
        if (!$modelName && $this->isPermissionGranted) {
            $modelOptions = $builder->getRow('model')->getOption('options');
            $modelName = reset($modelOptions);
        }

        $entryOptions = array('' => '---');

        if ($modelName) {
            $model = $orm->getModel($modelName);

            $entries = $model->find(null, $this->locale);
            $entryOptions += $model->getOptionsFromEntries($entries);
        }

        $builder->addRow('entry', 'select', array(
            'label' => $translator->translate('label.entry'),
            'description' => $translator->translate('label.entry.description'),
            'options' => $entryOptions,
        ));
        $builder->addRow('breadcrumb', 'boolean', array(
            'label' => $translator->translate('label.breadcrumb.add'),
            'description' => $translator->translate('label.breadcrumb.add.description'),
        ));
        if ($this->isPermissionGranted) {
            $builder->addRow('content-mapper', 'select', array(
                'label' => $translator->translate('label.content.mapper.select'),
                'description' => $translator->translate('label.content.mapper.select.description'),
                'options' => $this->getContentMapperOptions($modelName),
            ));
        }
        $builder->addRow('title', 'boolean', array(
            'label' => $translator->translate('label.title'),
            'description' => $translator->translate('label.title.content.description'),
        ));
    }

}
