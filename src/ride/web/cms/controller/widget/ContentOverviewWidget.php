<?php

namespace ride\web\cms\controller\widget;

use ride\library\cms\exception\NodeNotFoundException;
use ride\library\cms\content\Content;
use ride\library\cms\node\NodeModel;
use ride\library\html\Pagination;
use ride\library\http\Response;
use ride\library\orm\query\ModelQuery;
use ride\library\orm\entry\format\EntryFormatter;
use ride\library\orm\OrmManager;
use ride\library\router\Route;
use ride\library\validation\exception\ValidationException;

use ride\web\cms\form\ContentOverviewComponent;
use ride\web\cms\orm\ContentProperties;
use ride\web\cms\orm\ContentService;
use ride\web\cms\orm\FieldService;

use \Exception;

/**
 * Widget to show a overview of a content type
 */
class ContentOverviewWidget extends AbstractWidget implements StyleWidget {

    /**
     * Machine name of this widget
     * @var string
     */
    const NAME = 'orm.overview';

    /**
     * Relative path to the icon of this widget
     * @var string
     */
    const ICON = 'img/cms/widget/content.overview.png';

    /**
     * Namespace for the templates of this widget
     * @var string
     */
    const TEMPLATE_NAMESPACE = 'cms/widget/orm-overview';

    /**
     * Parameter name for the page
     * @var string
     */
    const PARAM_PAGE = 'page';

    /**
     * Instance of the model
     * @var \ride\library\orm\model\Model
     */
    private $model;

    /**
     * Formatter for ORM entries
     * @var \ride\library\orm\entry\format\EntryFormatter
     */
    private $entryFormatter;

    /**
     * Processed filters of the data
     * @var array
     */
    private $filters;

    /**
     * Gets the additional sub routes for this widget
     * @return array|null Array with a route path as key and the action method
     * as value
     */
    public function getRoutes() {
        $node = $this->properties->getNode();
        if ($node->getRoute($this->locale) === '/') {
            // never make the root of your site dynamic, it can mess up other resources
            return null;
        }

        $route = new Route('/', array($this, 'indexAction'), null, array('head', 'get'));
        $route->setIsDynamic(true);

        return array(
            $route,
        );
    }

