<?php

namespace pallo\web\cms\form;

use pallo\library\database\manipulation\expression\OrderExpression;
use pallo\library\form\component\AbstractComponent;
use pallo\library\form\FormBuilder;
use pallo\library\i18n\translator\Translator;

use pallo\web\cms\orm\ContentProperties;

/**
 * Form to edit the properties of a content overview widget
 */
class ContentOverviewComponent extends AbstractContentComponent {

    /**
     * Numeric parameters type
     * @var string
     */
    const PARAMETERS_TYPE_NUMERIC = 'numeric';

    /**
     * Named parameters type
     * @var string
     */
    const PARAMETERS_TYPE_NAMED = 'named';

    /**
     * None parameters type
     * @var string
     */
    const PARAMETERS_TYPE_NONE = 'none';

    /**
     * Array with node options
     * @var array
     */
    protected $nodeOptions;

    /**
     * Set the available nodes
     * @param array $nodeOptions
     * @return null
     */
    public function setNodeOptions(array $nodeOptions) {
        $this->nodeOptions = $nodeOptions;
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
        $result['condition'] = $data->getCondition();
        $result['order'] = $data->getOrder();
        $result['pagination-enable'] = $data->isPaginationEnabled();
        $result['pagination-rows'] = $data->getPaginationRows();
        $result['pagination-offset'] = $data->getPaginationOffset();
        $result['pagination-show'] = $data->willShowPagination();
        $result['pagination-ajax'] = $data->useAjaxForPagination();
        $result['title'] = $data->getTitle();
        $result['empty-result-message'] = $data->getEmptyResultMessage();
        $result['more-show'] = $data->willShowMoreLink();
        $result['more-label'] = $data->getMoreLabel();
        $result['more-node'] = $data->getMoreNode();

        $parameters = $data->getParameters();
        if (is_array($parameters)) {
            $result['parameters-type'] = self::PARAMETERS_TYPE_NAMED;
            $result['parameters-number'] = null;
            $result['parameters-name'] = $parameters;
        } elseif (is_numeric($parameters)) {
            $result['parameters-type'] = self::PARAMETERS_TYPE_NUMERIC;
            $result['parameters-number'] = $parameters;
            $result['parameters-name'] = null;
        } else {
            $result['parameters-type'] = self::PARAMETERS_TYPE_NONE;
            $result['parameters-number'] = null;
            $result['parameters-name'] = null;
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

        $result->setCondition($data['condition']);;
        $result->setOrder($data['order']);
        $result->setIsPaginationEnabled($data['pagination-enable']);
        $result->setPaginationRows($data['pagination-rows']);
        $result->setPaginationOffset($data['pagination-offset']);
        $result->setWillShowPagination($data['pagination-show']);
        $result->setUseAjaxForPagination($data['pagination-ajax']);
        $result->setTitle($data['title']);;
        $result->setEmptyResultMessage($data['empty-result-message']);
        $result->setWillShowMoreLink($data['more-show']);
        $result->setMoreLabel($data['more-label']);
        $result->setMoreNode($data['more-node']);

        if ($data['parameters-type'] == self::PARAMETERS_TYPE_NAMED) {
            $parameters = $data['parameters-name'];
        } elseif ($data['parameters-type'] == self::PARAMETERS_TYPE_NUMERIC) {
            $parameters = $data['parameters-number'];
        } else {
            $parameters = null;
        }
        $result->setParameters($parameters);

        return $result;
    }

	/**
	 * Prepares the form builder by adding row definitions
	 * @param pallo\library\html\form\builder\Builder $builder
	 * @param array $options Extra options from the controller
	 * @return null
	 */
	public function prepareForm(FormBuilder $builder, array $options) {
	    parent::prepareForm($builder, $options);

	    if ($this->data) {
	        $modelName = $this->data->getModelName();
	        $recursiveDepth = $this->data->getRecursiveDepth();
	    } else {
	        $modelName = null;
	        $recursiveDepth = 1;
	    }

	    $translator = $options['translator'];

	    $builder->addRow('condition', 'text', array(
	        'label' => $translator->translate('label.condition'),
	        'description' => $translator->translate('label.condition.description'),
	    ));
	    $builder->addRow('order-field', 'select', array(
	        'label' => $translator->translate('label.order.field'),
	        'description' => $translator->translate('label.order.field.description'),
	        'options' => $this->fieldService->getFields($modelName, true, false, $recursiveDepth),
	    ));
	    $builder->addRow('order-direction', 'select', array(
	        'label' => $translator->translate('label.order.direction'),
	        'description' => $translator->translate('label.order.direction.description'),
	        'options' => $this->getOrderDirectionOptions($translator),
	    ));
	    $builder->addRow('order', 'text', array(
	        'label' => $translator->translate('label.order'),
	        'description' => $translator->translate('label.order.description'),
	    ));
	    $builder->addRow('pagination-enable', 'option', array(
	        'label' => $translator->translate('label.pagination.enabled'),
	        'description' => $translator->translate('label.pagination.enabled.description'),
	    ));
	    $builder->addRow('pagination-rows', 'select', array(
	        'label' => $translator->translate('label.pagination.rows'),
	        'description' => $translator->translate('label.pagination.rows.description'),
	        'options' => $this->getNumericOptions(1, 50),
	    ));
	    $builder->addRow('pagination-offset', 'select', array(
	        'label' => $translator->translate('label.pagination.offset'),
	        'description' => $translator->translate('label.pagination.offset.description'),
	        'options' => $this->getNumericOptions(0, 50),
	    ));
	    $builder->addRow('pagination-show', 'option', array(
	        'label' => $translator->translate('label.pagination.show'),
	        'description' => $translator->translate('label.pagination.show.description'),
	    ));
	    $builder->addRow('pagination-ajax', 'option', array(
	        'label' => $translator->translate('label.pagination.ajax'),
	        'description' => $translator->translate('label.pagination.ajax.description'),
	    ));
	    $builder->addRow('parameters-type', 'option', array(
	        'label' => $translator->translate('label.parameters.type'),
	        'description' => $translator->translate('label.parameters.type.description'),
	        'options' => $this->getParametersTypeOptions($translator),
	    ));
	    $builder->addRow('parameters-number', 'select', array(
	        'label' => $translator->translate('label.parameters.number'),
	        'description' => $translator->translate('label.parameters.number.description'),
	        'options' => $this->getNumericOptions(1, 5),
	    ));
	    $builder->addRow('parameters-name', 'collection', array(
	        'type' => 'string',
	        'label' => $translator->translate('label.parameter'),
	    ));
	    $builder->addRow('title', 'string', array(
	        'label' => $translator->translate('label.title'),
	        'description' => $translator->translate('label.title.description'),
	    ));
	    $builder->addRow('empty-result-message', 'wysiwyg', array(
	        'label' => $translator->translate('label.message.result.empty'),
	        'description' => $translator->translate('label.message.result.empty.description'),
	    ));
	    $builder->addRow('more-show', 'option', array(
	        'label' => $translator->translate('label.more.show'),
	        'description' => $translator->translate('label.more.show.description'),
	    ));
	    $builder->addRow('more-node', 'select', array(
	        'label' => $translator->translate('label.more.node'),
	        'description' => $translator->translate('label.more.node.description'),
	        'options' => $this->nodeOptions,
	    ));
	    $builder->addRow('more-label', 'string', array(
	        'label' => $translator->translate('label.more.label'),
	        'description' => $translator->translate('label.more.label.description'),
	    ));
	}

	/**
	 * Gets the options for the order direction
	 * @param pallo\library\i18n\translator\Translator $translator
	 * @return array
	 */
	private function getOrderDirectionOptions(Translator $translator) {
	    return array(
	        OrderExpression::DIRECTION_ASC => $translator->translate('label.order.direction.asc'),
	        OrderExpression::DIRECTION_DESC => $translator->translate('label.order.direction.desc'),
	    );
	}

	/**
	 * Gets the options for the parameters type
	 * @param pallo\library\i18n\translator\Translator $translator
	 * @return array
	 */
	private function getParametersTypeOptions(Translator $translator) {
	    return array(
	        self::PARAMETERS_TYPE_NONE => $translator->translate('label.parameters.none'),
	        self::PARAMETERS_TYPE_NUMERIC => $translator->translate('label.parameters.numeric'),
	        self::PARAMETERS_TYPE_NAMED => $translator->translate('label.parameters.type.named'),
	    );
	}

}