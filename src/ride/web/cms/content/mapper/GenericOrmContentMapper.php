<?php

namespace ride\web\cms\content\mapper;

use ride\library\cms\exception\CmsException;
use ride\library\cms\content\mapper\SearchableContentMapper;
use ride\library\cms\node\NodeModel;
use ride\library\cms\node\Node;
use ride\library\orm\entry\format\EntryFormatter;
use ride\library\orm\model\Model;
use ride\library\widget\WidgetProperties;

use ride\web\cms\orm\ContentProperties;

/**
 * Content mapper for models defined with the detail widget
 */
class GenericOrmContentMapper extends OrmContentMapper implements SearchableContentMapper {

    /**
     * Property for the URL
     * @var string
     */
    const PROPERTY_URL = 'url';

    /**
     * Node containing the detail widget
     * @var \ride\library\cms\node\Node
     */
    protected $node;

    /**
     * Widget properties of the detail widget
     * @var \ride\library\widget\WidgetProperties
     */
    protected $properties;

    /**
     * Parsed arguments of the widget
     * @var array
     */
    protected $arguments;

    /**
     * Constructs a new content mapper for a detail widget
     * @param \ride\library\cms\node\NodeModel $nodeModel
     * @param \ride\library\cms\node\Node $node
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\orm\entry\format\EntryFormatter $entryFormatter
     * @param \ride\library\widget\WidgetProperties $properties
     * @return null
     */
    public function __construct(NodeModel $nodeModel, Node $node, Model $model, EntryFormatter $entryFormatter, WidgetProperties $properties) {
        parent::__construct($nodeModel, $model, $entryFormatter);

        $this->node = $node;
        $this->properties = $properties;

        $this->arguments = array();
    }

    /**
     * Get a generic content object for the provided data
     * @param mixed $data data object of the model or the id of a data object
     * @return \ride\library\cms\content\Content Generic content object
     */
    public function getContent($site, $locale, $data) {
        if ($data === null) {
            throw new CmsException('Could not get content: provided data is empty');
        }

        $index = $site . '-' . $locale;
        if (!isset($this->arguments[$index])) {
            $this->parseArguments($index, $site, $locale);
        }

        $recursiveDepth = $this->arguments[$index][ContentProperties::PROPERTY_RECURSIVE_DEPTH];
        $includeUnlocalized = $this->arguments[$index][ContentProperties::PROPERTY_INCLUDE_UNLOCALIZED];
        $idField = $this->arguments[$index][ContentProperties::PROPERTY_ID_FIELD];

        $entry = $this->getEntry($site, $locale, $recursiveDepth, $includeUnlocalized, $data, $idField);
        if (!$entry) {
            return null;
        }

        $id = $this->reflectionHelper->getProperty($entry, $idField);
        if ($id) {
            $url = $this->arguments[$index][self::PROPERTY_URL] . $id;
        } else {
            $url = null;
        }

        $titleFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_TITLE];
        $teaserFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_TEASER];
        $imageFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_IMAGE];
        $dateFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_DATE];

        return $this->getContentFromEntry($entry, $url, $titleFormat, $teaserFormat, $imageFormat, $dateFormat);
    }

    /**
     * Loads the properties of the detail widget for the provided site and locale
     * @param string $index The key to store the values
     * @param string $site Id of the site
     * @param string $locale Code of the current locale
     * @return null
     */
    protected function parseArguments($index, $site, $locale) {
        $properties = new ContentProperties();
        $properties->getFromWidgetProperties($this->properties, $locale);

        $this->reflectionHelper = $this->model->getReflectionHelper();

        $this->arguments[$index] = array(
            ContentProperties::PROPERTY_RECURSIVE_DEPTH => $properties->getRecursiveDepth(),
            ContentProperties::PROPERTY_INCLUDE_UNLOCALIZED => $properties->getIncludeUnlocalized(),
            ContentProperties::PROPERTY_ID_FIELD => $properties->getIdField(),
            ContentProperties::PROPERTY_FORMAT_TITLE => $properties->getContentTitleFormat(),
            ContentProperties::PROPERTY_FORMAT_TEASER => $properties->getContentTeaserFormat(),
            ContentProperties::PROPERTY_FORMAT_IMAGE => $properties->getContentImageFormat(),
            ContentProperties::PROPERTY_FORMAT_DATE => $properties->getContentDateFormat(),
            self::PROPERTY_URL => rtrim($this->node->getUrl($locale, $this->baseScript), '/') . '/',
        );
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
        $index = $site . '-' . $locale;
        if (!isset($this->arguments[$index])) {
            $this->parseArguments($index, $site, $locale);
        }

        $recursiveDepth = $this->arguments[$index][ContentProperties::PROPERTY_RECURSIVE_DEPTH];
        $includeUnlocalized = $this->arguments[$index][ContentProperties::PROPERTY_INCLUDE_UNLOCALIZED];
        $idField = $this->arguments[$index][ContentProperties::PROPERTY_ID_FIELD];

        $titleFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_TITLE];
        $teaserFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_TEASER];
        $imageFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_IMAGE];
        $dateFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_DATE];

        $collection = $this->model->collect(array(
            'query' => $query,
            'limit' => $pageItems,
            'page' => $page,
        ), $locale);

        foreach ($collection as $entryId => $entry) {
            $id = $this->reflectionHelper->getProperty($entry, $idField);
            if ($id) {
                $url = $this->arguments[$index][self::PROPERTY_URL] . $id;
            } else {
                $url = null;
            }

            $collection[$entryId] = $this->getContentFromEntry($entry, $url, $titleFormat, $teaserFormat, $imageFormat, $dateFormat);
        }

        return $collection;
    }

}
