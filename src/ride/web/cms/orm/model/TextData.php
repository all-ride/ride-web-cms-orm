<?php

namespace ride\web\cms\orm\model;

use ride\library\orm\model\data\Data;

use ride\web\cms\text\Text;

/**
 * Data container for a text
 */
class TextData extends Data implements Text {

    /**
     * Id of the format of the text
     * @var string
     */
    protected $format;

    /**
     * Title of the text
     * @var string
     */
	protected $title;

	/**
	 * Body text
	 * @var string
	 */
	protected $body;

	/**
	 * Path to a image
	 * @var string
	 */
	protected $image;

	/**
	 * Alignment of the image
	 * @var string
	 */
	protected $imageAlignment;

	/**
	 * Constructs a new instance
	 * @param string $format Name of the format
	 * @param string $body Body text
	 * @return null
	 */
	public function __construct($format = null, $body = null) {
	    $this->format = $format;
	    $this->body = $body;

	    $this->title = null;
	    $this->image = null;
	    $this->imageAlignment = null;
	}

	/**
	 * Sets the name of the format
	 * @param string $format Name of the format
	 * @return null
	 */
	public function setFormat($format) {
	    $this->format = $format;
	}

	/**
	 * Gets the name of the format
	 * @return string
	 */
	public function getFormat() {
	    return $this->format;
	}

	/**
	 * Sets the title
	 * @param string $title
	 * @return null
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Gets the title
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the body
	 * @param string $body
	 * @return null
	 */
	public function setBody($body) {
	    $this->body = $body;
	}

	/**
	 * Gets the body
	 * @return string
	 */
	public function getBody() {
	    return $this->body;
	}

	/**
	 * Sets a image
	 * @param string $image
	 * @return null
	 */
	public function setImage($image) {
	    $this->image = $image;
	}

	/**
	 * Gets the image
	 * @return string
	 */
	public function getImage() {
	    return $this->image;
	}

	/**
	 * Sets the image alignment
	 * @param string $alignment
	 * @return null
	 */
	public function setImageAlignment($imageAlignment) {
	    $this->imageAlignment = $imageAlignment;
	}

	/**
	 * Gets the image alignment
	 * @return string
	 */
	public function getImageAlignment() {
	    return $this->imageAlignment;
	}

}
