<?php

namespace pallo\web\cms\controller\widget;

use pallo\library\cms\content\Content;
use pallo\library\http\Response;
use pallo\library\orm\definition\ModelTable;
use pallo\library\orm\model\data\format\DataFormatter;
use pallo\library\orm\OrmManager;
use pallo\library\router\Route;
use pallo\library\validation\exception\ValidationException;

use pallo\web\cms\controller\widget\AbstractWidget;
use pallo\web\cms\form\ContentDetailComponent;
use pallo\web\cms\orm\ContentProperties;
use pallo\web\cms\orm\FieldService;

use \Exception;

/**
 * Widget to show the detail of a content type
 */
class ContentDetailWidget extends AbstractWidget {

    /**
     * Machine name of this widget
     * @var string
     */
    const NAME = 'orm.detail';

    /**
     * Relative path to the icon of this widget
     * @var string
     */
    const ICON = 'img/cms/widget/content.detail.png';

    /**
     * Gets the additional sub routes for this widget
     * @return array|null Array with a route path as key and the action method
     * as value
     */
    public function getRoutes() {
        $contentProperties = $this->getContentProperties();

        $modelName = $contentProperties->getModelName();
        if (!$modelName) {
            return array();
        }

        return array(
            new Route('/%id%', array($this, 'indexAction'), null, array('head', 'get')),
        );
    }

    /**
     * Gets the templates used by this widget
     * @return array Array with the resource names of the templates
     */
    public function getTemplates() {
        $contentProperties = $this->getContentProperties();

        $view = $contentProperties->getView();
        if (!$view) {
            return null;
        }

        $view = $this->dependencyInjector->get('pallo\\web\\cms\\view\\widget\\ContentDetailView', $view);

        return array($view->getTemplate()->getResource());
    }

    /**
     * Action to display the widget
     * @return null
     */
    public function indexAction(OrmManager $orm, $id = null) {
        $contentProperties = $this->getContentProperties();
        $action = $contentProperties->getNoParametersAction();

        if ($id === null) {
            if ($action != ContentProperties::NONE_IGNORE) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);
            }

            return;
        }

        $modelName = $contentProperties->getModelName();
        if (!$modelName) {
            return;
        }

        $this->dataFormatter = $orm->getDataFormatter();
        $this->model = $orm->getModel($modelName);

        $query = $this->getModelQuery($contentProperties, $this->locale, $id);

        $content = $this->getResult($contentProperties, $query);

        if (!$content) {
            if ($action != ContentProperties::NONE_IGNORE) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);
            }

            return;
        }

        $this->setContext('content', $content);

        $url = $this->request->getBaseScript() . $this->properties->getNode()->getRoute($this->locale) . '/' . $id;
        $this->addBreadcrumb($url, $content->title);

        if ($contentProperties->getTitle()) {
            $this->setPageTitle($content->title);
        }

        $view = $this->getView($contentProperties, $content);

        if ($this->properties->isAutoCache()) {
            $this->properties->setCache(true);
            $this->properties->setCacheTtl(60);
        }

        if ($this->properties->getWidgetProperty('region')) {
            $this->setIsRegion(true);
        }

        $this->response->setView($view);
    }

    /**
     * Gets the model query
     * @param pallo\web\cms\orm\ContentProperties $contentProperties
     * @param pallo\library\orm\model\Model $model
     * @param string $locale Code of the locale
     * @param string $id The id of the record to fetch
     * @return pallo\library\orm\query\ModelQuery
     */
    private function getModelQuery(ContentProperties $contentProperties, $locale, $id) {
        $includeUnlocalizedData = $contentProperties->getIncludeUnlocalized();

        $query = $this->model->createQuery($locale);
        $query->setRecursiveDepth($contentProperties->getRecursiveDepth());
        $query->setFetchUnlocalizedData($includeUnlocalizedData);

        $modelFields = $contentProperties->getModelFields();
        if ($modelFields) {
            foreach ($modelFields as $fieldName) {
                $query->addFields('{' . $fieldName . '}');
            }
        }

        $idField = $contentProperties->getIdField();
        $query->addCondition('{' . $idField . '} = %1%', $id);

        $condition = $contentProperties->getCondition();
        if ($condition) {
            $query->addCondition($condition);
        }

        $order = $contentProperties->getOrder();
        if ($order) {
            $query->addOrderBy($order);
        }

        return $query;
    }

    /**
     * Gets the result from the query
     * @param pallo\web\cms\orm\ContentProperties $properties
     * @param pallo\library\orm\query\ModelQuery $query
     * @return array Array with Content objects
     */
    private function getResult(ContentProperties $contentProperties, $query) {
        $data = $query->queryFirst();
        if (!$data) {
            return $data;
        }

        $node = $this->properties->getNode();
        $meta = $this->model->getMeta();

        $modelTable = $meta->getModelTable();

        $titleFormat = $contentProperties->getContentTitleFormat();
        if (!$titleFormat) {
            $titleFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_TITLE);
        }

        $teaserFormat = $contentProperties->getContentTeaserFormat();
        if (!$teaserFormat && $modelTable->hasDataFormat(DataFormatter::FORMAT_TEASER)) {
            $teaserFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_TEASER);
        }

        $imageFormat = $contentProperties->getContentImageFormat();
        if (!$imageFormat && $modelTable->hasDataFormat(DataFormatter::FORMAT_IMAGE)) {
            $imageFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_IMAGE);
        }

        $dateFormat = $contentProperties->getContentDateFormat();
        if (!$dateFormat && $modelTable->hasDataFormat(DataFormatter::FORMAT_DATE)) {
            $dateFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_DATE);
        }

        $title = $this->dataFormatter->formatData($data, $titleFormat);
        $url = null;
        $teaser = null;
        $image = null;
        $date = null;

        if ($teaserFormat) {
            $teaser = $this->dataFormatter->formatData($data, $teaserFormat);
        }

