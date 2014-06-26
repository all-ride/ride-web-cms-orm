<?php

namespace ride\web\cms\controller\widget;

use ride\library\cms\content\Content;
use ride\library\http\Response;
use ride\library\i18n\I18n;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\entry\format\EntryFormatter;
use ride\library\orm\OrmManager;
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
            new Route('/%id%', array($this, 'indexAction'), null, array('head', 'get', 'post')),
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

        $view = $this->dependencyInjector->get('ride\\web\\cms\\view\\widget\\ContentDetailView', $view);

        return array($view->getTemplate()->getResource());
    }

    /**
     * Action to display the widget
     * @return null
     */
    public function indexAction(OrmManager $orm, I18n $i18n, $id = null) {
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

        $this->entryFormatter = $orm->getEntryFormatter();
        $this->model = $orm->getModel($modelName);

        $query = $this->getModelQuery($contentProperties, $this->locale, $id);
        $content = $this->getResult($contentProperties, $query);

        if (!$content && $contentProperties->getIncludeUnlocalized()) {
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
     * @param \ride\web\cms\orm\ContentProperties $contentProperties
     * @param \ride\library\orm\model\Model $model
     * @param string $locale Code of the locale
     * @param string $id The id of the record to fetch
     * @return \ride\library\orm\query\ModelQuery
     */
    private function getModelQuery(ContentProperties $contentProperties, $locale, $id) {
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
    private function getResult(ContentProperties $contentProperties, $query) {
        $entry = $query->queryFirst();
        if (!$entry) {
            return $entry;
        }

        $node = $this->properties->getNode();
        $meta = $this->model->getMeta();

        $modelTable = $meta->getModelTable();

        $titleFormat = $contentProperties->getContentTitleFormat();
        if (!$titleFormat) {
            $titleFormat = $modelTable->getFormat(EntryFormatter::FORMAT_TITLE);
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
     * Gets the view
     * @param \ride\web\cms\orm\ContentProperties $properties
     * @param \ride\library\cms\content\Content $content
     * @return \ride\web\cms\view\widget\ContentView
     */
    private function getView(ContentProperties $contentProperties, $content) {
        $view = $contentProperties->getView();

        $view = $this->dependencyInjector->get('ride\\web\\cms\\view\\widget\\ContentDetailView', $view);
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

        $recursiveDepth = $contentProperties->getRecursiveDepth();
        if ($recursiveDepth) {
            $preview .= $translator->translate('label.depth.recursive') . ': ' . $recursiveDepth . '<br />';
        }

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
     * Action to show and edit the properties of this widget
     * @return null
     */
    public function propertiesAction(FieldService $fieldService) {
        $contentProperties = $this->getContentProperties();
        $views = $this->dependencyInjector->getAll('ride\\web\\cms\\view\\widget\\ContentDetailView');

        $component = new ContentDetailComponent($fieldService);
        $component->setViews($views);

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
            'container' => 'label.widget.style.container',
            'title' => 'label.widget.style.title',
        );
    }

}
