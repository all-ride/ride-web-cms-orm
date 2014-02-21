<?php

namespace ride\web\cms\view\widget;

use ride\library\cms\content\Content;
use ride\library\template\GenericThemedTemplate;

use ride\web\cms\orm\ContentProperties;
use ride\web\mvc\view\TemplateView;

/**
 * View for content detail
 */
abstract class AbstractContentDetailView extends TemplateView implements ContentDetailView {

	/**
	 * Constructs a new content detail view
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
     * @param ride\library\cms\content\Content $content
     * @param joppa\orm\model\ContentProperties $contentProperties Properties
     * for the view
     * @return null
     */
	public function setContent($locale, $widgetId, Content $content, ContentProperties $contentProperties) {
	    $this->template->set('locale', $locale);
	    $this->template->set('widgetId', $widgetId);
	    $this->template->set('content', $content);
	    $this->template->set('properties', $contentProperties);
	}
}