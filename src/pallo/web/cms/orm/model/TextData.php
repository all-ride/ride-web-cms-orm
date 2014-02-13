<?php

namespace pallo\web\cms\orm\model;

use pallo\library\orm\model\data\Data;

use pallo\web\cms\text\Text;

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
	 * The text
	 * @var string
	 */
	protected $text;

	/**
	 * Constructs a new instance
	 * @param string $format Name of the format
	 * @param string $text Name of the text
	 * @return null
	 */
	public function __construct($format = null, $text = null) {
	    $this->format = $format;
	    $this->text = $text;
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
	 * Sets the text
	 * @param string $text
	 * @return null
	 */
	public function setText($text) {
	    $this->text = $text;
	}

	/**
	 * Gets the text
	 * @return string
	 */
	public function getText() {
	    return $this->text;
	}

}