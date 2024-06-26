<?php

namespace ride\web\cms\node\type;

use ride\library\cms\node\type\AbstractNodeType;
use ride\library\orm\OrmManager;

use ride\web\cms\node\EntryNode;

/**
 * CMS node type for a ORM entry
 */
class EntryNodeType extends AbstractNodeType implements NodeType {

    /**
     * Name of this node type
     * @var string
     */
    const NAME = 'entry';

    protected $orm;

    /**
     * Constructs a new entry
     * @param \ride\library\orm\OrmManager $ormManager Instance of the ORM
     * manager
     * @param string $modelName Name of the model
     * @return null
     */
    public function __construct(OrmManager $ormManager) {
        $this->orm = $ormManager;
    }

    /**
     * Gets the callback for the frontend route
     * @return string|array|\ride\web\cms\controller\frontend\NodeController
     */
    public function getFrontendCallback() {
        return array('ride\\web\\cms\\controller\\frontend\\NodeController', 'indexAction');
    }

    /**
     * Gets the id of the route to create a new node of this type
     * @return string Route id
     */
    public function getRouteAdd() {
        return 'cms.entry.add';
    }

    /**
     * Gets the id of the route to edit a node of this type
     * @return string Route id
     */
    public function getRouteEdit() {
        return 'cms.entry.edit';
    }

    /**
     * Gets the id of the route to clone a node of this type
     * @return string Route id
     */
    public function getRouteClone() {
        return 'cms.node.clone';
    }

    /**
     * Gets the id of the route to delete a node of this type
     * @return string Route id
     */
    public function getRouteDelete() {
        return 'cms.node.delete';
    }

    /**
     * Creates a new node of this type
     * @return \ride\library\cms\node\Node
     */
    public function createNode() {
        $node = new EntryNode($this->getName());
        $node->setOrmManager($this->orm);

        return $node;
    }

}