    /**
     * Action to display the widget
     * @return null
     */
    public function indexAction(OrmManager $orm, ContentService $contentService) {
        $contentProperties = $this->getContentProperties();
        $modelName = $contentProperties->getModelName();

        if (!$modelName) {
            if ($this->properties->isAutoCache()) {
                $this->properties->setCache(true);
            }

            return;
        }

        $action = $contentProperties->getNoParametersAction();
        $parameters = $contentProperties->getParameters();
        $arguments = func_get_args();
        array_shift($arguments); // remove $orm
        array_shift($arguments); // remove $contentService

        if ($parameters) {
            if (is_array($parameters)) {
                if (count($arguments) != (count($parameters) * 2)) {
                    if ($action != ContentProperties::NONE_IGNORE) {
                        $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);
                    }

                    return;
                }

                $arguments = $this->parseArguments($arguments);
            } else {
                if (count($arguments) != $parameters) {
                    if ($action != ContentProperties::NONE_IGNORE) {
                        $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);
                    }

                    return;
                }

                foreach ($arguments as $index => $argument) {
                    $arguments[$index] = urldecode($argument);
                }
            }
        } elseif ($arguments) {
            if ($action == ContentProperties::NONE_404 || $action == ContentProperties::NONE_IGNORE) {
                if ($action != ContentProperties::NONE_IGNORE) {
                    $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);
                }

                return;
            }
        } else {
            $arguments = array();
        }

        $page = 1;
        $pages = 1;
        if ($contentProperties->willShowPagination()) {
            $page = $this->request->getQueryParameter(self::PARAM_PAGE);
            if (!is_numeric($page) || $page <= 0) {
                $page = 1;
            }
        }

        $this->entryFormatter = $orm->getEntryFormatter();
        $this->model = $orm->getModel($modelName);

        $query = $this->getModelQuery($contentProperties, $this->locale, $page, $arguments);

        if ($contentProperties->willShowPagination()) {
            $rows = max(0, $query->count() - $contentProperties->getPaginationOffset());
            $pages = ceil($rows / $contentProperties->getPaginationRows());

            if ($contentProperties->useAjaxForPagination() && $this->request->isXmlHttpRequest()) {
                $this->setIsContent(true);
            }
        }

        $result = $this->getResult($contentProperties, $contentService, $query);

        $this->setView($contentProperties, $result, $pages, $page, $arguments);

        if ($this->properties->isAutoCache()) {
            $this->properties->setCache(true);
            $this->properties->setCacheTtl(60);
        }
    }

    /**
     * Gets the view
     * @param array $result
     * @param \ride\web\cms\orm\ContentProperties $properties
     * @param integer $pages
     * @param integer $page
     * @param array $arguments
     * @return \ride\library\mvc\view\View
     */
    private function setView(ContentProperties $contentProperties, array $result, $pages = 1, $page = 1, array $arguments = array()) {
        $pagination = null;
        if ($contentProperties->willShowPagination()) {
            $query = null;
            $paginationUrl = $this->request->getUrl();
            if (strpos($paginationUrl, '?') !== false) {
                list($paginationUrl, $query) = explode('?', $paginationUrl, 2);
            }

            $query = preg_replace('((\\?)?' . self::PARAM_PAGE . '=([0-9])*(&)?)', '', $query);

            $paginationUrl .= '?' . self::PARAM_PAGE . '='. '%page%';
            if ($query) {
                $paginationUrl .= '&' . $query;
            }

            $pagination = new Pagination($pages, $page);
            $pagination->setHref($paginationUrl);
        }

        $moreUrl = null;
        if ($contentProperties->willShowMoreLink()) {
            $nodeModel = $this->dependencyInjector->get('ride\\library\\cms\\node\\NodeModel');

            $selfNode = $this->properties->getNode();
            try {
                $node = $nodeModel->getNode($selfNode->getRootNodeId(), $selfNode->getRevision(), $contentProperties->getMoreNode());
                $moreUrl = $this->request->getBaseScript() . $node->getRoute($this->locale);
            } catch (NodeNotFoundException $exception) {

            }
        }

        $baseUrl = $this->request->getBaseScript() . $this->properties->getNode()->getRoute($this->locale);

        foreach ($this->filters as $filterName => $filter) {
            $filter['filter']->setVariables($this->filters, $this->model, $filterName, $this->locale, $baseUrl);
        }

        $this->setContext('orm.overview.' . $this->id, $result);
        $this->setContext('orm.filters.' . $this->id, $this->filters);

        $template = $this->getTemplate(static::TEMPLATE_NAMESPACE . '/block');
        $variables = array(
            'locale' => $this->locale,
            'widgetId' => $this->id,
            'result' => $result,
            'properties' => $contentProperties,
            'title' => $contentProperties->getTitle(),
            'emptyResultMessage' => $contentProperties->getEmptyResultMessage(),
            'filters' => $this->filters,
            'arguments' => $arguments,
            'pagination' => $pagination,
            'moreUrl' => $moreUrl,
            'moreLabel' => $contentProperties->getMoreLabel(),
        );

        $view = $this->setTemplateView($template, $variables);

        $viewProcessor = $contentProperties->getViewProcessor();
        if ($viewProcessor) {
            $viewProcessor = $this->dependencyInjector->get('ride\\web\\cms\\orm\\processor\\ViewProcessor', $viewProcessor);

            $viewProcessor->processView($view);
        }

        return $view;
    }

    /**
     * Gets the result from the query
     * @param \ride\web\cms\orm\ContentProperties $contentProperties
     * @param \ride\web\cms\orm\ContentService $contentService
     * @param \ride\library\orm\query\ModelQuery $query
     * @return array Array with Content objects
     */
    private function getResult(ContentProperties $contentProperties, ContentService $contentService, ModelQuery $query) {
        $result = $query->query();
        if (!$result) {
            return $result;
        }

        $node = $this->properties->getNode();
        $meta = $this->model->getMeta();

        $modelTable = $meta->getModelTable();

        $titleFormat = $contentProperties->getContentTitleFormat();
        if (!$titleFormat) {
            $titleFormat = $modelTable->getFormat(EntryFormatter::FORMAT_TITLE, false);
            if ($titleFormat == null) {
                $titleFormat = $this->model->getName() . ' #{id}';
            }
        }

        $teaserFormat = $contentProperties->getContentTeaserFormat();
        if (!$teaserFormat && $modelTable->hasFormat(EntryFormatter::FORMAT_TEASER)) {
            $teaserFormat = $modelTable->getFormat(EntryFormatter::FORMAT_TEASER);
        }

        $imageFormat = $contentProperties->getContentImageFormat();
        if (!$imageFormat && $modelTable->hasFormat(EntryFormatter::FORMAT_IMAGE)) {
            $imageFormat = $modelTable->getFormat(EntryFormatter::FORMAT_IMAGE);
        }

        $dateFormat = $contentProperties->getContentDateFormat();
        if (!$dateFormat && $modelTable->hasFormat(EntryFormatter::FORMAT_DATE)) {
            $dateFormat = $modelTable->getFormat(EntryFormatter::FORMAT_DATE);
        }

        return $contentService->getContentForEntries($this->model, $result, $node->getRootNodeId(), $this->locale, $contentProperties->getContentMapper(), $titleFormat, $teaserFormat, $imageFormat, $dateFormat);
    }

    /**
     * Creates the model query from the provided properties
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\orm\model\ContentProperties $contentProperties
     * @param string $locale Code of the locale
     * @param integer $page Page number
     * @param array $arguments Arguments for the condition
     * @return \ride\library\orm\query\ModelQuery
     */
    public function getModelQuery(ContentProperties $contentProperties, $locale, $page = 1, array $arguments) {
        $query = $this->model->createQuery($locale);
        $query->setRecursiveDepth($contentProperties->getRecursiveDepth());
        $query->setFetchUnlocalized($contentProperties->getIncludeUnlocalized());

        $modelFields = $contentProperties->getModelFields();
        if ($modelFields) {
            foreach ($modelFields as $fieldName) {
                $query->addFields('{' . $fieldName . '}');
            }
        }

        $condition = $contentProperties->getCondition();
        if ($condition) {
            $arguments = $this->parseContextVariables($condition, $arguments);
            if ($arguments) {
                $query->addConditionWithVariables($condition, $arguments);
            } else {
                $query->addCondition($condition);
            }
        }

        $this->filters = array();

        $filters = $contentProperties->getFilters();
        foreach ($filters as $filter) {
            $filterValue = $this->request->getQueryParameter($filter['name']);

            $this->filters[$filter['name']] = array(
                'type' => $filter['type'],
                'field' => $filter['field'],
                'filter' => $this->dependencyInjector->get('ride\\web\\cms\\orm\\filter\\ContentOverviewFilter', $filter['type']),
            );
            $this->filters[$filter['name']]['value'] = $this->filters[$filter['name']]['filter']->applyQuery($this->model, $query, $filter['field'], $filterValue);
        }

        $order = $contentProperties->getOrder();
        if ($order) {
            $query->addOrderBy($order);
        }

        if ($contentProperties->isPaginationEnabled()) {
            $paginationOffset = $contentProperties->getPaginationOffset();

            $rows = $contentProperties->getPaginationRows();
            $offset = ($page - 1) * $rows;

            if ($paginationOffset) {
                $offset += $paginationOffset;
            }

            $query->setLimit((integer) $rows, (integer) $offset);
        }

        return $query;
    }

    /**
     * Parses the context into the arguments
     * @param string $condition Condition string
     * @param array $arguments Already set arguments
     * @return array Provided arguments with the resolved context arguments
     * @throws Exception when the context arguments could not be parsed
     */
    public function parseContextVariables($condition, array $arguments) {
        if (strpos($condition, '%context') === false) {
            return $arguments;
        }

        $matches = array();

        preg_match_all('(%context([A-Za-z0-9]|\\.)*%)', $condition, $matches);
        if (!$matches[0]) {
            throw new Exception('Could not parse context argument: no arguments matched');
        }

        $reflectionHelper = $this->model->getReflectionHelper();

        foreach ($matches[0] as $variable) {
            $tokens = explode('.', trim($variable, '%'));
            array_shift($tokens);

            $value = null;

            do {
                $token = array_shift($tokens);

                if ($value === null) {
                    $value = $this->getContext($token);
                } else {
                    $value = $reflectionHelper->getProperty($value, $token);
                }

                if ($value === null) {
                    throw new Exception('Could not parse context arguments: ' . $variable . ' could not be resolved');
                }
            } while ($tokens);

            $arguments[substr($variable, 1, -1)] = $value;
        }

        return $arguments;
    }

    /**
     * Gets a preview of the properties of this widget
     * @return string
     */
    public function getPropertiesPreview() {
        $translator = $this->getTranslator();
        $contentProperties = $this->getContentProperties();

        $modelName = $contentProperties->getModelName();
        if (!$modelName) {
            return $translator->translate('label.widget.properties.unset');
        }

        $preview = '<strong>' . $translator->translate('label.model') . '</strong>: ' . $modelName . '<br />';

        $fields = $contentProperties->getModelFields();
        if ($fields) {
            $preview .= '<strong>' . $translator->translate('label.fields') . '</strong>: ' . implode(', ', $fields) . '<br />';
        }

        $recursiveDepth = $contentProperties->getRecursiveDepth();
        if ($recursiveDepth) {
            $preview .= '<strong>' . $translator->translate('label.depth.recursive') . '</strong>: ' . $recursiveDepth . '<br />';
        }

        $includeUnlocalized = $contentProperties->getIncludeUnlocalized();
        if ($includeUnlocalized) {
            $preview .= '<strong>' . $translator->translate('label.unlocalized') . '</strong>: ' . $translator->translate('label.yes') . '<br />';
        } else {
            $preview .= '<strong>' . $translator->translate('label.unlocalized') . '</strong>: ' . $translator->translate('label.no') . '<br />';
        }

        $condition = $contentProperties->getCondition();
        if ($condition) {
            $preview .= '<strong>' . $translator->translate('label.condition') . '</strong>: ' . $condition . '<br />';
        }

        $filters = $contentProperties->getFilters();
        if ($filters) {
            foreach ($filters as $index => $filter) {
                $filters[$index] = '<li>' . $filter['name'] . ': ' . $translator->translate('label.content.overview.filter.' . $filter['type']) . ' (' . $filter['field'] . ')</li>';
            }

            $preview .= '<strong>' . $translator->translate('label.filters') . '</strong>: <ul>' . implode('', $filters) . '</ul>';
        }

        $order = $contentProperties->getOrder();
        if ($order) {
            $preview .= '<strong>' . $translator->translate('label.order') . '</strong>: ' . $order . '<br />';
        }

        if ($contentProperties->isPaginationEnabled()) {
            $parameters = array(
                'rows' => $contentProperties->getPaginationRows(),
                'offset' => $contentProperties->getPaginationOffset(),
            );

            $preview .= $translator->translate('label.pagination.description', $parameters) . '<br />';
        }

        $preview .= '<strong>' . $translator->translate('label.template') . '</strong>: ' . $this->getTemplate(static::TEMPLATE_NAMESPACE . '/block') . '<br>';

        return $preview;
    }

    /**
     * Action to show and edit the properties of this widget
     * @return null
     */
    public function propertiesAction(NodeModel $nodeModel, FieldService $fieldService, ContentService $contentService) {
        $contentProperties = $this->getContentProperties();
        if (!$contentProperties->getModelName()) {
            $contentProperties->setRecursiveDepth(0);
            $contentProperties->setTemplate(static::TEMPLATE_NAMESPACE . '/block');
        }
        $translator = $this->getTranslator();

        $contentOverviewFilters = $this->dependencyInjector->getAll('ride\\web\\cms\\orm\\filter\\ContentOverviewFilter');
        foreach ($contentOverviewFilters as $filterName => $filter) {
            $contentOverviewFilters[$filterName] = $translator->translate('label.content.overview.filter.' . $filterName);
        }

        $nodeOptions = $this->getNodeList($nodeModel);

        $viewProcessors = $this->dependencyInjector->getByTag('ride\\web\\cms\\orm\\processor\\ViewProcessor', 'overview');
        foreach ($viewProcessors as $id => $viewProcessor) {
            $viewProcessors[$id] = $id;
        }
        $viewProcessors = array('' => '---') + $viewProcessors;

        $component = new ContentOverviewComponent($fieldService);
        $component->setContentService($contentService);
        $component->setNodeOptions($nodeOptions);
        $component->setContentOverviewFilters($contentOverviewFilters);
        $component->setTemplates($this->getAvailableTemplates(static::TEMPLATE_NAMESPACE));
        $component->setViewProcessors($viewProcessors);

        $form = $this->buildForm($component, $contentProperties);
        if ($form->isSubmitted($this->request)) {
            if ($this->request->getBodyParameter('cancel')) {
                return false;
            }

            try {
                $form->validate();

                $contentProperties = $form->getData();
                $contentProperties->setToWidgetProperties($this->properties, $this->locale);

                return true;
            } catch (ValidationException $exception) {

            }
        }

        $orderFieldsAction = $this->getUrl('cms.ajax.orm.fields.order', array('model' => '%model%', 'recursiveDepth' => '%recursiveDepth%'));
        $filterFieldsAction = $this->getUrl('cms.ajax.orm.fields.relation', array('model' => '%model%'));
        $modelMappersAction = $this->getUrl('api.cms.orm.model.mappers', array('model' => '%model%'));

        $view = $this->setTemplateView(static::TEMPLATE_NAMESPACE . '/properties', array(
        	'form' => $form->getView(),
        ));
        $view->addJavascript('js/cms/orm.js');
        $view->addInlineJavascript('joppaContentInitializeOverviewProperties("' . $orderFieldsAction . '", "' . $filterFieldsAction . '", "' . $modelMappersAction . '");');

        return false;
    }

    /**
     * Gets the properties
     * @return \ride\web\cms\orm\ContentProperties
     */
    private function getContentProperties() {
        $contentProperties = new ContentProperties();
        $contentProperties->getFromWidgetProperties($this->properties, $this->locale);

        return $contentProperties;
    }

    /**
     * Gets the options for the styles
     * @return array Array with the name of the option as key and the
     * translation key as value
     */
    public function getWidgetStyleOptions() {
        return array(
            'container' => 'label.style.container',
            'title' => 'label.style.title',
        );
    }

}
