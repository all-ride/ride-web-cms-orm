<?php

namespace ride\web\cms\orm\model;

use ride\library\orm\model\GenericModel;
use ride\library\StringHelper;

/**
 * Model for the texts of the text widget
 */
class TextModel extends GenericModel {

	/**
	 * Gets a specific version of a text
	 * @param integer|TextData $text Primary key of the text or an already loaded text
	 * @param integer $version The version to lookup
	 * @param string $locale The locale of the text
	 * @return \ride\web\cms\orm\model\TextData |null The text in the provided version if found, null otherwise
	 */
	public function getTextVersion($text, $version, $locale = null) {
		$id = $this->getPrimaryKey($text);

		$logModel = $this->getLogModel();

		return $logModel->getEntryByVersion(self::NAME, $id, $version, $locale);
	}

	/**
	 * Get the history of a text
	 * @param integer|TextData $text Primary key of the text or an already loaded text
	 * @param string $locale The locale of the text
	 * @return array Array with LogData objects
	 */
	public function getTextHistory($text, $locale = null) {
		$id = $this->getPrimaryKey($text);

		$logModel = $this->getLogModel();

		$logs = $logModel->getLog(self::NAME, $id, null, $locale);

		foreach ($logs as $index => $log) {
			if (!$log->changes) {
				unset($logs[$index]);
			}
		}

		return $logs;
	}

	/**
	 * Saves the data in the model
	 * @param mixed $entry
	 * @return nulls
	 */
	public function saveEntry($entry) {
		// generate a name for the text
		if ($entry->getTitle()) {
			$entry->name = $entry->getTitle();
		} elseif ($entry->getBody()) {
			$strippedBody = strip_tags($entry->getBody());
			if ($strippedBody) {
				$body = $strippedBody;
			} else {
				$body = $entry->getBody();
			}

			$entry->name = StringHelper::truncate($body, 30);
		} elseif ($entry->getImage()) {
			$entry->name = $entry->getImage();
		} else {
			$entry->name = 'Text';
		}

		// perform the actual saving
		return parent::saveEntry($entry);
	}

}
