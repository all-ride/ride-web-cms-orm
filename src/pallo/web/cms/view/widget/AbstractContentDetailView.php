<?php

namespace pallo\web\cms\view\widget;

use pallo\library\cms\content\Content;
use pallo\library\template\GenericThemedTemplate;

use pallo\web\cms\orm\ContentProperties;
use pallo\web\mvc\view\TemplateView;

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
     * Sets the content
     * @param string $locale Code of the current locale
     * @param integer $widgetId Id of the widget
     * @param pallo\library\cms\content\Content $content
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