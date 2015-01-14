<?php

namespace ride\web\cms\node;

use ride\library\cms\node\PageNode;
use ride\library\orm\OrmManager;

use ride\web\cms\node\type\EntryNodeType;

/**
 * Node for a entry
 */
class EntryNode extends PageNode {

    /**
     * Property key for the model name
     * @var string
     */
    const PROPERTY_ENTRY_MODEL = 'entry.model';

    /**
     * Property key for the entry id
     * @var string
     */
    const PROPERTY_ENTRY_ID = 'entry.id';

    /**
     * Instance of the ORM manager
     * @var \ride\library\orm\OrmManager
     */
    protected $orm;

    /**
     * Sets the ORM manager
     * @param \ride\library\orm\OrmManager $orm Instance of the ORM manager
     * @return null
     */
    public function setOrmManager(OrmManager $orm) {
        $this->orm = $orm;
    }

    /**
     * Sets the entry
     * @param string $modelName Name of the entry model
     * @param string $id Id of the entry
     * @return null
     */
    public function setEntry($modelName, $id) {
        $this->set(self::PROPERTY_ENTRY_MODEL, $modelName, false);
        $this->set(self::PROPERTY_ENTRY_ID, $id, false);
    }

    /**
     * Gets the entry model
     * @return string Name of the entry model
     */
    public function getEntryModel() {
        return $this->get(self::PROPERTY_ENTRY_MODEL);
    }

    /**
     * Gets the entry id
     * @return string Id of the entry
     */
    public function getEntryId() {
        return $this->get(self::PROPERTY_ENTRY_ID);
    }

    /**
     * Gets the entry
     * @param string $locale Code of the locale
     * @return mixed|null Instance of the entry if found, null otherwise
     */
    public function getEntry($locale = null) {
        if (isset($this->entries[$locale])) {
            return $this->entries[$locale];
        }

        $model = $this->getEntryModel();
        $id = $this->getEntryId();
        if (!$model || !$id) {
            return null;
        }

        $model = $this->orm->getModel($model);

        $this->entries[$locale] = $model->getById($id, $locale);

        return $this->entries[$locale];
    }

}
