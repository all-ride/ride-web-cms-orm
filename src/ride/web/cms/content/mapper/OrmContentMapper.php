<?php

namespace ride\web\cms\content\mapper;

use ride\library\cms\content\mapper\AbstractContentMapper;
use ride\library\cms\content\Content;
use ride\library\cms\exception\CmsException;
use ride\library\cms\node\NodeModel;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\data\format\DataFormatter;
use ride\library\orm\model\Model;

/**
 * Abstract implementation of a ContentMapper for a model of the ORM module
 */
class OrmContentMapper extends AbstractContentMapper {

	/**
	 * Model of the content to map
	 * @var \ride\library\orm\Model
	 */
	protected $model;

	/**
	 * Instance of the data formatter
	 * @var \ride\library\orm\model\data\format\DataFormatter
	 */
	protected $dataFormatter;

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
     * @param \ride\library\orm\model\data\format\DataFormatter $dataFormatter
     * @param integer $recursiveDepth Recursive depth for the queries
     * @param boolean|string $fetchUnlocalized Flag to see how unlocalized
     * data is fetched
     * @return null
	 */
	public function __construct(NodeModel $nodeModel, Model $model, DataFormatter $dataFormatter, $recursiveDepth = 1, $fetchUnlocalized = null) {
	    parent::__construct($nodeModel);

        if ($fetchUnlocalized === null) {
            $fetchUnlocalized = true;
        }

        $this->model = $model;
        $this->dataFormatter = $dataFormatter;
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

        $data = $this->getData($site, $locale, $this->recursiveDepth, $this->fetchUnlocalized, $data);
        if (!$data) {
            return null;
        }

        return $this->getContentFromData($data);
    }

	/**
     * Get a data object from the model
     * @param integer|object $data When an object is provided, the object will
     * be returned. When a primary key is provided,
     * the data object will be looked up in the model
     * @return mixed
	 */
    protected function getData($site, $locale, $recursiveDepth, $fetchUnlocalized, $data, $idField = null) {
        $isScalar = is_scalar($data);
        if (!$isScalar && ($data->dataLocale && $data->dataLocale == $locale)) {
            return $data;
        } elseif (!$isScalar) {
            $idField = ModelTable::PRIMARY_KEY;
            $id = $this->model->getReflectionHelper()->getProperty($data, $idField);
        } else {
            if (!$idField) {
                $idField = ModelTable::PRIMARY_KEY;
            }
            $id = $data;
        }

        $query = $this->model->createQuery($locale);
        $query->setRecursiveDepth($recursiveDepth);
        $query->addCondition('{' . $idField . '} = %1%', $id);
        if ($fetchUnlocalized) {
            $query->setFetchUnlocalizedData(true);
        }

        return $query->queryFirst();
    }

    /**
     * Creates a generic content object from the provided data
     * @param mixed $data
     * @return \ride\library\cms\content\Content
     */
    protected function getContentFromData($data, $url = null, $titleFormat = null, $teaserFormat = null, $imageFormat = null, $dateFormat = null) {
        if (!$this->titleFormat) {
        	$this->initDataFormats();
        }

        $teaser = null;
        $image = null;
        $date = null;

        if ($titleFormat) {
            $title = $this->dataFormatter->formatData($data, $titleFormat);
        } else {
            $title = $this->dataFormatter->formatData($data, $this->titleFormat);
        }

        if ($teaserFormat) {
            $teaser = $this->dataFormatter->formatData($data, $teaserFormat);
        } elseif ($this->teaserFormat) {
            $teaser = $this->dataFormatter->formatData($data, $this->teaserFormat);
        }

        if ($imageFormat) {
            $image = $this->dataFormatter->formatData($data, $imageFormat);
        } elseif ($this->imageFormat) {
            $image = $this->dataFormatter->formatData($data, $this->imageFormat);
        }

        if ($dateFormat) {
            $date = $this->dataFormatter->formatData($data, $dateFormat);
        } elseif ($this->dateFormat) {
            $date = $this->dataFormatter->formatData($data, $this->dateFormat);
        }

        return new Content($this->model->getName(), $title, $url, $teaser, $image, $date, $data);
    }

    /**
     * Initialize the data formats of the model
     * @return null
     */
    protected function initDataFormats() {
        $meta = $this->model->getMeta();
        $modelTable = $meta->getModelTable();

        $this->titleFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_TITLE);
        $this->teaserFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_TEASER, false);
        $this->imageFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_IMAGE, false);
        $this->dateFormat = $modelTable->getDataFormat(DataFormatter::FORMAT_DATE, false);
    }

}
