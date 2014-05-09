<?php

namespace ride\web\cms\view\widget;

use ride\library\html\Pagination;
use ride\library\template\GenericThemedTemplate;

use ride\web\cms\orm\ContentProperties;
use ride\web\mvc\view\TemplateView;

/**
 * View for a content overview
 */
abstract class AbstractContentOverviewView extends TemplateView implements ContentOverviewView {

	/**
	 * Constructs a new content overview view
	 * @return null
	 */
	public function __construct() {
	    $this->template = new GenericThemedTemplate();
	    $this->template->setResource(static::TEMPLATE);

	    $this->javascripts = array();
	    $this->inlineJavascripts = array();
	    $this->styles = array();
	}

	/**
	 * Clones this view
	 * @return null
	 */
	public function __clone() {
	    $this->template = clone $this->template;
	}

	/**
	 * Sets the content
	 * @param string $locale Code of the current locale
	 * @param integer $widgetId Id of the widget
	 * @param array $result Array with Content objects
	 * @param \ride\web\cms\orm\ContentProperties $contentProperties Properties
	 * for the view
	 * @param array $filters Filters for the data
	 * @param array $arguments Used arguments
	 * @param ride\library\html\\Pagination $pagination Properties for the
	 * pagination
	 * @param string $moreUrl URL for the more link
	 * @return null
	 */
	public function setContent($locale, $widgetId, array $result, ContentProperties $contentProperties, array $filters, array $arguments = array(), Pagination $pagination = null, $moreUrl = null) {
	    $this->template->set('locale', $locale);
	    $this->template->set('widgetId', $widgetId);
	    $this->template->set('result', $result);
	    $this->template->set('properties', $contentProperties);
	    $this->template->set('title', $contentProperties->getTitle());
	    $this->template->set('emptyResultMessage', $contentProperties->getEmptyResultMessage());
	    $this->template->set('filters', $filters);
	    $this->template->set('arguments', $arguments);
	    $this->template->set('pagination', $pagination);
	    if ($moreUrl) {
    	    $this->template->set('moreUrl', $moreUrl);
    	    $this->template->set('moreLabel', $contentProperties->getMoreLabel());
	    } else {
    	    $this->template->set('moreUrl', null);
	    }

	    $this->processContent();
	}

	/**
	 * Hook to process the content set to this view
	 * @return null
	 */
	protected function processContent() {

	}

}
