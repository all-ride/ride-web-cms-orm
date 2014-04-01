<?php

namespace ride\web\cms\view\widget;

use ride\library\cms\content\Content;
use ride\library\mvc\view\View;

use ride\web\cms\orm\ContentProperties;

/**
 * Interface for a content detail view
 */
interface ContentDetailView extends View {

    /**
     * Sets the content
     * @param string $locale Code of the current locale
     * @param integer $widgetId Id of the widget
     * @param \ride\library\cms\content\Content $content
     * @param \ride\web\cms\orm\ContentProperties $contentProperties Properties
     * for the view
     * @return null
     */
	public function setContent($locale, $widgetId, Content $content, ContentProperties $contentProperties);

}