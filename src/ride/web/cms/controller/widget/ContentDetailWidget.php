<?php

namespace ride\web\cms\controller\widget;

use ride\library\cms\content\Content;
use ride\library\http\Response;
use ride\library\i18n\I18n;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\entry\format\EntryFormatter;
use ride\library\orm\OrmManager;
use ride\library\reflection\ReflectionHelper;
use ride\library\router\Route;
use ride\library\validation\exception\ValidationException;

use ride\web\cms\form\ContentDetailComponent;
use ride\web\cms\orm\ContentProperties;
use ride\web\cms\orm\FieldService;

use \Exception;

/**
 * Widget to show the detail of a content type
 */
class ContentDetailWidget extends AbstractWidget implements StyleWidget {

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
     * Namespace for the templates of this widget
     * @var string
     */
    const TEMPLATE_NAMESPACE = 'cms/widget/orm-detail';

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
            new Route('/%id%', array($this, 'indexAction'), 'detail', array('head', 'get', 'post')),
        );
    }

     /**
     * Action to display the widget
     * @return null
     */
    public function indexAction(OrmManager $orm, I18n $i18n, ReflectionHelper $reflectionHelper, $id = null) {
        $contentProperties = $this->getContentProperties();

        $modelName = $contentProperties->getModelName();
        if (!$modelName) {
            return;
        }

        $action = $contentProperties->getNoParametersAction();

        if ($id === null) {
            if ($action != ContentProperties::NONE_IGNORE) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);
            }

            return;
        }

        $this->entryFormatter = $orm->getEntryFormatter();
        $this->model = $orm->getModel($modelName);

        $query = $this->getModelQuery($contentProperties, $this->locale, $id);
        $content = $this->getResult($contentProperties, $query);

        if (!$content && $contentProperties->getIncludeUnlocalized()) {
            // no content, look for localized version
            $locales = $i18n->getLocaleList();
            foreach ($locales as $localeCode => $locale) {
                if ($localeCode == $this->locale) {
                    continue;
                }

                $query = $this->getModelQuery($contentProperties, $localeCode, $id);
                $content = $this->getResult($contentProperties, $query);

                if ($content) {
                    break;
                }
            }
        }

        if (!$content && $contentProperties->getIdField() != ModelTable::PRIMARY_KEY) {
            // no content, look for slug in history and redirect if possible
            $entryId = $this->getIdFromLog($orm, $reflectionHelper, $contentProperties, $this->locale, $id);
            if ($entryId) {
                $url = $this->getUrl('detail', array('id' => $entryId));

                $this->response->setRedirect($url, Response::STATUS_CODE_MOVED_PERMANENTLY);

                return;
            }
        }

        if ($content && $content->data instanceof LocalizedEntry && !$content->data->isLocalized() && !$contentProperties->getIncludeUnlocalized()) {
            $content = null;
        }

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

        $this->setView($contentProperties, $content);

        if ($this->properties->isAutoCache()) {
            $this->properties->setCache(true);
            $this->properties->setCacheTtl(60);
        }

        if ($this->properties->getWidgetProperty('region')) {
            $this->setIsRegion(true);
        }
    }

    /**
     * Gets the model query
     * @param \ride\web\cms\orm\ContentProperties $contentProperties
     * @param \ride\library\orm\model\Model $model
     * @param string $locale Code of the locale
     * @param string $id The id of the record to fetch
     * @return \ride\library\orm\query\ModelQuery
     */
    protected function getModelQuery(ContentProperties $contentProperties, $locale, $id) {
        $query = $this->model->createQuery($locale);
        $query->setRecursiveDepth($contentProperties->getRecursiveDepth());
        $query->setFetchUnlocalized($contentProperties->getIncludeUnlocalized());

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
     * @param \ride\web\cms\orm\ContentProperties $properties
     * @param \ride\library\orm\query\ModelQuery $query
     * @return array Array with Content objects
     */
    protected function getResult(ContentProperties $contentProperties, $query) {
        $entry = $query->queryFirst();
        if (!$entry) {
            return $entry;
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

        $title = $this->entryFormatter->formatEntry($entry, $titleFormat);
        $url = null;
        $teaser = null;
        $image = null;
        $date = null;

        if ($teaserFormat) {
            $teaser = $this->entryFormatter->formatEntry($entry, $teaserFormat);
        }

        if ($imageFormat) {
            $image = $this->entryFormatter->formatEntry($entry, $imageFormat);
        }

        if ($dateFormat) {
            $date = $this->entryFormatter->formatEntry($entry, $dateFormat);
        }

        try {
            $mapper = $this->getContentMapper($this->model->getName());
            $url = $mapper->getUrl($node->getRootNodeId(), $this->locale, $entry);
        } catch (Exception $e) {

        }

        return new Content($this->model->getName(), $title, $url, $teaser, $image, $date, $entry);
    }

    /**
     * Gets a previous slug from the entry log
     * @param \ride\library\orm\OrmManager $orm
     * @param \ride\library\reflection\ReflectionHelper $reflectionHelper
     * @param \ride\web\cms\orm\ContentProperties $contentProperties
     * @param string $locale Code of the locale
     * @param string $id Requested slug
     * @return string|null Current slug of the entry, null if no change found
     */
    protected function getIdFromLog(OrmManager $orm, ReflectionHelper $reflectionHelper, ContentProperties $contentProperties, $locale, $id) {
        $entryLogChangeModel = $orm->getEntryLogChangeModel();
        $meta = $this->model->getMeta();

        $isLocalized = $meta->isLocalized();
        if ($isLocalized) {
            $model = $orm->getModel($meta->getLocalizedModelName());
        } else {
            $model = $this->model;
        }

        $idField = $contentProperties->getIdField();

        // look in the log for the requested id
        $query = $entryLogChangeModel->createQuery($locale);
        $query->addCondition('{entryLog.model} = %1%', $model->getName());
        $query->addCondition('{fieldName} = %1%', $idField);
        $query->addCondition('{oldValue} = %1%', $id);

        $query->addOrderBy('{id} DESC');

        $entryLogChange = $query->queryFirst();
        if (!$entryLogChange) {
            // no history of the provided id
            return null;
        }

        $entryLog = $entryLogChange->getEntryLog();
        $entryId = $entryLog->getEntry();

        // get the original entry
        $entry = $model->getById($entryId, $this->locale);
        if (!$entry) {
            return null;
        }

        // retrieve and return the id value from the entry
        return $reflectionHelper->getProperty($entry, $idField);
    }

    /**
     * Sets the view
     * @param \ride\web\cms\orm\ContentProperties $properties
     * @param \ride\library\cms\content\Content $content
     * @return \ride\library\mvc\view\View
     */
    protected function setView(ContentProperties $contentProperties, $content) {
        $template = $this->getTemplate(static::TEMPLATE_NAMESPACE . '/default');
        $variables = array(
            'locale' => $this->locale,
            'widgetId' => $this->id,
            'content' => $content,
            'properties' => $contentProperties,
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
        $preview .= '<strong>' . $translator->translate('label.unlocalized') . '</strong>: ' . $translator->translate($includeUnlocalized ? 'label.yes' : 'label.no') . '<br />';

        $idField = $contentProperties->getIdField();
        if ($idField && $idField != ModelTable::PRIMARY_KEY) {
            $preview .= '<strong>' . $translator->translate('label.field.id') . '</strong>: ' . $idField . '<br />';
        }

        $preview .= '<strong>' . $translator->translate('label.template') . '</strong>: ' . $this->getTemplate(static::TEMPLATE_NAMESPACE . '/block') . '<br>';

        return $preview;
    }

    /**
     * Action to show and edit the properties of this widget
     * @return null
     */
    public function propertiesAction(FieldService $fieldService) {
        $contentProperties = $this->getContentProperties();
        if (!$contentProperties->getModelName()) {
            $contentProperties->setTitle(true);
        }

        $viewProcessors = $this->dependencyInjector->getByTag('ride\\web\\cms\\orm\\processor\\ViewProcessor', 'detail');
        foreach ($viewProcessors as $id => $viewProcessors) {
            $viewProcessors[$id] = $id;
        }
        $viewProcessors = array('' => '---') + $viewProcessors;

        $component = new ContentDetailComponent($fieldService);
        $component->setTemplates($this->getAvailableTemplates(static::TEMPLATE_NAMESPACE));
        $component->setViewProcessors($viewProcessors);

        $form = $this->buildForm($component, $contentProperties);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                return false;
            }

            try {
                $form->validate();

                $contentProperties = $form->getData();
                $contentProperties->setToWidgetProperties($this->properties, $this->locale);

                return true;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $uniqueFieldsAction = $this->getUrl('cms.ajax.orm.fields.unique', array('model' => '%model%'));

        $view = $this->setTemplateView(static::TEMPLATE_NAMESPACE . '/properties', array(
            'form' => $form->getView(),
        ));
        $view->addJavascript('js/cms/orm.js');
        $view->addInlineJavascript('joppaContentInitializeDetailProperties("' . $uniqueFieldsAction . '");');

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
