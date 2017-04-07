<?php

namespace ride\web\cms\content\mapper;

use ride\library\cms\content\mapper\AbstractContentMapper;
use ride\library\cms\content\mapper\SearchableContentMapper;
use ride\library\cms\content\Content;
use ride\library\cms\exception\CmsException;
use ride\library\cms\node\NodeModel;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\entry\format\EntryFormatter;
use ride\library\orm\model\Model;

/**
 * Abstract implementation of a ContentMapper for a model of the ORM module
 */
class OrmContentMapper extends AbstractContentMapper implements SearchableContentMapper {

	/**
	 * Model of the content to map
	 * @var \ride\library\orm\Model
	 */
	protected $model;

	/**
	 * Instance of the data formatter
	 * @var \ride\library\orm\entry\format\EntryFormatter
	 */
	protected $entryFormatter;

	/**
	 * Recursive depth of the query
	 * @var integer
	 */
	protected $recursiveDepth;

	/**
	 * Flag to see how unlocalized data is fetched
	 * @var string
	 */
	protected $fetchUnlocalized;

	/**
	 * The data format for the title
	 * @var string|boolean
	 */
	protected $titleFormat;

	/**
	 * The data format for the teaser
	 * @var string|boolean
	 */
	protected $teaserFormat;

	/**
	 * The data format for the image
	 * @var string|boolean
	 */
	protected $imageFormat;

	/**
	 * The data format for the date
	 * @var string|boolean
	 */
	protected $dateFormat;

	/**
     * Construct a new ORM content mapper
     * @param \ride\library\cms\node\NodeModel $nodeModel Instance of the node
     * model
     * @param \ride\library\orm\model\Model $model Model to map
     * @param \ride\library\orm\entry\format\EntryFormatter $entryFormatter
     * @param integer $recursiveDepth Recursive depth for the queries
     * @param boolean|string $fetchUnlocalized Flag to see how unlocalized
     * data is fetched
     * @return null
	 */
	public function __construct(NodeModel $nodeModel, Model $model, EntryFormatter $entryFormatter, $recursiveDepth = 1, $fetchUnlocalized = null) {
	    parent::__construct($nodeModel);

        if ($fetchUnlocalized === null) {
            $fetchUnlocalized = true;
        }

        $this->model = $model;
        $this->entryFormatter = $entryFormatter;
        $this->recursiveDepth = $recursiveDepth;
        $this->fetchUnlocalized = $fetchUnlocalized;
	}

    /**
     * Get a generic content object for the provided data
     * @param mixed $data data object of the model or the id of a data object
     * @return \ride\library\cms\content\Content Generic content object
     */
    public function getContent($site, $locale, $data) {
        if ($data === null) {
            throw new CmsException('Could not get the content: provided data is empty');
        }

        $entry = $this->getEntry($site, $locale, $this->recursiveDepth, $this->fetchUnlocalized, $data);
        if (!$entry) {
            return null;
        }

        return $this->getContentFromEntry($entry);
    }

