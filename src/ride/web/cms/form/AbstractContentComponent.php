<?php

namespace ride\web\cms\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;

use ride\web\cms\orm\ContentProperties;
use ride\web\cms\orm\ContentService;
use ride\web\cms\orm\FieldService;

/**
 * Form to edit ORM data
 */
abstract class AbstractContentComponent extends AbstractComponent {

    /**
     * Instance of the content service
     * @var \ride\web\cms\orm\ContentService
     */
    protected $contentService;

    /**
     * Instance of the field service
     * @var \ride\web\cms\orm\FieldService
     */
    protected $fieldService;

    /**
     * Boolean to check is permission is granted or not
     * @var Boolean
     */
    protected $isPermissionGranted;

    /**
     * Instance of the content data
     * @var unknown
     */
    protected $data;

    /**
     * Available templates for the widget
     * @var array
     */
    protected $templates;

    /**
     * Available view processors
     * @var array
     */
    protected $viewProcessors;

    /**
     * Constructs a new content properties form component
     * @param \ride\web\cms\orm\FieldService $fieldService
     * @return null
     */
    public function __construct(FieldService $fieldService, $isPermissionGranted) {
        $this->fieldService = $fieldService;
        $this->isPermissionGranted = $isPermissionGranted;
    }

    /**
     * Sets the content service to this component
     * @param \ride\web\cms\orm\ContentService $contentService
     * @return null
     */
    public function setContentService(ContentService $contentService) {
        $this->contentService = $contentService;
    }

    /**
     * Sets the available views
     * @param array $views
     * @return null
     */
    public function setTemplates(array $templates) {
        $this->templates = $templates;
    }

    /**
     * Sets the view processors
     * @param array $views
     * @return null
     */
    public function setViewProcessors(array $viewProcessors) {
        $this->viewProcessors = $viewProcessors;
    }

    /**
     * Gets the name of this component, used when this component is the root
     * of the form to be build
     * @return string
     */
    public function getName() {
        return 'form-content-properties';
    }

    /**
     * Gets the data type for the data of this form component
     * @return string|null A string for a data class, null for an array
     */
    public function getDataType() {
        return 'ride\\web\\cms\\orm\\ContentProperties';
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

        $this->data = $data;

        return array(
            'model' => $data->getModelName(),
            'include-unlocalized' => $data->getIncludeUnlocalized(),
            'parameters-none' => $data->getNoParametersAction(),
            'template' => $data->getTemplate(),
            'view-processor' => $data->getViewProcessor(),
            'format-title' => $data->getContentTitleFormat(),
            'format-teaser' => $data->getContentTeaserFormat(),
            'format-image' => $data->getContentImageFormat(),
            'format-date' => $data->getContentDateFormat(),
        );
    }

    /**
     * Parse the form values to data of the component
     * @param array $data
     * @return mixed $data
     */
    public function parseGetData(array $data) {
        if (!$this->data) {
            $this->data = new ContentProperties();
        }
        if ($this->isPermissionGranted) {
            $this->data->setModelName($data['model']);
            $this->data->setIncludeUnlocalized($data['include-unlocalized']);
            $this->data->setNoParametersAction($data['parameters-none']);
            $this->data->setTemplate($data['template']);
            $this->data->setViewProcessor($data['view-processor']);
            $this->data->setContentTitleFormat($data['format-title']);
            $this->data->setContentTeaserFormat($data['format-teaser']);
            $this->data->setContentImageFormat($data['format-image']);
            $this->data->setContentDateFormat($data['format-date']);
        }

        return $this->data;
    }

    /**
     * Prepares the form builder by adding row definitions
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options Extra options from the controller
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $data = $options['data'];

        $translator = $options['translator'];

        if ($data) {
            $modelName = $data->getModelName();
        } else {
            $modelName = null;
        }
        if ($this->isPermissionGranted){
            $builder->addRow('model', 'select', array(
                'label' => $translator->translate('label.model'),
                'description' => $translator->translate('label.model.description'),
                'options' => $this->getModelOptions(),
            ));
            $builder->addRow('include-unlocalized', 'boolean', array(
                'label' => $translator->translate('label.unlocalized'),
                'description' => $translator->translate('label.unlocalized.description'),
            ));
            $builder->addRow('template', 'select', array(
                'label' => $translator->translate('label.template'),
                'description' => $translator->translate('label.template.widget.description'),
                'options' => $this->templates,
            ));
            $builder->addRow('view-processor', 'select', array(
                'label' => $translator->translate('label.processor.view'),
                'description' => $translator->translate('label.processor.view.description'),
                'options' => $this->viewProcessors,
            ));
            $builder->addRow('format-title', 'string', array(
                'label' => $translator->translate('label.format.title'),
                'description' => $translator->translate('label.format.title.description'),
                'filters' => array(
                    'trim' => array(),
                ),
            ));
            $builder->addRow('format-teaser', 'string', array(
                'label' => $translator->translate('label.format.teaser'),
                'description' => $translator->translate('label.format.teaser.description'),
                'filters' => array(
                    'trim' => array(),
                ),
            ));
            $builder->addRow('format-image', 'string', array(
                'label' => $translator->translate('label.format.image'),
                'description' => $translator->translate('label.format.image.description'),
                'filters' => array(
                    'trim' => array(),
                ),
            ));
            $builder->addRow('format-date', 'string', array(
                'label' => $translator->translate('label.format.date'),
                'description' => $translator->translate('label.format.date.description'),
                'filters' => array(
                    'trim' => array(),
                ),
            ));
            $builder->addRow('parameters-none', 'select', array(
                'label' => $translator->translate('label.parameters.none'),
                'description' => $translator->translate('label.parameters.none.description'),
                'options' => $this->getParametersNoneOptions($translator),
            ));

        }
    }

    /**
     * Gets the options for the model field
     * @return array Array with the name of the model as key and as value
     */
    protected function getModelOptions() {
        $models = $this->fieldService->getOrm()->getModels(true);

        ksort($models);

        $options = array();
        foreach ($models as $modelName => $model) {
            $options[$modelName] = $modelName;
        }

        return $options;
    }

    /**
     * Gets numeric options
     * @param integer $minimum
     * @param integer $maximum
     * @return array
     */
    protected function getNumericOptions($minimum, $maximum) {
        $options = array();
        for ($i = $minimum; $i <= $maximum; $i++) {
            $options[$i] = $i;
        }

        return $options;
    }

    /**
     * Gets the options for the parameters type
     * @param \ride\library\i18n\translator\Translator $translator
     * @return array
     */
    protected function getParametersNoneOptions(Translator $translator) {
        return array(
            ContentProperties::NONE_404 => $translator->translate('label.parameters.none.404'),
            ContentProperties::NONE_IGNORE => $translator->translate('label.parameters.none.ignore'),
        );
    }

    /**
     * Gets the options for the content mappers
     * @return array
     */
    protected function getContentMapperOptions($modelName) {
        $contentMappers = $this->contentService->getContentMappersForType($modelName);
        foreach ($contentMappers as $id => $contentMapper) {
            $contentMappers[$id] = $id;
        }

        $contentMappers = array('' => '---') + $contentMappers;

        return $contentMappers;
    }

}
