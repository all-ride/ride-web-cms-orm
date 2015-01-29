<?php

namespace ride\web\cms\orm;

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

    protected $orm;

    protected $contentFacade;

    protected $nodeModel;

    /**
     * Constructs a new field service
     * @param \ride\library\orm\OrmManager $orm
     * @param \ride\library\cms\content\ContentFacade $contentFacade
     */
    public function __construct(OrmManager $orm, ContentFacade $contentFacade, NodeModel $nodeModel) {
        $this->orm = $orm;
        $this->contentFacade = $contentFacade;
        $this->nodeModel = $nodeModel;
    }

    /**
     * Gets the content mappers for the content detail widgets
     * @return array
     */
    public function createContentMappers() {
        $mappers = array();
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

            $model = $this->orm->getModel($modelName);

            $mappers[$modelName] = new GenericOrmContentMapper($this->nodeModel, $node, $model, $entryFormatter, $widgetProperties);
        }

        return $mappers;
    }

    /**
     * Gets the content object for the provided entry
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @param string $siteId
     * @param string $locale
     * @param string $titleFormat
     * @param string $teaserFormat
     * @param string $imageFormat
     * @param string $dateFormat
     * @return \ride\library\cms\content\Content
     */
    public function getContentForEntry(Model $model, $entry, $siteId, $locale, $titleFormat = null, $teaserFormat = null, $imageFormat = null, $dateFormat = null) {
        $result = $this->getContentForEntries($model, array($entry), $siteId, $locale, $titleFormat, $teaserFormat, $imageFormat, $dateFormat);

        return $result[0];
    }

    /**
     * Gets the content objects for the provided entries
     * @param \ride\library\orm\model\Model $model
     * @param array $entries
     * @param string $siteId
     * @param string $locale
     * @param string $titleFormat
     * @param string $teaserFormat
     * @param string $imageFormat
     * @param string $dateFormat
     * @return array Array with Content objects for the provided entries
     * @see \ride\library\cms\content\Content
     */
    public function getContentForEntries(Model $model, array $result, $siteId, $locale, $titleFormat = null, $teaserFormat = null, $imageFormat = null, $dateFormat = null) {
        $modelName = $model->getName();
        $entryFormatter = $this->orm->getEntryFormatter();

        try {
            $mapper = $this->contentFacade->getContentMapper($modelName);
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
