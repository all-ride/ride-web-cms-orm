<?php

namespace ride\web\cms\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\orm\OrmManager;

use ride\web\cms\orm\ContentProperties;
use ride\web\cms\orm\FieldService;

/**
 * Form to edit ORM data
 */
abstract class AbstractContentComponent extends AbstractComponent {

    /**
     * Instance of the field service
     * @var ride\web\cms\orm\FieldService
     */
    protected $fieldService;

    /**
     * Instance of the content data
     * @var unknown
     */
    protected $data;

    /**
     * Model of this form component
     * @var array
     */
    protected $views;

    /**
     * Constructs a new content properties form component
     * @param ride\web\cms\orm\FieldService $fieldService
     * @return null
     */
    public function __construct(FieldService $fieldService) {
        $this->fieldService = $fieldService;
    }

    /**
     * Sets the available views
     * @param array $views
     * @return null
     */
    public function setViews(array $views) {
        $this->views = $views;
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
            'fields' => $data->getModelFields(),
            'recursive-depth' => $data->getRecursiveDepth(),
            'include-unlocalized' => $data->getIncludeUnlocalized(),
            'parameters-none' => $data->getNoParametersAction(),
            'view' => $data->getView(),
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

        $this->data->setModelName($data['model']);
        $this->data->setRecursiveDepth($data['recursive-depth']);
        $this->data->setIncludeUnlocalized($data['include-unlocalized']);
        $this->data->setNoParametersAction($data['parameters-none']);
        $this->data->setView($data['view']);
        $this->data->setContentTitleFormat($data['format-title']);
        $this->data->setContentTeaserFormat($data['format-teaser']);
        $this->data->setContentImageFormat($data['format-image']);
        $this->data->setContentDateFormat($data['format-date']);

        if ($data['fields']) {
            $this->data->setModelFields($data['fields']);
        }

        return $this->data;
    }

	/**
	 * Prepares the form builder by adding row definitions
	 * @param ride\library\html\form\builder\Builder $builder
	 * @param array $options Extra options from the controller
	 * @return null
	 */
	public function prepareForm(FormBuilder $builder, array $options) {
	    $translator = $options['translator'];

	    if ($this->data) {
            $modelName = $this->data->getModelName();
	    } else {
	        $modelName = null;
	    }

	    $builder->addRow('model', 'select', array(
	        'label' => $translator->translate('label.model'),
	        'description' => $translator->translate('label.model.description'),
	    	'options' => $this->getModelOptions(),
	    ));
	    $builder->addRow('fields', 'select', array(
	        'label' => $translator->translate('label.fields'),
	        'description' => $translator->translate('label.fields.description'),
	    	'options' => $this->fieldService->getFields($modelName),
	    	'multiple' => true,
	    ));
	    $builder->addRow('recursive-depth', 'select', array(
	        'label' => $translator->translate('label.depth.recursive'),
	        'description' => $translator->translate('label.depth.recursive.description'),
	    	'options' => $this->getNumericOptions(0, 5),
	    ));
	    $builder->addRow('include-unlocalized', 'boolean', array(
	        'label' => $translator->translate('label.unlocalized'),
	        'description' => $translator->translate('label.unlocalized.description'),
	    ));
	    $builder->addRow('view', 'select', array(
	        'label' => $translator->translate('label.view'),
	        'description' => $translator->translate('label.view.description'),
	    	'options' => $this->getViewOptions($translator),
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

	/**
	 * Gets the options for the model field
	 * @return array Array with the name of the model as key and as value
	 */
	protected function getModelOptions() {
	    $models = $this->fieldService->getOrm()->getModels(true);

	    ksort($models);

	    $options = array();
	    foreach ($models as $modelName => $model) {
	        if (!$model->getMeta()->getOption('scaffold.expose')) {
    	        unset($options[$modelName]);
	        } else {
    	        $options[$modelName] = $modelName;
	        }
	    }

	    return $options;
	}

	/**
	 * Gets the options for the view type
	 * @param ride\library\i18n\translator\Translator $translator
	 * @return array
	 */
	protected function getViewOptions(Translator $translator) {
	    $views = array();

	    foreach ($this->views as $name => $class) {
	        $views[$name] = $translator->translate('label.view.' . $name);
	    }

	    return $views;
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
	 * @param ride\library\i18n\translator\Translator $translator
	 * @return array
	 */
	protected function getParametersNoneOptions(Translator $translator) {
	    return array(
	        ContentProperties::NONE_404 => $translator->translate('label.parameters.none.404'),
	        ContentProperties::NONE_IGNORE => $translator->translate('label.parameters.none.ignore'),
	    );
	}

}