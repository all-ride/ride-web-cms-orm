<?php

namespace ride\web\cms\form;

use ride\library\database\manipulation\expression\OrderExpression;
use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;

use ride\web\cms\orm\ContentProperties;
use ride\web\cms\orm\ContentService;

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
     * Instance of the content service
     * @var \ride\web\cms\orm\ContentService
     */
    protected $contentService;

    /**
     * Array with node options
     * @var array
     */
    protected $nodeOptions;

    /**
     * Sets the content service to this component
     * @param \ride\web\cms\orm\ContentService $contentService
     * @return null
     */
    public function setContentService(ContentService $contentService) {
        $this->contentService = $contentService;
    }

    /**
     * Set the available nodes
     * @param array $nodeOptions
     * @return null
     */
    public function setNodeOptions(array $nodeOptions) {
        $this->nodeOptions = $nodeOptions;
    }

    /**
     * Sets the available filters
     * @param array $contentOverviewFilters Machine name as key, label as value
     * @return null
     * @see ride\web\cms\orm\filter\ContentOverviewFilter
     */
    public function setContentOverviewFilters(array $contentOverviewFilters) {
        $this->contentOverviewFilters = $contentOverviewFilters;
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
        $result['filters'] = $data->getFilters();
        $result['order'] = $data->getOrder();
        $result['pagination-enable'] = $data->isPaginationEnabled();
        $result['pagination-rows'] = $data->getPaginationRows();
        $result['pagination-offset'] = $data->getPaginationOffset();
        $result['pagination-show'] = $data->willShowPagination();
        $result['pagination-ajax'] = $data->useAjaxForPagination();
        $result['content-mapper'] = $data->getContentMapper();
        $result['title'] = $data->getTitle();
        $result['empty-result-view'] = $data->hasEmptyResultView();
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
        $result->setFilters($data['filters']);
        $result->setOrder($data['order']);
        $result->setIsPaginationEnabled($data['pagination-enable']);
        $result->setPaginationRows($data['pagination-rows']);
        $result->setPaginationOffset($data['pagination-offset']);
        $result->setWillShowPagination($data['pagination-show']);
        $result->setUseAjaxForPagination($data['pagination-ajax']);
        $result->setContentMapper($data['content-mapper']);
        $result->setTitle($data['title']);
        $result->setHasEmptyResultView($data['empty-result-view']);
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
     * @param \ride\library\form\FormBuilder $builder
     * @param array $options Extra options from the controller
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $data = $options['data'];

        parent::prepareForm($builder, $options);

        $modelName = $data->getModelName();
        if (!$modelName) {
            $modelOptions = $builder->getRow('model')->getOption('options');
            $modelName = reset($modelOptions);
        }

        $fieldIdOptions = $this->fieldService->getUniqueFields($modelName);

        $translator = $options['translator'];

        $filterComponent = new ContentOverviewFilterComponent();
        $filterComponent->setFields($this->fieldService->getFields($modelName, true, true, 2));
        $filterComponent->setTypes($this->contentOverviewFilters);

        $builder->addRow('condition', 'text', array(
            'label' => $translator->translate('label.condition'),
            'description' => $translator->translate('label.condition.description'),
        ));
        $builder->addRow('order-field', 'select', array(
            'label' => $translator->translate('label.order.field'),
            'description' => $translator->translate('label.order.field.description'),
            'options' => $this->fieldService->getFields($modelName, true, false, 1),
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
        $builder->addRow('content-mapper', 'select', array(
            'label' => $translator->translate('label.content.mapper.select'),
            'description' => $translator->translate('label.content.mapper.select.description'),
            'options' => $this->getContentMapperOptions($modelName),
        ));
        $builder->addRow('title', 'string', array(
            'label' => $translator->translate('label.title'),
            'description' => $translator->translate('label.title.query.description'),
        ));
        $builder->addRow('filters', 'collection', array(
            'type' => 'component',
            'options' => array(
                'component' => $filterComponent,
            ),
            'label' => $translator->translate('label.filters'),
            'description' => $translator->translate('label.filters.exposed.description'),
        ));
        $builder->addRow('empty-result-view', 'boolean', array(
            'label' => $translator->translate('label.result.empty'),
            'description' => $translator->translate('label.view.result.empty.description'),
            'attributes' => array(
                'data-toggle-dependant' => 'option-empty-result',
            ),
        ));
        $builder->addRow('empty-result-message', 'wysiwyg', array(
            'label' => $translator->translate('label.message'),
            'description' => $translator->translate('label.message.result.empty.description'),
            'attributes' => array(
                'class' => 'option-empty-result option-empty-result-1',
            ),
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
     * @param \ride\library\i18n\translator\Translator $translator
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
     * @param \ride\library\i18n\translator\Translator $translator
     * @return array
     */
    private function getParametersTypeOptions(Translator $translator) {
        return array(
            self::PARAMETERS_TYPE_NONE => $translator->translate('label.parameters.none'),
            self::PARAMETERS_TYPE_NUMERIC => $translator->translate('label.parameters.numeric'),
            self::PARAMETERS_TYPE_NAMED => $translator->translate('label.parameters.type.named'),
        );
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
            '' => $translator->translate('label.parameters.none.render'),
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
