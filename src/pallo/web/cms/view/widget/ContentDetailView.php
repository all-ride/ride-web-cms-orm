<?php

namespace pallo\web\cms\view\widget;

use pallo\library\cms\content\Content;
use pallo\library\mvc\view\View;

use pallo\web\cms\orm\ContentProperties;

/**
 * Interface for a content detail view
 */
interface ContentDetailView extends View {

    /**
     * Sets the content
     * @param string $locale Code of the current locale
     * @param integer $widgetId Id of the widget
     * @param pallo\library\cms\content\Content $content
     * @param joppa\orm\model\ContentProperties $contentProperties Properties
     * for the view
     * @return null
     */
	public function setContent($locale, $widgetId, Content $content, ContentProperties $contentProperties);

}