    /**
     * Get an entry from the model
     * @param integer|object $entry
     * @return mixed
     */
    protected function getEntry($site, $locale, $recursiveDepth, $fetchUnlocalized, $entry, $idField = null) {
        $entryLocale = null;
        if ($entry instanceof LocalizedEntry) {
            $entryLocale = $entry->getLocale();
            $isObject = true;
        } elseif (is_object($entry)) {
            $isObject = true;
        } else {
            $isObject = false;
        }

        if ($isObject && ($entryLocale && $entryLocale == $locale)) {
            return $entry;
        } elseif ($isObject) {
            $idField = ModelTable::PRIMARY_KEY;

            $id = $this->model->getReflectionHelper()->getProperty($entry, $idField);
        } else {
            if (!$idField) {
                $idField = ModelTable::PRIMARY_KEY;
            }

            $id = $entry;
        }

        if ($idField == ModelTable::PRIMARY_KEY && $fetchUnlocalized) {
            $entry = $this->model->createProxy($id, $locale);

            return $entry;
        }

        $query = $this->model->createQuery($locale);
        $query->setRecursiveDepth($recursiveDepth);
        $query->addCondition('{' . $idField . '} = %1%', $id);
        if ($fetchUnlocalized) {
            $query->setFetchUnlocalized(true);
        }

        $entry = $query->queryFirst();
        if (!$entry && is_numeric($id) && $idField != ModelTable::PRIMARY_KEY) {
            $query = $this->model->createQuery($locale);
            $query->setRecursiveDepth($recursiveDepth);
            $query->addCondition('{' . ModelTable::PRIMARY_KEY . '} = %1%', $id);
            if ($fetchUnlocalized) {
                $query->setFetchUnlocalized(true);
            }

            $entry = $query->queryFirst();
        }

        if (!$fetchUnlocalized && (!$entry || ($entry instanceof LocalizedEntry && !$entry->isLocalized()))) {
            return null;
        }

        return $entry;
    }

    /**
     * Creates a generic content object from the provided entry
     * @param mixed $entry
     * @return \ride\library\cms\content\Content
     */
    protected function getContentFromEntry($entry, $url = null, $titleFormat = null, $teaserFormat = null, $imageFormat = null, $dateFormat = null) {
        if (!$this->titleFormat) {
        	$this->initDataFormats();
        }

        $teaser = null;
        $image = null;
        $date = null;

        if ($titleFormat) {
            $title = $this->entryFormatter->formatEntry($entry, $titleFormat);
        } else {
            $title = $this->entryFormatter->formatEntry($entry, $this->titleFormat);
        }

        if (!$title) {
            $title = $this->model->getName() . ' #' . $entry->getId();
        }

        if ($teaserFormat) {
            $teaser = $this->entryFormatter->formatEntry($entry, $teaserFormat);
        } elseif ($this->teaserFormat) {
            $teaser = $this->entryFormatter->formatEntry($entry, $this->teaserFormat);
        }

        if ($imageFormat) {
            $image = $this->entryFormatter->formatEntry($entry, $imageFormat);
        } elseif ($this->imageFormat) {
            $image = $this->entryFormatter->formatEntry($entry, $this->imageFormat);
        }

        if ($dateFormat) {
            $date = $this->entryFormatter->formatEntry($entry, $dateFormat);
        } elseif ($this->dateFormat) {
            $date = $this->entryFormatter->formatEntry($entry, $this->dateFormat);
        }

        return new Content($this->model->getName(), $title, $url, $teaser, $image, $date, $entry);
    }

    /**
     * Initialize the data formats of the model
     * @return null
     */
    protected function initDataFormats() {
        $meta = $this->model->getMeta();

        $this->titleFormat = $meta->getFormat(EntryFormatter::FORMAT_TITLE);
        $this->teaserFormat = $meta->getFormat(EntryFormatter::FORMAT_TEASER);
        $this->imageFormat = $meta->getFormat(EntryFormatter::FORMAT_IMAGE);
        $this->dateFormat = $meta->getFormat(EntryFormatter::FORMAT_DATE);
    }

    /**
     * Gets the search results
     * @param string $site Id of the site
     * @param string $locale Code of the current locale
     * @param string $query Full search query
     * @param string $queryTokens Full search query parsed in tokens
     * @param integer $page number of the result page (optional)
     * @param integer $pageItems number of items per page (optional)
     * @return \ride\library\cms\content\ContentResult
     * @see \ride\library\cms\content\Content
     */
    public function searchContent($site, $locale, $query, array $queryTokens, $page = null, $pageItems = null) {
        $collection = $this->model->collect(array(
            'distinct' => true,
            'query' => $query,
            'limit' => $pageItems,
            'page' => $page,
        ), $locale);

        foreach ($collection as $entryId => $entry) {
            $collection[$entryId] = $this->getContentFromEntry($entry);
        }

        return $collection;
    }

}