//         if ($data instanceof MediaItem) {
//             $image = $data->getImage($this->pallo);
//         } elseif ($imageFormat) {
        if ($imageFormat) {
            $image = $this->dataFormatter->formatData($data, $imageFormat);
        }

        if ($dateFormat) {
            $date = $this->dataFormatter->formatData($data, $dateFormat);
        }

        try {
            $mapper = $this->getContentMapper($this->model->getName());
            $url = $mapper->getUrl($node->getRootNodeId(), $this->locale, $data);
        } catch (Exception $e) {

        }

        return new Content($this->model->getName(), $title, $url, $teaser, $image, $date, $data);
    }

    /**
     * Gets the view
     * @param pallo\web\cms\orm\ContentProperties $properties
     * @param pallo\library\cms\content\Content $content
     * @return pallo\web\cms\view\widget\ContentView
     */
    private function getView(ContentProperties $contentProperties, $content) {
        $view = $contentProperties->getView();

        $view = $this->dependencyInjector->get('pallo\\web\\cms\\view\\widget\\ContentDetailView', $view);
        $view = clone $view;

        $view->setContent($this->locale, $this->id, $content, $contentProperties);

        return $view;
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

        $preview = $translator->translate('label.model') . ': ' . $modelName . '<br />';

        $fields = $contentProperties->getModelFields();
        if ($fields) {
            $preview .= $translator->translate('label.fields') . ': ' . implode(', ', $fields) . '<br />';
        }

        $preview .= $translator->translate('label.depth.recursive') . ': ' . $contentProperties->getRecursiveDepth() . '<br />';

        $includeUnlocalized = $contentProperties->getIncludeUnlocalized();
        if ($includeUnlocalized) {
            $preview .= $translator->translate('label.unlocalized') . ': ' . $translator->translate('label.yes') . '<br />';
        } else {
            $preview .= $translator->translate('label.unlocalized') . ': ' . $translator->translate('label.no') . '<br />';
        }

        $idField = $contentProperties->getIdField();
        if ($idField && $idField != ModelTable::PRIMARY_KEY) {
            $preview .= $translator->translate('label.field.id') . ': ' . $idField . '<br />';
        }

        $view = $contentProperties->getView();
        if ($view) {
            $preview .= $translator->translate('label.view') . ': ' . $view . '<br />';
        }

        return $preview;
    }

    /**
     * Gets the callback for the properties action
     * @return null|callback Null if the widget does not implement a properties
     * action, a callback for the action otherwise
     */
    public function getPropertiesCallback() {
        return array($this, 'propertiesAction');
    }

    /**
     * Action to show and edit the properties of this widget
     * @return null
     */
    public function propertiesAction(FieldService $fieldService) {
        $contentProperties = $this->getContentProperties();
        $views = $this->dependencyInjector->getAll('pallo\\web\\cms\\view\\widget\\ContentDetailView');

        $component = new ContentDetailComponent($fieldService);
        $component->setViews($views);

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

        $selectFieldsAction = $this->getUrl('cms.ajax.orm.fields.select', array('model' => '%model%'));
        $uniqueFieldsAction = $this->getUrl('cms.ajax.orm.fields.unique', array('model' => '%model%'));

        $view = $this->setTemplateView('cms/widget/orm/properties.detail', array(
            'form' => $form->getView(),
        ));
        $view->addJavascript('js/cms/orm.js');
        $view->addInlineJavascript('joppaContentInitializeDetailProperties("' . $selectFieldsAction . '", "' . $uniqueFieldsAction . '");');

        return false;
    }

    /**
     * Gets the properties
     * @return pallo\web\cms\orm\ContentProperties
     */
    private function getContentProperties() {
        $contentProperties = new ContentProperties();
        $contentProperties->getFromWidgetProperties($this->properties, $this->locale);

        return $contentProperties;
    }

}