<?php

namespace pallo\web\cms\orm\model;

use pallo\library\orm\model\GenericModel;

/**
 * Model for the texts of the text widget
 */
class TextModel extends GenericModel {

	/**
	 * Gets a specific version of a text
	 * @param integer|TextData $text Primary key of the text or an already loaded text
	 * @param integer $version The version to lookup
	 * @param string $locale The locale of the text
	 * @return joppa\text\model\data\TextData|null The text in the provided version if found, null otherwise
	 */
	public function getTextVersion($text, $version, $locale = null) {
		$id = $this->getPrimaryKey($text);

		$logModel = $this->getLogModel();

		return $logModel->getDataByVersion(self::NAME, $id, $version, $locale);
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

}