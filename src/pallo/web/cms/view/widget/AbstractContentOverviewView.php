<?php

namespace pallo\web\cms\view\widget;

use pallo\library\html\Pagination;
use pallo\library\template\GenericThemedTemplate;

use pallo\web\cms\orm\ContentProperties;
use pallo\web\mvc\view\TemplateView;

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
	 * @param pallo\web\cms\orm\ContentProperties $contentProperties Properties
	 * for the view
	 * @param pallo\library\html\\Pagination $pagination Properties for the
	 * pagination
	 * @param string $moreUrl URL for the more link
	 * @return null
	 */
	public function setContent($locale, $widgetId, array $result, ContentProperties $contentProperties, Pagination $pagination = null, $moreUrl = null) {
	    $this->template->set('locale', $locale);
	    $this->template->set('widgetId', $widgetId);
	    $this->template->set('result', $result);
	    $this->template->set('properties', $contentProperties);
	    $this->template->set('title', $contentProperties->getTitle());
	    $this->template->set('emptyResultMessage', $contentProperties->getEmptyResultMessage());
	    $this->template->set('pagination', $pagination);
	    if ($moreUrl) {
    	    $this->template->set('moreUrl', $moreUrl);
    	    $this->template->set('moreLabel', $contentProperties->getMoreLabel());
	    } else {
    	    $this->template->set('moreUrl', null);
	    }
	}

}