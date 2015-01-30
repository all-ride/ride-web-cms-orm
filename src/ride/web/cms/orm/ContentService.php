<?php

namespace ride\web\cms\orm;

use ride\library\cms\exception\CmsException;
use ride\library\cms\content\ContentFacade;
use ride\library\cms\content\Content;
use ride\library\cms\node\NodeModel;
use ride\library\orm\model\Model;
use ride\library\orm\OrmManager;

use ride\web\cms\content\mapper\OrmContentMapper;
use ride\web\cms\content\mapper\GenericOrmContentMapper;

use \Exception;

/**
 * Service to integrate the ORM with the content layer of the CMS
 */
class ContentService {

    /**
     * Instance of the ORM manager
     * @var \ride\library\orm\OrmManager
     */
    protected $orm;

    /**
     * Instance of the node model
     * @param \ride\library\cms\node\NodeModel
     */
    protected $nodeModel;

    /**
     * Constructs a new field service
     * @param \ride\library\orm\OrmManager $orm
     * @param \ride\library\cms\node\NodeModel $nodeModel
     */
    public function __construct(OrmManager $orm, NodeModel $nodeModel) {
        $this->orm = $orm;
        $this->nodeModel = $nodeModel;

        $this->mappers = null;
        $this->defaultMappers = null;
    }

    /**
     * Sets the content facade of the CMS
     * @param \ride\library\cms\content\ContentFacade $contentFacade
     * @return null
     */
    public function setContentFacade(ContentFacade $contentFacade) {
        $this->contentFacade = $contentFacade;
    }

    /**
     * Reads the content mappers for the content detail widgets
     * @return array
     */
    public function readContentMappers() {
        $this->mappers = array();
        $this->defaultMappers = array();

        $entryFormatter = $this->orm->getEntryFormatter();

        $nodes = $this->nodeModel->getNodesForWidget('orm.detail');
        foreach ($nodes as $node) {
            $widgetId = $node->getWidgetId();
            if (!$widgetId) {
                continue;
            }

            $widgetProperties = $node->getWidgetProperties($widgetId);

            $modelName = $widgetProperties->getWidgetProperty(ContentProperties::PROPERTY_MODEL_NAME);
            if (!$modelName) {
                continue;
            }

            if (!isset($mappers[$modelName])) {
                $mappers[$modelName] = array();
            }

            $model = $this->orm->getModel($modelName);

            $mapperId = $node->getId() . '-' . $widgetId;

            $this->mappers[$modelName][$mapperId] = new GenericOrmContentMapper($this->nodeModel, $node, $model, $entryFormatter, $widgetProperties);

            if ($widgetProperties->getWidgetProperty(ContentProperties::PROPERTY_PRIMARY)) {
                $this->defaultMappers[$modelName] = $mapperId;
            }
        }

        foreach ($this->mappers as $modelName => $modelMappers) {
            if (!isset($this->defaultMappers[$modelName])) {
                reset($modelMappers);

                $this->defaultMappers[$modelName] = key($modelMappers);
            }
        }

        return $this->mappers;
    }

    /**
     * Gets the default content mappers indexed by type
     * @return array Array with the type as key and the instance of the default
     * content mapper of the type as value
     */
    public function getContentMappers() {
        $mappers = array();

        foreach ($this->defaultMappers as $modelName => $widgetId) {
            $mappers[$modelName] = $this->mappers[$modelName][$widgetId];
        }

        return $mappers;
    }

    /**
     * Gets all the content mappers of the provided type
     * @param string $type Name of the content type
     * @return array Array with the id of the content mapper as key and the
     * instance of the content mapper as value
     */
    public function getContentMappersForType($type) {
        if (!isset($this->mappers[$type])) {
            return array();
        }

        return $this->mappers[$type];
    }

    /**
     * Gets the content mapper for the provided content type
     * @param string $type Name of the content type
     * @param string $id Id of the content mapper, if omitted, the default
     * content mapper of the provided type will be returned
     * @return \ride\library\cms\content\mapper\ContentMapper
     */
    public function getContentMapper($type, $id = null) {
        if ($this->mappers === null) {
            $this->readContentMappers();
        }

        if (!$id) {
            if (isset($this->defaultMappers[$type])) {
                $id = $this->defaultMappers[$type];
            } else {
                throw new CmsException('Could not get the content mapper for ' . $type . ': no mappers set for the provided content type');
            }
        }

        if (!isset($this->mappers[$type][$id])) {
            throw new CmsException('Could not get the content mapper for ' . $type . ': no mappers set with id ' . $id);
        }

        $mapper = $this->mappers[$type][$id];
        $mapper->setBaseUrl($this->contentFacade->getBaseUrl());
        $mapper->setBaseScript($this->contentFacade->getBaseScript());

        return $mapper;
    }

    /**
     * Gets the content object for the provided entry
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @param string $siteId
     * @param string $locale
     * @param string $idContentMapper
     * @param string $titleFormat
     * @param string $teaserFormat
     * @param string $imageFormat
     * @param string $dateFormat
     * @return \ride\library\cms\content\Content
     */
    public function getContentForEntry(Model $model, $entry, $siteId, $locale, $idContentMapper = null, $titleFormat = null, $teaserFormat = null, $imageFormat = null, $dateFormat = null) {
        $result = $this->getContentForEntries($model, array($entry), $siteId, $locale, $idContentMapper, $titleFormat, $teaserFormat, $imageFormat, $dateFormat);

        return $result[0];
    }

    /**
     * Gets the content objects for the provided entries
     * @param \ride\library\orm\model\Model $model
     * @param array $entries
     * @param string $siteId
     * @param string $locale
     * @param string $idContentMapper
     * @param string $titleFormat
     * @param string $teaserFormat
     * @param string $imageFormat
     * @param string $dateFormat
     * @return array Array with Content objects for the provided entries
     * @see \ride\library\cms\content\Content
     */
    public function getContentForEntries(Model $model, array $result, $siteId, $locale, $idContentMapper = null, $titleFormat = null, $teaserFormat = null, $imageFormat = null, $dateFormat = null) {
        $modelName = $model->getName();
        $entryFormatter = $this->orm->getEntryFormatter();

        try {
            $mapper = $this->getContentMapper($modelName, $idContentMapper);
        } catch (Exception $e) {
            $mapper = new OrmContentMapper($this->nodeModel, $model, $entryFormatter);
        }

        foreach ($result as $index => $entry) {
            $title = $entryFormatter->formatEntry($entry, $titleFormat);
            $url = $mapper->getUrl($siteId, $locale, $entry);

            $teaser = null;
            if ($teaserFormat) {
                $teaser = $entryFormatter->formatEntry($entry, $teaserFormat);
            }

            $image = null;
            if ($imageFormat) {
                $image = $entryFormatter->formatEntry($entry, $imageFormat);
            }

            $date = null;
            if ($dateFormat) {
                $date = $entryFormatter->formatEntry($entry, $dateFormat);
            }

            $content = new Content($modelName, $title, $url, $teaser, $image, $date, $entry);

            $result[$index] = $content;
        }

        return $result;
    }

}
