<?php

namespace ride\web\cms\content\mapper\io;

use ride\library\cms\content\mapper\io\ContentMapperIO;
use ride\library\cms\node\NodeModel;
use ride\library\orm\OrmManager;

use ride\web\cms\content\mapper\GenericOrmContentMapper;
use ride\web\cms\content\mapper\SearchableOrmContentMapper;
use ride\web\cms\orm\model\SearchableModel;
use ride\web\cms\orm\ContentProperties;

/**
 * Implementation to load content mappers from the dependency injector
 */
class OrmContentMapperIO implements ContentMapperIO {

    /**
     * Array with the mappers
     * @var array
     */
    protected $mappers;

    /**
     * Instance of the ORM manager
     * @var \ride\library\orm\OrmManager
     */
    protected $orm;

    /**
     * Instance of the node model
     * @var \ride\library\cms\node\NodeModel
     */
    protected $nodeMode;

    /**
     * Constructs a new ORM content mapper IO
     * @return null
     */
    public function __construct(OrmManager $orm, NodeModel $nodeModel) {
        $this->mappers = null;
        $this->orm = $orm;
        $this->nodeModel = $nodeModel;
    }

    /**
     * Gets a content mapper
     * @return \ride\library\cms\content\mapper\ContentMapper|null
     */
    public function getContentMapper($type) {
        $this->loadMappers();

        if (isset($this->mappers[$type])) {
            return $this->mappers[$type];
        }

        return null;
    }

    /**
     * Gets the available mappers
     * @return array Array with ContentMapper objects
     * @see \ride\library\cms\content\mapper\ContentMapper
     */
    public function getContentMappers() {
        $this->loadMappers();

        return $this->mappers;
    }

    /**
     * Loads the mappers for the detail widget instances
     * @return null
     */
    protected function loadMappers() {
        if ($this->mappers !== null) {
            return;
        }

        $this->mappers = array();

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

            if ($model instanceof SearchableModel) {
                $this->mappers[$modelName] = new SearchableOrmContentMapper($this->nodeModel, $node, $model, $entryFormatter, $widgetProperties);
            } else {
                $this->mappers[$modelName] = new GenericOrmContentMapper($this->nodeModel, $node, $model, $entryFormatter, $widgetProperties);
            }
        }
    }

}
